<?php

namespace App\Services;

use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\PartnerLearner;
use App\Models\Crm\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\LearnerAccountActivatedMail;

class LearnerActivationService
{
    /**
     * Activate a learner account after payment confirmation.
     */
    public function activate($enrolment)
    {
        return DB::connection('mysql_crm')->transaction(function () use ($enrolment) {
            $enrolment->refresh();

            // Do not activate if already active
            if ($enrolment->activation_status === 'active') {
                return $enrolment;
            }

            // Get learner details from partner_learner
            $partnerLearner = $enrolment->partnerLearner;
            
            if (!$partnerLearner) {
                // Fallback for existing enrolments
                if ($enrolment->learner_id) {
                    $user = User::find($enrolment->learner_id);
                    if ($user) {
                        $user->status_id = 2;
                        $user->crm_approved = 1;
                        $user->crm_approved_at = now();
                        $user->save();
                        
                        $enrolment->update([
                            'activation_status' => 'active',
                            'enrolment_status' => 'active',
                            'activated_at' => now(),
                        ]);
                        return $enrolment;
                    }
                }
                throw new \Exception('Learner record not found for this enrolment.');
            }

            // Check if user already exists
            $portalUser = User::where('email_address', $partnerLearner->personal_email)
                ->orWhere('email_address', $partnerLearner->email)
                ->first();

            DB::connection('mysql_portal')->beginTransaction();
            try {
                if (!$portalUser) {
                    // Create new learner user
                    $portalUser = User::create([
                        'org_id' => $partnerLearner->partner_id,
                        'first_name' => $partnerLearner->first_name,
                        'middle_name' => $partnerLearner->middle_name ?? '',
                        'sur_name' => $partnerLearner->last_name,
                        'email_address' => $partnerLearner->email ?? $partnerLearner->personal_email,
                        'password' => Hash::make(Str::random(40)),
                        'status_id' => 2, // Active
                        'crm_approved' => 1,
                        'crm_approved_at' => now(),
                    ]);

                    // If the email was not set (generated), update it now
                    if (!$partnerLearner->email) {
                        $dsEmail = "DS" . $portalUser->id . "@directskills.co.uk";
                        $portalUser->update(['email_address' => $dsEmail]);
                        $partnerLearner->update(['email' => $dsEmail]);
                    }
                } else {
                    $portalUser->status_id = 2; // Active
                    $portalUser->crm_approved = 1;
                    $portalUser->crm_approved_at = now();
                    $portalUser->save();
                }

                // Generate secure password setup token
                $token = Str::random(64);
                DB::connection('mysql_portal')->table('password_reset_tokens')->updateOrInsert(
                    ['email' => $portalUser->email_address],
                    [
                        'token' => Hash::make($token),
                        'created_at' => now(),
                    ]
                );

                DB::connection('mysql_portal')->commit();
            } catch (\Exception $e) {
                DB::connection('mysql_portal')->rollBack();
                throw $e;
            }

            // Link learner record with user account
            $partnerLearner->user_id = $portalUser->id;
            $partnerLearner->account_status = 'active';
            $partnerLearner->activation_status = 'active';
            $partnerLearner->save();

            // Update enrolment
            $enrolment->learner_id = $portalUser->id;
            $enrolment->activation_status = 'active';
            $enrolment->enrolment_status = 'active';
            $enrolment->activated_at = now();
            $enrolment->save();

            // Update linked order if exists
            Order::where('enrolment_id', $enrolment->id)
                ->where('partner_learner_id', $partnerLearner->id)
                ->update(['learner_id' => $portalUser->id]);

            // Generate secure password setup token
            $token = \Illuminate\Support\Str::random(64);
            
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $portalUser->email_address],
                [
                    'token' => \Illuminate\Support\Facades\Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Use config or env for portal URL
            $portalUrl = env('LEARNER_PORTAL_URL', 'https://portal.directskills.co.uk');
            $setupUrl = $portalUrl . '/reset-password/' . $token . '?email=' . urlencode($portalUser->email_address);

            // Send activation email
            try {
                Mail::to($partnerLearner->personal_email ?? $portalUser->email_address)->send(
                    new LearnerAccountActivatedMail($partnerLearner ?? $portalUser, $enrolment, $setupUrl)
                );
                
                $enrolment->welcome_email_sent_at = now();
                $enrolment->save();
                Log::info("Activation email sent to {$portalUser->email_address}");
            } catch (\Exception $e) {
                Log::error('Failed to send activation email: ' . $e->getMessage());
            }

            Log::info("Learner Activated: {$portalUser->email_address}. Password setup token generated.");

            return $enrolment;
        });
    }
}
