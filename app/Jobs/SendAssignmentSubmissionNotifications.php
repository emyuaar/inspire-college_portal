<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Exception;

class SendAssignmentSubmissionNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $submissionId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $submission = AssignmentSubmission::with(['learner', 'assignment.course'])->find($this->submissionId);

            if (!$submission || !$submission->learner) {
                Log::warning('Assignment submission or learner not found in job', [
                    'submission_id' => $this->submissionId,
                    'found' => (bool)$submission,
                    'learner_found' => $submission ? (bool)$submission->learner : false,
                ]);
                return;
            }

            $learner = $submission->learner;
            $learnerName = trim($learner->name ?? 'Learner');
            $learnerEmail = $learner->email ?? null;
            $courseName = $submission->assignment->course->title ?? 'Course';
            $assignmentName = $submission->assignment->title ?? 'Assignment';
            $assignmentId = $submission->assignment_id;
            $fileName = $submission->file_name ?? null;
            $submittedAt = $submission->created_at ? $submission->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A');
            $attemptNumber = $submission->attempt_no ?? 1;

            $crmBaseUrl = rtrim(env('CRM_BASE_URL', config('app.url')), '/');
            $submissionUrl = $crmBaseUrl . '/admin/lms/assignments/' . $assignmentId . '/submissions' . ($learnerEmail ? '?search=' . urlencode($learnerEmail) : '');

            $emailData = [
                'learnerName' => $learnerName,
                'learnerEmail' => $learnerEmail,
                'courseName' => $courseName,
                'assignmentName' => $assignmentName,
                'assignmentId' => $assignmentId,
                'fileName' => $fileName,
                'submittedAt' => $submittedAt,
                'attemptNumber' => $attemptNumber,
                'submissionUrl' => $submissionUrl,
                'submission' => $submission,
            ];

            $graphService = new \App\Services\MicrosoftGraphService();

            if ($learnerEmail && empty($submission->learner_email_sent_at)) {
                $learnerSubject = 'Assignment Submitted Successfully – ' . $assignmentName;
                $learnerHtml = view('emails.assignments.learner-submitted', $emailData)->render();
                
                $graphService->sendMail($learnerSubject, $learnerHtml, $learnerEmail);
                
                if (\Illuminate\Support\Facades\Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'learner_email_sent_at')) {
                    $submission->learner_email_sent_at = now();
                    $submission->save();
                }
            } elseif (!$learnerEmail) {
                Log::error('Learner email is empty, skipping learner confirmation email', [
                    'submission_id' => $submission->id,
                    'learner_id' => $learner->id ?? null,
                ]);
            }

            // 2. Internal Emails
            $courseId = $submission->assignment->course_id ?? null;
            
            // Querying CRM database for staff
            $assessmentManagers = \Illuminate\Support\Facades\DB::connection('mysql_crm')
                ->table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where(function ($q) {
                    $q->where('roles.name', 'Assessment Manager')
                      ->orWhere('roles.slug', 'assessment-manager');
                })
                ->pluck('users.email')
                ->toArray();
            
            $assessmentCheckers = [];
            if ($courseId) {
                $assessmentCheckers = \Illuminate\Support\Facades\DB::connection('mysql_crm')
                    ->table('users')
                    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->join('assessor_course_assignments', 'users.id', '=', 'assessor_course_assignments.assessor_id')
                    ->where(function ($q) {
                        $q->where('roles.name', 'Assessment Checker')
                          ->orWhere('roles.slug', 'assessment-checker');
                    })
                    ->where('assessor_course_assignments.course_id', $courseId)
                    ->pluck('users.email')
                    ->toArray();
            }

            $supervisorEmail = config('mail.supervisor_email', 'supervisor@inspirecollegeoflearning.com');
            $toEmails = [$supervisorEmail];
            $ccEmails = array_unique(array_filter($assessmentManagers));
            $bccEmails = array_unique(array_filter($assessmentCheckers));

            if (empty($submission->internal_email_sent_at)) {
                $internalSubject = 'New Assignment Submission – ' . trim($learnerName) . ' – ' . $courseName;
                $internalHtml = view('emails.assignments.internal-submitted', $emailData)->render();

                $graphService->sendMail($internalSubject, $internalHtml, $toEmails, $ccEmails, $bccEmails);

                if (\Illuminate\Support\Facades\Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'internal_email_sent_at')) {
                    $submission->internal_email_sent_at = now();
                    $submission->save();
                }
            }

        } catch (\Throwable $e) {
            Log::error('Assignment submission email job failed', [
                'submission_id' => $this->submissionId ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}
