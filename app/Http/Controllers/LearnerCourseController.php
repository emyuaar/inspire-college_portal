<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Crm\Enrolment;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Assignment;
use App\Models\AssignmentFile;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use App\Models\Crm\GradeSheetCell; // Added CRM Grade Model
use App\Services\SharePointService;

class LearnerCourseController extends Controller
{
    /**
     * Strict check for enrolment status (User Requirements + CRM Approval).
     * Payment is NOT a blocker for access (Content Gate).
     */
    private function checkAccess($enrolment)
    {
        // 1. Requirements Check
        $user = Auth::user();

        // STRICT GATE: Use the consolidated verification check
        if (!$user->isVerified()) {
            return false;
        }

        // 2. Enrolment Status Hard Block & Payment Gate
        // Policy A (Strict): Must be Active/Paid/Approved. Unpaid logic returns false.

        $allowedStatuses = ['active', 'paid', 'approved'];
        $statusStr = strtolower($enrolment->status->status ?? '');

        // Block if Denied/Archived
        if ($statusStr === 'denied' || $statusStr === 'archived') {
            return false;
        }

        // Strict Payment/Status Check
        // If it's NOT in allowed statuses, check if Order is Paid (ID 1)
        if (!in_array($statusStr, $allowedStatuses)) {
            $enrolment->load('latestOrder');
            if (!$enrolment->latestOrder || (int) $enrolment->latestOrder->status_id !== 1) {
                return false;
            }
        }

        return true;
    }

    public function show(Enrolment $enrolment)
    {
        $user = Auth::user();

        if ($enrolment->learner_id != $user->id) {
            abort(403, 'You are not allowed to view this course.');
        }

        // Eager load status to allow string check
        if (!$enrolment->relationLoaded('status')) {
            $enrolment->load('status');
        }

        if (!$this->checkAccess($enrolment)) {

            // Optional: better messages
            if (($enrolment->status->status ?? '') === 'denied') {
                return redirect()
                    ->route('portal.learner.dashboard')
                    ->with('error', 'Your enrolment was denied.');
            }

            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is awaiting approval or payment.');
        }

        $enrolment->load('course');
        $course = $enrolment->course;

        $modules = CourseModule::with([
            'lessons' => function ($q) {
                $q->where('is_published', true)
                    ->orderBy('sort_order');
            },
            'assignments.files',
            'assignments.submissions' => function ($q) use ($user) {
                $q->where('learner_id', $user->id)
                    ->with('files')
                    ->latest();
            },
            'assignments.gradeResets' => function ($q) use ($user) {
                $q->where('learner_id', $user->id)
                    ->latest('reset_at');
            },
        ])
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->get();

        // ------------------------------------------------------------------
        // NEW: Fetch CRM Grading Data (Cross-DB)
        // ------------------------------------------------------------------
        // Collect all assignment IDs
        $assignmentIds = $modules->pluck('assignments')->flatten()->pluck('id')->filter();

        if ($assignmentIds->isNotEmpty()) {
            // Fetch Cells (Grade + Feedback)
            $gradeCells = GradeSheetCell::with(['latestAttempt.assessor', 'latestAttempt.attachments'])
                ->whereIn('portal_assignment_id', $assignmentIds)
                ->whereHas('row', function ($q) use ($user) {
                    $q->where('learner_id', $user->id);
                })
                ->get()
                ->keyBy('portal_assignment_id');

            // 1. Attach Grades to Assignments
            $allAssignments = collect();
            foreach ($modules as $module) {
                foreach ($module->assignments as $assignment) {

                    if ($gradeCells->has($assignment->id)) {
                        $cell = $gradeCells->get($assignment->id);
                        $assignment->setRelation('grade_cell', $cell);
                        if ($cell->latestAttempt) {
                            $assignment->setRelation('latest_grade', $cell->latestAttempt);
                        }
                    }
                }
            }
        }
        // ------------------------------------------------------------------

        return view('learner.courses.show', [
            'user' => $user,
            'enrolment' => $enrolment,
            'course' => $course,
            'modules' => $modules,
        ]);
    }

    public function submitAssignment(Request $request, Assignment $assignment, SharePointService $sp)
    {
        $user = Auth::user();

        // enrolment check (CRM DB)
        $enrolment = Enrolment::with(['course', 'status'])
            ->where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            return back()->with('error', 'Your enrolment is not active or payment is pending.');
        }

        $request->validate([
            'submission_files' => 'required_without:submission_file|array|min:1',
            'submission_files.*' => 'file|max:20480',
            'submission_file' => 'required_without:submission_files|file|max:20480',
        ]);

        $files = $request->file('submission_files') ?: [$request->file('submission_file')];

        // load relations (must exist on Assignment model)
        $assignment->load(['module', 'course']); // if assignment->course relation exists

        $courseTitle = $assignment->course?->title ?? ($enrolment->course?->title ?? 'Course');
        $moduleTitle = $assignment->module?->title ?? 'Module';

        // folder names
        $courseFolder = $sp->safeName($courseTitle);                          // Course_Name
        $learnerFolder = 'ICOL_ID_' . str_pad($user->id, 5, '0', STR_PAD_LEFT);   // ICOL_ID_00001
        $modFolder = $sp->safeName($moduleTitle);                          // Module_1
        $assFolder = $sp->safeName($assignment->title);                    // Assignment_1

        // create folder structure
        $path1 = $sp->ensureFolder('', $courseFolder);
        $path2 = $sp->ensureFolder($path1, $learnerFolder);
        $path3 = $sp->ensureFolder($path2, $modFolder);
        $path4 = $sp->ensureFolder($path3, $assFolder);

        // Calculate Attempt No
        $attemptNo = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('learner_id', $user->id)
            ->max('attempt_no') ?? 0;

        if ($attemptNo == 0) {
            $count = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->count();
            $attemptNo = $count;
        }

        // --- ENFORCE RESUBMISSION RULES ---
        if ($attemptNo >= 2) {
            return back()->with('error', 'Maximum attempts reached for this assignment.');
        }

        if ($attemptNo > 0) {
            // This is a re-submission. Check if authorized.
            // We need a GradeReset that is newer than the last submission.
            $lastSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->latest()
                ->first();

            $hasReset = \App\Models\Crm\GradeReset::where('portal_assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->where('reset_at', '>', $lastSubmission->created_at)
                ->exists();

            if (!$hasReset) {
                // Determine if the last grade was actually "Pass" (which shouldn't happen here usually)
                // But if it was "Refer", we strictly need a reset.
                return back()->with('error', 'Re-submission is not yet approved by the assessor.');
            }
        }
        // ----------------------------------

        $uploadedFiles = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $ext = strtolower($file->getClientOriginalExtension());
            $fileName = $sp->safeName($baseName) . '_' . time() . '_' . \Illuminate\Support\Str::random(4) . '.' . $ext;
            $uploaded = $sp->uploadFile($path4, $fileName, $file->getRealPath());

            $uploadedFiles[] = [
                'original_name' => $originalName,
                'stored_name' => $fileName,
                'path' => $courseFolder . '/' . $learnerFolder . '/' . $modFolder . '/' . $assFolder . '/' . $fileName,
                'item_id' => $uploaded['id'] ?? null,
                'url' => $uploaded['webUrl'] ?? null,
                'drive_id' => $uploaded['parentReference']['driveId'] ?? $sp->driveId(),
                'size' => $file->getSize(),
                'mime' => $file->getClientMimeType(),
            ];
        }

        $primaryFile = $uploadedFiles[0];

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'learner_id' => $user->id,
            'file_name' => $primaryFile['original_name'],
            'stored_file_name' => $primaryFile['stored_name'],
            'status_id' => 1,  // Status ID for 'submitted'
            'attempt_no' => $attemptNo + 1,

            'sharepoint_item_id' => $primaryFile['item_id'],
            'sharepoint_path' => $primaryFile['path'],
            'sharepoint_url' => $primaryFile['url'],
            'drive_id' => $primaryFile['drive_id'],
            'file_size' => $primaryFile['size'],
            'mime_type' => $primaryFile['mime'],
        ]);

        foreach ($uploadedFiles as $uploadedFile) {
            AssignmentSubmissionFile::create([
                'assignment_submission_id' => $submission->id,
                'file_name' => $uploadedFile['original_name'],
                'stored_file_name' => $uploadedFile['stored_name'],
                'sharepoint_item_id' => $uploadedFile['item_id'],
                'sharepoint_path' => $uploadedFile['path'],
                'sharepoint_url' => $uploadedFile['url'],
                'drive_id' => $uploadedFile['drive_id'],
                'file_size' => $uploadedFile['size'],
                'mime_type' => $uploadedFile['mime'],
            ]);
        }

        return back()->with('success', count($uploadedFiles) . ' assignment file(s) submitted successfully.');
    }

    public function viewSubmission(AssignmentSubmission $submission, SharePointService $sp)
    {
        $user = Auth::user();

        if ((int) $submission->learner_id !== (int) $user->id) {
            abort(403);
        }

        if (!$submission->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        // inline if route is "inline" OR query inline=1
        $inline = request()->routeIs('portal.learner.submission.inline') || request()->boolean('inline', false);

        return $sp->streamByItemId(
            $submission->sharepoint_item_id,
            $submission->file_name ?? 'submission',
            $inline,
            $submission->drive_id
        );
    }

    public function viewSubmissionFile(AssignmentSubmissionFile $file, SharePointService $sp)
    {
        $user = Auth::user();
        $submission = $file->submission;

        if (!$submission || (int) $submission->learner_id !== (int) $user->id) {
            abort(403);
        }

        if (!$file->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        $assignment = $submission->assignment;
        if ($assignment) {
            $enrolment = Enrolment::where('learner_id', $user->id)
                ->where('course_id', $assignment->course_id)
                ->firstOrFail();

            if (!$this->checkAccess($enrolment)) {
                abort(403);
            }
        }

        return $sp->streamByItemId(
            $file->sharepoint_item_id,
            $file->file_name ?? 'submission_file',
            true,
            $file->drive_id
        );
    }

    public function submissionDirectLink(AssignmentSubmission $submission, SharePointService $sp)
    {
        $user = Auth::user();

        if ((int) $submission->learner_id !== (int) $user->id) {
            abort(403);
        }

        if (!$submission->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        return $sp->streamByItemId(
            $submission->sharepoint_item_id,
            $submission->file_name ?? 'submission',
            false,
            $submission->drive_id
        );
    }

    public function viewLesson(Lesson $lesson)
    {
        $user = Auth::user();

        // module + course id
        $module = CourseModule::findOrFail($lesson->module_id);

        // CRM enrolment check (approved only)
        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is not active or payment is pending.');
        }

        // published only
        if (!(int) $lesson->is_published) {
            abort(404);
        }

        if ($lesson->resource_type === 'secure_document') {
            return redirect()->route('portal.learner.secure_doc.view', $lesson->id);
        }

        // optional: previous/next lesson (same module)
        $prev = Lesson::where('module_id', $lesson->module_id)
            ->where('is_published', 1)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        $next = Lesson::where('module_id', $lesson->module_id)
            ->where('is_published', 1)
            ->where('sort_order', '>', $lesson->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        return view('learner.lessons.show', [
            'lesson' => $lesson,
            'module' => $module,
            'enrolment' => $enrolment,
            'prev' => $prev,
            'next' => $next,
        ]);
    }

    public function downloadLessonFile(Lesson $lesson, SharePointService $sp)
    {
        $user = Auth::user();

        // learner enrolment check for this lesson's course (approved only)
        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403);
        }

        if ($lesson->resource_type === 'secure_document') {
            abort(403, 'Direct download is disabled for secure documents.');
        }

        if (!blank($lesson->sharepoint_item_id)) {
            $inline = request()->routeIs('portal.learner.lesson.file.inline');
            $name = $lesson->file_name ?? $lesson->title . '.pdf';

            return $sp->streamByItemId($lesson->sharepoint_item_id, $name, $inline, $lesson->drive_id);
        }

        if (!blank($lesson->file_path) && filter_var($lesson->file_path, FILTER_VALIDATE_URL)) {
            try {
                $inline = request()->routeIs('portal.learner.lesson.file.inline');
                return $sp->streamByShareUrl($lesson->file_path, $lesson->file_name ?: ($lesson->title . '.pdf'), $inline);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Lesson file SharePoint URL fallback failed', [
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!blank($lesson->file_path)) {
            $fullPath = $this->resolveLessonFilePath($lesson->file_path);

            if ($fullPath) {
                $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                $downloadName = $lesson->title . ($extension ? '.' . $extension : '');

                return response()->download($fullPath, $downloadName);
            }
        }

        return back()->with('error', 'Lesson file not available.');
    }

    private function assignmentBriefDownloadName(AssignmentFile $brief, string $fallback = 'brief', ?string $extraSource = null): string
    {
        $rawName = trim((string) ($brief->file_name ?: ''));
        $name = $rawName !== '' ? $rawName : $this->basenameFromFilenameLike($extraSource ?: $brief->file_path);
        $name = $name ?: $fallback;
        $name = $this->safeDownloadFilename($name);

        if ($this->extensionFromFilenameLike($name)) {
            return $name;
        }

        $extension = $this->extensionFromFilenameLike($brief->file_path)
            ?: $this->extensionFromFilenameLike($brief->sharepoint_path ?? null)
            ?: $this->extensionFromFilenameLike($brief->sharepoint_url ?? null)
            ?: $this->extensionFromFilenameLike($extraSource)
            ?: $this->extensionFromFilenameLike($fallback);

        return $extension ? $name . '.' . $extension : $name;
    }

    private function basenameFromFilenameLike(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $path = parse_url((string) $value, PHP_URL_PATH) ?: (string) $value;
        $path = rawurldecode($path);
        $base = basename(str_replace('\\', '/', $path));

        return $base !== '' && $base !== '.' ? $base : null;
    }

    private function extensionFromFilenameLike(?string $value): ?string
    {
        $base = $this->basenameFromFilenameLike($value);
        if (!$base) {
            return null;
        }

        $extension = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));

        return preg_match('/^[a-z0-9]{1,10}$/', $extension) ? $extension : null;
    }

    private function safeDownloadFilename(string $name): string
    {
        $name = rawurldecode($name);
        $name = preg_replace('~[\\/:*?"<>|]+~', ' ', $name) ?: 'brief';
        $name = preg_replace('/\s+/', ' ', $name) ?: 'brief';

        return trim($name) ?: 'brief';
    }

    public function downloadAssignmentBrief(AssignmentFile $brief, SharePointService $sp)
    {
        $user = Auth::user();

        $assignment = $brief->assignment;
        if (!$assignment)
            abort(404);

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403);
        }

        $inline = request()->routeIs('portal.learner.assignment.brief.inline');
        $downloadName = $this->assignmentBriefDownloadName($brief);

        if (!blank($brief->sharepoint_item_id)) {
            return $sp->streamByItemId($brief->sharepoint_item_id, $downloadName, $inline, $brief->drive_id);
        }

        if (!blank($brief->file_path) && !filter_var($brief->file_path, FILTER_VALIDATE_URL)) {
            return $this->downloadAssignmentBriefLocal($brief);
        }

        if (!blank($brief->file_path) && filter_var($brief->file_path, FILTER_VALIDATE_URL)) {
            try {
                return $sp->streamByShareUrl($brief->file_path, $downloadName, $inline);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Assignment brief SharePoint URL fallback failed', [
                    'brief_id' => $brief->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('error', 'Brief not available on SharePoint.');
    }

    public function downloadAssignmentBriefLocal(AssignmentFile $brief)
    {
        $user = Auth::user();

        $assignment = $brief->assignment; // ensure relation exists
        if (!$assignment) {
            abort(404);
        }

        // Approved enrolment check
        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403);
        }

        $crmRoot = config('services.crm.storage_root');
        if (!$crmRoot) {
            abort(500, 'CRM storage path not configured.');
        }

        // DB me usually "assignments/briefs/xxx.docx" hota hai
        $relative = ltrim((string) $brief->file_path, '/\\');

        // Safe join + normalize (path traversal protection)
        $crmRootReal = realpath($crmRoot);
        if (!$crmRootReal) {
            abort(500, 'CRM storage path invalid.');
        }

        $fullPath = $crmRootReal . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        $fullReal = realpath($fullPath);
        if (!$fullReal || !is_file($fullReal)) {
            abort(404, 'Brief file not found.');
        }

        // extra safety: ensure file is inside crm storage root
        if (strpos($fullReal, $crmRootReal) !== 0) {
            abort(403);
        }

        $downloadName = $this->assignmentBriefDownloadName($brief, basename($fullReal), $fullReal);

        return response()->download($fullReal, $downloadName);
    }

    public function downloadGradingFile(Request $request, Assignment $assignment, SharePointService $sp)
    {
        $user = Auth::user();
        $type = $request->query('type'); // 'marking_sheet' or 'feedback_file'

        // 1. Enrolment Check
        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403);
        }

        // 2. Fetch Grade Attempt (CRM)
        // Using CRM GradeSheetCell model
        $gradeCell = GradeSheetCell::where('portal_assignment_id', $assignment->id)
            ->whereHas('row', function ($q) use ($user) {
                $q->where('learner_id', $user->id);
            })
            ->first();

        if (!$gradeCell) {
            abort(404, 'No grading record found.');
        }

        $attempt = $gradeCell->latestAttempt;
        if (!$attempt) {
            abort(404, 'No attempt found.');
        }

        $attachment = $attempt->attachments->where('type', $type)->first();
        $path = $attachment?->file_path;

        if (blank($path)) {
            return back()->with('error', 'File not available.');
        }

        if (!blank($attachment->sharepoint_item_id)) {
            return $sp->streamByItemId(
                $attachment->sharepoint_item_id,
                $attachment->original_name ?: ($type . '_file'),
                false,
                $attachment->drive_id
            );
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            try {
                return $sp->streamByShareUrl($path, $attachment->original_name ?: ($type . '_file'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Grading attachment SharePoint URL fallback failed', [
                    'attachment_id' => $attachment?->id,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'Secure SharePoint metadata is missing for this grading file.');
            }
        }

        // Local Storage via CRM Root
        $crmRoot = config('services.crm.storage_root');
        if (!$crmRoot) {
            // Try absolute check if no config
            if (file_exists($path)) {
                return response()->download($path);
            }
            abort(500, 'CRM storage path not configured.');
        }

        // Sanitize path (remove crm_storage if present in DB path to avoid duplication if DB has absolute or relative logic)
        // Usually DB path is relative e.g. "learners/123/sheet.pdf"
        $crmRootReal = realpath($crmRoot);
        if (!$crmRootReal) {
            abort(500, 'CRM storage path invalid.');
        }

        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '\\/');
        $fullPath = $crmRootReal . DIRECTORY_SEPARATOR . $relative;

        if (!file_exists($fullPath)) {
            abort(404, 'File not found on server.');
        }

        // Security check
        $realFullPath = realpath($fullPath);
        if (!$realFullPath || strpos($realFullPath, $crmRootReal) !== 0) {
            abort(403);
        }

        return response()->download($realFullPath);
    }

    public function viewSecureDocument(Lesson $lesson)
    {
        $user = Auth::user();

        if (!(int) $lesson->is_published || $lesson->resource_type !== 'secure_document') {
            abort(404, 'Secure document not found.');
        }

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is not active or payment is pending.');
        }

        return view('learner.lessons.secure_viewer', [
            'lesson' => $lesson,
            'enrolment' => $enrolment,
            'user' => $user,
        ]);
    }

    public function streamSecureDocument(Lesson $lesson, SharePointService $sp)
    {
        $user = Auth::user();

        if (!(int) $lesson->is_published || $lesson->resource_type !== 'secure_document') {
            abort(404, 'Secure document not found.');
        }

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403, 'Unauthorized access.');
        }

        if (!blank($lesson->sharepoint_item_id)) {
            return $sp->streamByItemId(
                $lesson->sharepoint_item_id,
                $lesson->file_name ?: ($lesson->title . '.pdf'),
                true,
                $lesson->drive_id
            );
        }

        $fullPath = $this->resolveLessonFilePath($lesson->file_path);
        if (!$fullPath) {
            abort(404, 'File not found.');
        }

        $binary = file_get_contents($fullPath);
        $mime = mime_content_type($fullPath) ?: 'application/pdf';

        return response($binary, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Length', strlen($binary))
            ->header('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function securePdfData(Lesson $lesson, SharePointService $sp)
    {
        $user = Auth::user();

        if (!(int) $lesson->is_published || $lesson->resource_type !== 'secure_document') {
            abort(404, 'Secure document not found.');
        }

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403, 'Unauthorized access.');
        }

        $driveId = $lesson->drive_id ?: config('services.sharepoint.drive_id');
        $itemId = blank($lesson->sharepoint_item_id) ? null : $lesson->sharepoint_item_id;
        $path = blank($lesson->sharepoint_path) ? ($lesson->file_path ?: null) : $lesson->sharepoint_path;

        if (app()->environment('local')) {
            \Log::info('Secure PDF viewer request', [
                'lesson_id' => $lesson->id,
                'secure_pdf_id' => $lesson->id,
                'drive_id' => $driveId,
                'sharepoint_item_id' => $itemId,
                'sharepoint_path' => $path,
            ]);
        }

        try {
            $binary = $sp->downloadFileContent($driveId, $itemId, $path);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'Secure PDF SharePoint metadata is missing') || str_contains($message, 'SharePoint drive ID is missing')) {
                abort(404, 'Secure PDF SharePoint metadata is missing.');
            }

            abort(404, 'File not found.');
        }

        if (strlen($binary) > 50 * 1024 * 1024) {
            abort(413, 'PDF too large for inline secure viewer.');
        }

        return response()->json([
            'success' => true,
            'filename' => $lesson->file_name ?: basename((string) ($lesson->sharepoint_path ?: $lesson->file_path)),
            'mime' => $lesson->mime_type ?: 'application/pdf',
            'data' => base64_encode($binary),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function resolveLessonFilePath(?string $filePath): ?string
    {
        if (blank($filePath) || filter_var($filePath, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);
            $realPath = realpath($path);

            if ($realPath && is_file($realPath)) {
                return $realPath;
            }
        }

        $crmRoot = config('services.crm.storage_root');
        if ($crmRoot) {
            $crmRootReal = realpath($crmRoot);

            if ($crmRootReal) {
                $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), '\\/');
                $candidates = [
                    'public' . DIRECTORY_SEPARATOR . $relative,
                    $relative,
                ];

                foreach ($candidates as $candidate) {
                    $fullPath = $crmRootReal . DIRECTORY_SEPARATOR . $candidate;
                    $fullReal = realpath($fullPath);

                    $rootPrefix = rtrim($crmRootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    if ($fullReal && is_file($fullReal) && str_starts_with($fullReal, $rootPrefix)) {
                        return $fullReal;
                    }
                }
            }
        }

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);
            $realPath = realpath($path);

            if ($realPath && is_file($realPath)) {
                return $realPath;
            }
        }

        return null;
    }
}
