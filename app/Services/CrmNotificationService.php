<?php

namespace App\Services;

use App\Models\Crm\CrmUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmNotificationService
{
    /**
     * Send a notification to CRM admins when a partner submits a payment proof.
     */
    public function notifyAdminsForProofSubmission($proof)
    {
        // 1. Find CRM Users to notify (Super Admins or those with specific roles)
        // We look directly into CRM database tables
        $adminIds = DB::connection('mysql_crm')->table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where(function($q) {
                $q->where('roles.is_super', true)
                  ->orWhere('roles.slug', 'admin')
                  ->orWhere('roles.slug', 'finance');
            })
            ->select('users.id')
            ->distinct()
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        // 2. Prepare Notification Data
        $amount = $proof->submitted_amount ?? $proof->installment_amount ?? '0';
        $partnerName = $proof->partner ? ($proof->partner->first_name . ' ' . $proof->partner->sur_name) : 'Partner';
        
        $enrolment = $proof->enrolment;
        $learnerName = "Unknown Learner";
        if ($enrolment) {
            $pl = $enrolment->partnerLearner;
            if ($pl) {
                $learnerName = trim($pl->first_name . ' ' . $pl->last_name);
            } elseif ($enrolment->learner) {
                $learnerName = trim($enrolment->learner->first_name . ' ' . $enrolment->learner->sur_name);
            }
        }

        $notificationData = [
            'type' => 'payment_proof',
            'subtype' => 'partner_proof_submitted',
            'title' => 'Partner Payment Proof Submitted',
            'identifier' => '£' . number_format($amount, 2),
            'display_name' => $partnerName,
            'secondary_text' => 'Learner: ' . $learnerName,
            'badge' => 'PROOF',
            'entity_type' => 'partner_payment_proof',
            'entity_id' => $proof->id,
            'enrolment_id' => $enrolment ? $enrolment->id : null,
            'action_url' => '/admin/partner-proofs?proof_id=' . $proof->id, // Handled by CRM
            'route_name' => 'admin.partner-proofs.index',
            'route_params' => ['proof_id' => $proof->id],
            'required_permissions' => ['crm.payments.approve'],
            'fallback_route' => 'admin.dashboard',
            'message' => "Partner $partnerName submitted a payment proof of £" . number_format($amount, 2) . " for $learnerName.",
            'severity' => 'info',
            'created_at' => now()->toDateTimeString(),
        ];

        // 3. Insert into CRM notifications table for each admin
        foreach ($adminIds as $adminId) {
            DB::connection('mysql_crm')->table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\PartnerPaymentProofNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $adminId,
                'data' => json_encode($notificationData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
