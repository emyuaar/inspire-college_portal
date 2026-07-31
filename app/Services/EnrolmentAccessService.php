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
        
        $canAccess = $this->canAccessLearning($enrolment, $user);

        $state = (object) [
            'can_access' => $canAccess,
            'is_verified' => $user->isVerified(),
            'requirements_met' => $user->areRequirementsMet(),
            'block_reason' => $accessStatus->reason,
            'grace_active' => $accessStatus->grace_active ?? false,
            'grace_until' => $accessStatus->grace_until ?? null,
            'due_info' => $accessStatus->due_info ?? null,
            'is_denied' => ($enrolment->status->status === 'denied'),
        ];

        // Add learner-facing status
        $status = $this->getLearnerStatus($enrolment, $state);
        $state->status_label = $status['label'];
        $state->status_variant = $status['variant'];

        return $state;
    }

    /**
     * Centralized logic for learner-facing status badges.
     */
    public function getLearnerStatus(Enrolment $enrolment, object $state): array
    {
        $internalStatus = strtolower($enrolment->status->status ?? '');
        $paymentStatus = $enrolment->payment_status_details['status'] ?? '';
        $isPaid = $paymentStatus === 'paid';
        $isInstallmentsActive = $paymentStatus === 'installments_active';
        
        // 1. Requirements
        if (!$state->requirements_met) {
            return ['label' => 'Requirements Pending', 'variant' => 'brand'];
        }

        // 2. Verification / Admissions Review
        if (!$state->is_verified) {
            return ['label' => 'Under Review', 'variant' => 'neutral'];
        }

        // 3. Denied
        if ($state->is_denied) {
            return ['label' => 'Denied', 'variant' => 'error'];
        }

        // 4. Grace Period (High Priority: User still has access despite pending payment)
        if ($state->grace_active) {
            return ['label' => 'GRACE PERIOD ACTIVE', 'variant' => 'warning'];
        }

        // 5. Payment Blocking
        if ($state->block_reason) {
            return ['label' => 'PAYMENT OVERDUE', 'variant' => 'error'];
        }

        // 6. Active / Paid (Access allowed and payment is healthy)
        $isActiveStatus = in_array($internalStatus, ['active', 'paid', 'approved', 'installments_active']);
        if ($isPaid || $isInstallmentsActive || $isActiveStatus) {
            return ['label' => 'Active', 'variant' => 'success'];
        }

        // 7. Payment Pending (Genuine payment issue that might not be blocking yet but is a primary state)
        if ($paymentStatus === 'pending_payment') {
            return ['label' => 'Payment Pending', 'variant' => 'warning'];
        }

        // 8. Fallback to General Pending
        return ['label' => 'Pending', 'variant' => 'neutral'];
    }

    private function isEffectivelyActive(Enrolment $enrolment): bool
    {
        $statusStr = strtolower($enrolment->status->status ?? '');
        $allowedStatuses = ['active', 'paid', 'approved', 'installments_active'];
        
        if (in_array($statusStr, $allowedStatuses)) {
            return true;
        }

        // Also check if they have a paid order or active installments (deposit paid)
        $paymentDetails = $enrolment->payment_status_details;
        return in_array($paymentDetails['status'], ['paid', 'installments_active']);
    }
}
