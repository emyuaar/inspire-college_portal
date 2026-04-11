<?php

namespace App\Services;

use App\Models\User;
use App\Models\Crm\Enrolment;

class EnrolmentAccessService
{
    /**
     * Determine if a learner has full "Continue Learning" access to an enrolment.
     * This considers CRM Verification, Enrolment Status, and Payment/Installment health.
     */
    public function canAccessLearning(Enrolment $enrolment, ?User $user = null): bool
    {
        $user = $user ?? $enrolment->learner;

        if (!$user) {
            return false;
        }

        // 1. CRM Verification (Admissions Review)
        // This is the primary gate. If not verified, NO learning access.
        if (!$user->isVerified()) {
            return false;
        }

        // 2. Denied Status
        if ($enrolment->status->status === 'denied') {
            return false;
        }

        // 3. Payment / Installment health
        // Enrolment model has 'installment_access_status' which handles 
        // partner installments, website installments, and grace periods.
        $accessStatus = $enrolment->installment_access_status;
        
        if (!$accessStatus->allowed) {
            return false;
        }

        // 4. Basic Activity Check
        // If it's a pending plan, they shouldn't access yet.
        $statusStr = strtolower($enrolment->status->status ?? '');
        if (in_array($statusStr, ['pending-plan', 'pending-payment'])) {
            // Check if they are in a grace period (allowed despite pending)
            if (!($accessStatus->grace_active ?? false)) {
                // If paid but still pending-payment, we might allow if verified? 
                // Typically system moves it to active, but let's be safe.
                if (!$this->isEffectivelyActive($enrolment)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the consolidated access state for UI display.
     */
    public function getAccessState(Enrolment $enrolment, ?User $user = null): object
    {
        $user = $user ?? $enrolment->learner;
        $accessStatus = $enrolment->installment_access_status;
        
        $state = (object) [
            'can_access' => $this->canAccessLearning($enrolment, $user),
            'is_verified' => $user->isVerified(),
            'requirements_met' => $user->areRequirementsMet(),
            'block_reason' => $accessStatus->reason,
            'grace_active' => $accessStatus->grace_active ?? false,
            'grace_until' => $accessStatus->grace_until ?? null,
            'due_info' => $accessStatus->due_info ?? null,
            'is_denied' => ($enrolment->status->status === 'denied'),
        ];

        return $state;
    }

    private function isEffectivelyActive(Enrolment $enrolment): bool
    {
        $statusStr = strtolower($enrolment->status->status ?? '');
        $allowedStatuses = ['active', 'paid', 'approved', 'installments_active'];
        
        if (in_array($statusStr, $allowedStatuses)) {
            return true;
        }

        // Also check if they have a paid order but status hasn't synced
        $paymentDetails = $enrolment->payment_status_details;
        return $paymentDetails['status'] === 'paid';
    }
}
