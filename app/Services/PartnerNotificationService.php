<?php

namespace App\Services;

use App\Models\Partner\PartnerNotification;
use App\Models\Partner\PartnerLearnerInstallment;
use App\Models\Crm\Enrolment;
use App\Models\User;

class PartnerNotificationService
{
    /**
     * Sync all dynamic notifications for a partner.
     * Deletes stale auto-notifications and regenerates from current state.
     * Called on dashboard load to keep notifications fresh.
     */
    public function syncForPartner(int $partnerId): void
    {
        $learnerIds = User::myLearners($partnerId)->pluck('id');
        $autoTypes = $this->autoTypes();

        // Track which IDs we've processed so we can delete stale ones later
        $validNotificationIds = [];

        // --- 1. Overdue installments ---
        $overdue = PartnerLearnerInstallment::where('partner_id', $partnerId)
            ->whereNotIn('status', ['paid'])
            ->whereDate('due_date', '<', now()->startOfDay())
            ->with('learner')
            ->get();

        foreach ($overdue as $inst) {
            $notification = $this->ensure($partnerId, [
                'type'          => 'installment_overdue',
                'related_id'    => $inst->id,
                'related_type'  => 'installment',
                'title'         => 'Installment Overdue',
                'body'          => $this->learnerName($inst->learner) . " — Installment #{$inst->installment_no} of £" . number_format($inst->installment_amount, 2) . " was due on " . optional($inst->due_date)->format('d M Y') . ".",
                'icon'          => 'warning',
                'color'         => 'red',
                'action_url'    => route('partner.installments.index', ['tab' => 'overdue']),
                'action_label'  => 'View Overdue',
            ]);
            $validNotificationIds[] = $notification->id;
        }

        // --- 2. Due-soon installments ---
        $dueSoon = PartnerLearnerInstallment::where('partner_id', $partnerId)
            ->whereNotIn('status', ['paid'])
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->with('learner')
            ->get();

        foreach ($dueSoon as $inst) {
            $notification = $this->ensure($partnerId, [
                'type'          => 'installment_due_soon',
                'related_id'    => $inst->id,
                'related_type'  => 'installment',
                'title'         => 'Installment Due Soon',
                'body'          => $this->learnerName($inst->learner) . " — Installment #{$inst->installment_no} of £" . number_format($inst->installment_amount, 2) . " is due " . optional($inst->due_date)->format('d M Y') . ".",
                'icon'          => 'clock',
                'color'         => 'amber',
                'action_url'    => route('partner.installments.index', ['tab' => 'due_soon']),
                'action_label'  => 'Review',
            ]);
            $validNotificationIds[] = $notification->id;
        }

        // --- 3. Enrolments needing plan selection ---
        $pendingPlan = Enrolment::whereIn('learner_id', $learnerIds)
            ->whereIn('status_id', [1, 5])
            ->whereDoesntHave('orders')
            ->whereDoesntHave('partnerInstallments')
            ->with('learner', 'course')
            ->get();

        foreach ($pendingPlan as $enrolment) {
            $notification = $this->ensure($partnerId, [
                'type'          => 'plan_required',
                'related_id'    => $enrolment->id,
                'related_type'  => 'enrolment',
                'title'         => 'Payment Plan Required',
                'body'          => $this->learnerName($enrolment->learner) . " — \"" . ($enrolment->course->title ?? 'Course') . "\" needs a payment plan.",
                'icon'          => 'diploma',
                'color'         => 'pink',
                'action_url'    => route('partner.enrolments.choose_plan', $enrolment->id),
                'action_label'  => 'Select Plan',
            ]);
            $validNotificationIds[] = $notification->id;
        }

        // --- 4. Learners pending CRM approval ---
        $pendingApprovals = User::myLearners($partnerId)
            ->where('crm_approved', 0)
            ->get();

        foreach ($pendingApprovals as $learner) {
            $notification = $this->ensure($partnerId, [
                'type'          => 'learner_pending_approval',
                'related_id'    => $learner->id,
                'related_type'  => 'learner',
                'title'         => 'Learner Awaiting Approval',
                'body'          => $this->learnerName($learner) . "'s account is pending CRM review.",
                'icon'          => 'user',
                'color'         => 'blue',
                'action_url'    => route('partner.learners.show', $learner->id),
                'action_label'  => 'View Learner',
            ]);
            $validNotificationIds[] = $notification->id;
        }

        // --- CLEANUP ---
        // Delete any auto-generated notifications for this partner that weren't refreshed above
        PartnerNotification::where('partner_id', $partnerId)
            ->whereIn('type', $autoTypes)
            ->whereNotIn('id', $validNotificationIds)
            ->delete();
    }

    /**
     * Push a one-off notification (e.g. payment received).
     * Does not get swept on next sync.
     */
    public function notify(int $partnerId, array $data): PartnerNotification
    {
        return $this->create($partnerId, $data);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function create(int $partnerId, array $data): PartnerNotification
    {
        return PartnerNotification::create(array_merge(['partner_id' => $partnerId], $data));
    }

    /**
     * Ensure a notification exists. If it does, return it. If not, create it.
     */
    private function ensure(int $partnerId, array $data): PartnerNotification
    {
        $existing = PartnerNotification::where('partner_id', $partnerId)
            ->where('type', $data['type'])
            ->where('related_id', $data['related_id'] ?? null)
            ->where('related_type', $data['related_type'] ?? null)
            ->first();

        if ($existing) {
            // Update fields that might have changed (body, action_url, etc.) 
            // but Laravel will preserve created_at automatically.
            $existing->update([
                'title'        => $data['title'],
                'body'         => $data['body'],
                'icon'         => $data['icon'],
                'color'        => $data['color'],
                'action_url'   => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
            ]);
            return $existing;
        }

        return $this->create($partnerId, $data);
    }

    private function autoTypes(): array
    {
        return [
            'installment_overdue',
            'installment_due_soon',
            'plan_required',
            'learner_pending_approval',
        ];
    }

    private function learnerName(?\App\Models\User $user): string
    {
        if (!$user) return 'Learner';
        return trim($user->first_name . ' ' . $user->sur_name);
    }
}
