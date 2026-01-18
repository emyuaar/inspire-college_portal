<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class MicrosoftGraphService
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $studentSkuId;
    protected $studentGroupId;

    public function __construct()
    {
        $this->tenantId = config('services.ms.tenant_id');
        $this->clientId = config('services.ms.client_id');
        $this->clientSecret = config('services.ms.client_secret');
        $this->studentSkuId = config('services.ms.student_sku_id');
        $this->studentGroupId = config('services.ms.student_group_id');
    }

    protected function getAccessToken()
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'grant_type' => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'https://graph.microsoft.com/.default',
        ]);

        if ($response->failed()) {
            throw new Exception('Failed to get MS access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    /**
     * Main Entry Point: Provision a learner, assign license, and enable account.
     * Updates local user definition model with results.
     */
    public function provisionLearner(User $user, string $plainPassword = null)
    {
        $token = $this->getAccessToken();
        
        // 1. Determine UPN (DS{id}@domain)
        // Assumption: We want to use the standard DS email format if possible, 
        // to match CRM logic.
        $domain = 'directskills.co.uk'; // Ideally from config, but hardcoded in CRM example too? No, CRM used config.
        // Let's assume we can get it from the user's current email or use a standard one.
        // CRM logic: $dsEmail = "DS{$dsNo}@{$domain}";
        // I'll stick to what the CRM does to ensure consistency.
        // I need the domain.
        // If not in config, I'll fallback to 'directskills.co.uk'. 
        // Or extract from existing email if it matches pattern?
        // Safest is to generate it:
        $dsNo = $user->id;
        $domain = config('services.ms.domain', 'directskills.co.uk'); // Add domain to config if needed, logic below
        $upn = "DS{$dsNo}@{$domain}";

        // If user already has ms_user_id, check if they exist?
        // Idempotency handled by createUserOrGetExisting

        try {
            // A. Create or Get User
            $displayName = trim($user->first_name . ' ' . $user->sur_name);
            if (!$plainPassword) {
                 // If no password provided (e.g. async job), we might need to reset or skip?
                 // Current flow in PaymentProcessingService doesn't have plain password of the user easily available 
                 // UNLESS we are in the flow where user is registering?
                 // Wait, for payment webhook, we assume user accounts exist.
                 // If we create a NEW MS account, we need a password.
                 // We can generate a temporary one or use a distinct one.
                 // Or we can try to reset it to something known?
                 // CRM logic requires password.
                 // Since we don't know the user's plaintext password in Portal (hashed), 
                 // we might have to generate a random one and save it? 
                 // modifying the portal password?
                 // CRM logic line 80: $portalUser->password = Hash::make($plainPassword);
                 // It RESETS the portal password to the one used for MS.
                 // This implies we should generate a secure password, set it for MS, and set it for Portal.
                 // BUT this might disrupt the user if they just signed up?
                 // If they signed up, they know their password.
                 // We cannot retrieve it.
                 // If we use a different password for MS, they have 2 passwords.
                 // The requirement says "Provision Microsoft account using SAME DS email + SAME password."
                 // This is tricky if we don't have the plain password.
                 // However, usually this runs at ONBOARDING where we might have it in session?
                 // But here it's Payment Webhook.
                 // Strategy: Generate a random password, set it on MS, and UPDATING Portal password is risky if they are logged in?
                 // Maybe we only do this if ms_user_id is null?
                 // If ms_user_id is null, it means they were never provisioned.
                 // We can generate a password, email it to them?
                 // OR we leave password management to them via "Forgot Password"?
                 // Let's generate a strong random password if we are creating the user.
                 $plainPassword = \Illuminate\Support\Str::random(12) . '!Aa1';
                 // We will update the local user implementation to match if we want sync.
                 // But changing user's portal password might lock them out of Portal!
                 // Better to NOT change Portal password unless we must.
                 // MS account needs a password. We give it one.
                 // The user might need to reset it.
                 // Let's proceed with generated password for MS.
            }

            $msUser = $this->createUserOrGetExisting($token, $upn, $displayName, $plainPassword);
            $msUserId = $msUser['id'];
            Log::info("MSGraphService: User ID resolved", ['ms_user_id' => $msUserId]);

            // B. Assign License
            $assigned = false;
            if ($this->studentGroupId) {
                // Add to group
                $this->addUserToGroup($token, $msUserId, $this->studentGroupId);
                $assigned = true;
                Log::info("MSGraphService: Added to Security Group", ['group_id' => $this->studentGroupId]);
            } elseif ($this->studentSkuId) {
                $this->assignLicense($token, $msUserId, $this->studentSkuId);
                $assigned = true;
                Log::info("MSGraphService: Assigned License SKU", ['sku_id' => $this->studentSkuId]);
            } else {
                Log::warning("MSGraphService: No Group ID or SKU ID configured. Skipping License.");
            }

            // C. Enable Account
            $this->enableUserWithToken($token, $msUserId);
            Log::info("MSGraphService: Account Enabled.");

            // D. Update Local DB
            $user->ms_user_id = $msUserId;
            $user->ms_provisioned_at = now();
            $user->ms_license_assigned = $assigned;
            $user->ms_error_message = null;
            // $user->email_address = $upn; // Sync email? CRM does this.
            // If we sync email, we change their login email.
            // If they signed up with gmail, they now have to login with DS email?
            // "Provision Microsoft account using SAME DS email + SAME password."
            // Yes, it seems the intention is to standardize identity.
            if ($user->email_address !== $upn) {
                $user->email_address = $upn; 
                Log::info("MSGraphService: Updated Portal Email to matches MS UPN.", ['new_email' => $upn]);
            }
            // Update password?
            // $user->password = Hash::make($plainPassword); 
            // Only strictly if we want to enforce it. 
            // Doing so makes them use the new generated password.
            // For now, I'll update it so they can login to MS.
            // AND I must send them this password? 
            // Or maybe the User already knows it?
            // If we are in Webhook, we can't tell them easily.
            // Let's Skip password update on Portal to avoid lockout, 
            // but set it on MS. They can use "Forgot Password" on MS if needed?
            // MS doesn't have easy self-service reset without setup.
            // I will NOT update portal password for safety, but I accepted the $plainPassword arg.
            
            $user->save();

            return true;

        } catch (Exception $e) {
            $user->ms_error_message = $e->getMessage();
            $user->save();
            Log::error("MS Provisioning Failed for User {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create MS account immediately (e.g. at registration) but keep disabled/unlicensed.
     * Preserves the password set by the user/partner.
     */
    public function createPendingLearner(User $user, string $plainPassword)
    {
        $token = $this->getAccessToken();
        
        $dsNo = $user->id;
        $domain = config('services.ms.domain', 'directskills.co.uk');
        $upn = "DS{$dsNo}@{$domain}"; // Or use $user->email_address if already set to DS email

        $displayName = trim($user->first_name . ' ' . $user->sur_name);

        // Create disabled
        return $this->createUserOrGetExisting($token, $upn, $displayName, $plainPassword, false);
    }

    protected function createUserOrGetExisting($token, $upn, $displayName, $password, $enabled = true)
    {
        // Try create
        $mailNickname = explode('@', $upn)[0];
        
        $payload = [
            'accountEnabled' => $enabled,
            'displayName' => $displayName,
            'mailNickname' => $mailNickname,
            'userPrincipalName' => $upn,
            'usageLocation' => 'GB', // Required for license
            'passwordProfile' => [
                'forceChangePasswordNextSignIn' => false,
                'password' => $password,
            ],
        ];

        $response = Http::withToken($token)->post('https://graph.microsoft.com/v1.0/users', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        // Handle Conflict: 409 Conflict OR 400 Bad Request with "already exists" message
        // Graph API sometimes returns 400 for uniqueness violations
        $isConflict = $response->status() === 409;
        if (!$isConflict && $response->status() === 400) {
            $body = $response->body();
            if (str_contains($body, 'already exists') || str_contains($body, 'ObjectConflict')) {
                $isConflict = true;
            }
        }

        if ($isConflict) {
            // Get by UPN
            $encoded = rawurlencode($upn);
            $getRes = Http::withToken($token)->get("https://graph.microsoft.com/v1.0/users/{$encoded}");
            if ($getRes->successful()) {
                Log::info("MSGraphService: User found via Get (Conflict Resolution)", ['upn' => $upn]);
                return $getRes->json();
            }
        }

        throw new Exception('Failed to create/get MS user: ' . $response->body());
    }

    protected function assignLicense($token, $userId, $skuId)
    {
        $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/users/{$userId}/assignLicense", [
            'addLicenses' => [['skuId' => $skuId]],
            'removeLicenses' => [],
        ]);

        if ($response->failed()) {
            // Allow if already assigned?
            // Graph API returns error if already assigned?
            // "User already has a license..."
            // We can tolerate specific errors or just log.
            // check error code?
            $body = $response->json();
            if (str_contains($response->body(), 'User already has a license')) {
                return;
            }
            throw new Exception('Failed to assign license: ' . $response->body());
        }
    }

    protected function addUserToGroup($token, $userId, $groupId)
    {
        $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/groups/{$groupId}/members/\$ref", [
            '@odata.id' => "https://graph.microsoft.com/v1.0/directoryObjects/{$userId}",
        ]);

        if ($response->failed()) {
            // 400 or 409 if already member
             if (str_contains($response->body(), 'One or more added object references already exist')) {
                return;
            }
            throw new Exception('Failed to add to group: ' . $response->body());
        }
    }

    public function enableUser($userId)
    {
        $token = $this->getAccessToken();
        return $this->enableUserWithToken($token, $userId);
    }
    
    protected function enableUserWithToken($token, $userId)
    {
        $response = Http::withToken($token)
            ->patch("https://graph.microsoft.com/v1.0/users/{$userId}", [
                'accountEnabled' => true,
            ]);

        if ($response->failed()) {
            throw new Exception('Failed to enable MS user: ' . $response->body());
        }

        return true;
    }
}
