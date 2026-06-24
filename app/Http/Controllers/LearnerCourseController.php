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
use App\Models\Crm\GradeSheetCell; // Added CRM Grade Model
use App\Services\SharePointService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\LearnerLogger;
use App\Models\LearnerCourseUnitSelection;
use App\Services\Lms\UnitSelectionService;
use App\Services\Lms\CreditCompletionService;

class LearnerCourseController extends Controller
{
    protected $accessService;

    public function __construct(SharePointService $sp, \App\Services\EnrolmentAccessService $accessService)
    {
        $this->accessService = $accessService;
    }

    /**
     * Strict check for enrolment status (User Requirements + Admissions Approval + Payment).
     */
    private function checkAccess($enrolment)
    {
        return $this->accessService->canAccessLearning($enrolment, Auth::user());
    }

    public function show(Enrolment $enrolment, UnitSelectionService $selectionService, CreditCompletionService $completionService)
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
            LearnerLogger::log('learner.course.access_denied', 'activity')
                ->status('blocked')
                ->severity('warning')
                ->humanMessage('Course access blocked: Enrolment not active.')
                ->save();

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

        LearnerLogger::log('learner.course.viewed', 'activity')
            ->humanMessage('Learner viewed course: ' . ($enrolment->course?->title ?? 'Untitled'))
            ->save();

        $enrolment->load('course');
        $course = $enrolment->course;
        $creditBased = $course->usesCreditBasedCompletion();
        if ($creditBased) {
            $selectionService->ensureMandatorySelections($user->id, $course);
            $selectionService->refreshLocks($user->id, $course->id);
        }

        $hasExtraAttemptTable = Schema::connection('mysql_crm')->hasTable('grade_extra_attempts');

        $with = [
            'lessons' => function ($q) {
                $q->where('is_published', true)
                    ->orderBy('sort_order');
            },
            'assignments.files',
            'assignments.submissions' => function ($q) use ($user) {
                $q->where('learner_id', $user->id)
                    ->latest();
            },
            'assignments.gradeResets' => function ($q) use ($user) {
                $q->where('learner_id', $user->id)
                    ->latest('reset_at');
            },
        ];

        if ($hasExtraAttemptTable) {
            $with['assignments.extraAttemptGrants'] = function ($q) use ($user) {
                $q->where('learner_id', $user->id)
                    ->latest('granted_at');
            };
        }

        $with[] = 'optionalGroup';
        $modules = CourseModule::with($with)
            ->where('course_id', $course->id)
            ->when($creditBased, fn ($query) => $query->where('status', 'active'))
            ->orderBy('sort_order')
            ->get();
        if ($creditBased) {
            $modules = $modules->sortBy(fn (CourseModule $module) => [
                $module->isUnit() ? 1 : 0,
                $module->sort_order,
                $module->id,
            ])->values();
        }

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

        $submissionStatusNameById = [];
        if (Schema::connection('mysql_portal')->hasTable('assignment_submission_statuses')) {
            $submissionStatusNameById = DB::connection('mysql_portal')
                ->table('assignment_submission_statuses')
                ->pluck('name', 'id')
                ->toArray();
        }
        
        $unitSelections = collect();
        $creditSummary = null;
        $completionReport = null;
        if ($creditBased) {
            $unitSelections = LearnerCourseUnitSelection::where('learner_id', $user->id)
                ->where('course_id', $course->id)->get()->keyBy('module_id');
            $creditSummary = $selectionService->summary($course, $unitSelections);
            $completionReport = $completionService->evaluate($user->id, $course);
        }

        return view('learner.courses.show', [
            'user' => $user,
            'enrolment' => $enrolment,
            'course' => $course,
            'modules' => $modules,
            'hasExtraAttemptTable' => $hasExtraAttemptTable,
            'submissionStatusNameById' => $submissionStatusNameById,
            'creditBased' => $creditBased,
            'unitSelections' => $unitSelections,
            'creditSummary' => $creditSummary,
            'completionReport' => $completionReport,
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
            LearnerLogger::log('learner.assignment.upload.blocked', 'upload')
                ->status('blocked')
                ->severity('warning')
                ->humanMessage('Upload blocked: Enrolment not active or payment pending.')
                ->save();
            return back()->with('error', 'Your enrolment is not active or payment is pending.');
        }

        $request->validate([
            'submission_files' => 'required|array|min:1',
            'submission_files.*' => 'file|max:20480',
        ]);

        $files = $request->file('submission_files');
        $uploadedFiles = [];
        
        $totalSize = 0;
        foreach($files as $file) {
            $totalSize += $file->getSize();
        }

        $logger = LearnerLogger::log('learner.assignment.upload.started', 'upload')
            ->humanMessage('Learner started multi-file assignment upload logic: ' . count($files) . ' files.')
            ->save();

        // load relations
        $assignment->load(['module', 'course']);

        if ($enrolment->course?->usesCreditBasedCompletion()
            && $assignment->module?->isUnit()
            && $assignment->module?->unit_type === 'optional'
            && !LearnerCourseUnitSelection::where('learner_id', $user->id)
                ->where('course_id', $assignment->course_id)
                ->where('module_id', $assignment->module_id)
                ->exists()) {
            return back()->with('error', 'Select this optional unit before submitting work.');
        }

        $courseTitle = $assignment->course?->title ?? ($enrolment->course?->title ?? 'Course');
        $moduleTitle = $assignment->module?->title ?? 'Module';

        // folder names
        $courseFolder = $sp->safeName($courseTitle);
        $dsFolder = 'DS_ID_' . str_pad($user->id, 5, '0', STR_PAD_LEFT);
        $modFolder = $sp->safeName($moduleTitle);
        $assFolder = $sp->safeName($assignment->title);

        // create folder structure
        $path1 = $sp->ensureFolder('', $courseFolder);
        $path2 = $sp->ensureFolder($path1, $dsFolder);
        $path3 = $sp->ensureFolder($path2, $modFolder);
        $path4 = $sp->ensureFolder($path3, $assFolder);

        // Calculate Attempt No
        $attemptNo = (int) (AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('learner_id', $user->id)
            ->max('attempt_no') ?? 0);

        // --- ENFORCE RESUBMISSION RULES ---
        $standardMaxAttempts = 2;
        $extraAttemptsGranted = 0;
        if (Schema::connection('mysql_crm')->hasTable('grade_extra_attempts')) {
            $extraAttemptsGranted = (int) \App\Models\Crm\GradeExtraAttempt::where('portal_assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->sum('additional_attempts');
        }
        $maxAllowedAttempts = $standardMaxAttempts + $extraAttemptsGranted;

        if ($attemptNo >= $maxAllowedAttempts) {
            return back()->with('error', 'Maximum attempts reached for this assignment.');
        }

        if ($attemptNo > 0) {
            $lastSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->latest()
                ->first();

            $hasReset = \App\Models\Crm\GradeReset::where('portal_assignment_id', $assignment->id)
                ->where('learner_id', $user->id)
                ->where('reset_at', '>', $lastSubmission->created_at)
                ->exists();

            if (!$hasReset) {
                return back()->with('error', 'Re-submission is not yet approved by the assessor.');
            }
        }
        // ----------------------------------

        // Process uploads
        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $ext = strtolower($file->getClientOriginalExtension());
            $fileName = $sp->safeName($baseName) . '_' . time() . '_' . \Illuminate\Support\Str::random(4) . '.' . $ext;
            
            $size = $file->getSize();

            if ($size <= 3.5 * 1024 * 1024) {
                $uploaded = $sp->uploadSmallFile($path4, $fileName, $file->getRealPath());
            } else {
                $uploaded = $sp->uploadLargeFile($path4, $fileName, $file->getRealPath());
            }

            $uploadedFiles[] = [
                'name' => $originalName,
                'path' => $courseFolder . '/' . $dsFolder . '/' . $modFolder . '/' . $assFolder . '/' . $fileName,
                'item_id' => $uploaded['id'] ?? null,
                'url' => $uploaded['webUrl'] ?? null,
                'size' => $size,
                'mime' => $file->getClientMimeType()
            ];
        }

        $primaryFile = $uploadedFiles[0];

        // DB save submission header
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'learner_id' => $user->id,
            'file_name' => $primaryFile['name'],
            'status_id' => 1, // Submitted
            'attempt_no' => $attemptNo + 1,

            'sharepoint_item_id' => $primaryFile['item_id'],
            'sharepoint_path' => $primaryFile['path'],
            'sharepoint_url' => $primaryFile['url'],
        ]);

        if ($assignment->module?->isUnit() && $assignment->module?->unit_type === 'optional') {
            $selection = LearnerCourseUnitSelection::where('learner_id', $user->id)
                ->where('course_id', $assignment->course_id)
                ->where('module_id', $assignment->module_id)
                ->first();
            if ($selection && !$selection->is_locked) {
                $old = $selection->toArray();
                $selection->update(['is_locked' => true, 'locked_at' => now()]);
                \App\Models\LearnerCourseUnitSelectionHistory::create([
                    'learner_id' => $user->id,
                    'course_id' => $assignment->course_id,
                    'module_id' => $assignment->module_id,
                    'action' => 'locked',
                    'old_value' => $old,
                    'new_value' => $selection->fresh()->toArray(),
                    'reason' => 'Assignment submission created for the optional unit.',
                    'created_at' => now(),
                ]);
            }
        }

        // DB save all files
        foreach ($uploadedFiles as $uFile) {
            \App\Models\AssignmentSubmissionFile::create([
                'assignment_submission_id' => $submission->id,
                'file_name' => $uFile['name'],
                'sharepoint_item_id' => $uFile['item_id'],
                'sharepoint_path' => $uFile['path'],
                'sharepoint_url' => $uFile['url'],
                'file_size' => $uFile['size'],
                'mime_type' => $uFile['mime'],
            ]);
        }

        \Illuminate\Support\Facades\Log::info('Assignment submission saved successfully', [
            'submission_id' => $submission->id ?? null,
            'learner_id' => $submission->learner_id ?? null,
            'assignment_id' => $submission->assignment_id ?? null,
            'file_name' => $submission->file_name ?? null,
        ]);
        
        LearnerLogger::log('learner.assignment.submission.completed', 'upload')
            ->courseId($assignment->course_id)
            ->assignmentId($assignment->id)
            ->humanMessage('Assignment submitted successfully with ' . count($uploadedFiles) . ' files')
            ->save();

        try {
            \App\Jobs\SendAssignmentSubmissionNotifications::dispatch($submission->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Assignment submission email job dispatch failed', [
                'submission_id' => $submission->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return back()->with('success', 'Your assignment files have been submitted');
    }

    public function viewSubmission(AssignmentSubmission $submission, SharePointService $sp)
    {
        $user = Auth::user();

        if ((int) $submission->learner_id !== (int) $user->id) {
            abort(403);
        }
        $submission->loadMissing('assignment');
        if ($submission->assignment) {
            $this->assertModuleContentAccessible($user->id, $submission->assignment->course_id, $submission->assignment->module_id);
        }

        if (!$submission->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        // inline if route is "inline" OR query inline=1
        $inline = request()->routeIs('portal.learner.submission.inline') || request()->boolean('inline', false);

        return $sp->streamByItemId(
            $submission->sharepoint_item_id,
            $submission->file_name ?? 'submission',
            $inline
        );
    }

    public function viewSubmissionFile(\App\Models\AssignmentSubmissionFile $file, SharePointService $sp)
    {
        $user = Auth::user();

        // Check if the user owns the submission through the relationship
        if (!$file->submission || (int) $file->submission->learner_id !== (int) $user->id) {
            abort(403);
        }
        $file->submission->loadMissing('assignment');
        if ($file->submission->assignment) {
            $this->assertModuleContentAccessible($user->id, $file->submission->assignment->course_id, $file->submission->assignment->module_id);
        }

        if (!$file->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        $inline = request()->boolean('inline', true);

        return $sp->streamByItemId(
            $file->sharepoint_item_id,
            $file->file_name ?? 'submission',
            $inline
        );
    }

    public function submissionDirectLink(AssignmentSubmission $submission, SharePointService $sp)
    {
        $user = Auth::user();

        if ((int) $submission->learner_id !== (int) $user->id) {
            abort(403);
        }
        $submission->loadMissing('assignment');
        if ($submission->assignment) {
            $this->assertModuleContentAccessible($user->id, $submission->assignment->course_id, $submission->assignment->module_id);
        }

        if (!$submission->sharepoint_item_id) {
            return back()->with('error', 'File not found on SharePoint.');
        }

        $url = $sp->temporaryDownloadUrlByItemId($submission->sharepoint_item_id);

        return redirect()->away($url);
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
        $this->assertModuleContentAccessible($user->id, $lesson->course_id, $lesson->module_id);

        $enrolment->loadMissing('course');
        if ($enrolment->course?->usesCreditBasedCompletion()
            && $module->isUnit()
            && $module->unit_type === 'optional'
            && !LearnerCourseUnitSelection::where('learner_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->where('module_id', $module->id)->exists()) {
            abort(403, 'Select this optional unit before opening its learning content.');
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
            
        LearnerLogger::log('learner.lesson.view.opened')
            ->courseId($lesson->course_id)
            ->moduleId($lesson->module_id)
            ->lessonId($lesson->id)
            ->humanMessage('Learner accessed a lesson view')
            ->save();

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
            LearnerLogger::log('learner.lesson.access_denied', 'activity')
                ->status('blocked')
                ->severity('warning')
                ->humanMessage('Lesson access blocked: Enrolment not active.')
                ->save();
            abort(403);
        }
        $this->assertModuleContentAccessible($user->id, $lesson->course_id, $lesson->module_id);

        if ($lesson->resource_type === 'secure_document') {
            abort(403, 'Direct download is disabled for secure documents.');
        }

        LearnerLogger::log('learner.lesson.viewed', 'activity')
            ->humanMessage('Learner viewed lesson/downloaded file')
            ->save();

        if (!blank($lesson->sharepoint_item_id)) {
            $inline = request()->routeIs('portal.learner.lesson.file.inline');
            $name = $lesson->file_name ?? $lesson->title . '.pdf';
            return $sp->streamByItemId($lesson->sharepoint_item_id, $name, $inline);
        }

        if (!blank($lesson->file_path)) {
            if (\Storage::disk('public')->exists($lesson->file_path)) {
                return response()->download(\Storage::disk('public')->path($lesson->file_path), $lesson->title . '.' . pathinfo($lesson->file_path, PATHINFO_EXTENSION));
            }

            // 2. Try CRM storage root
            $crmRoot = config('services.crm.storage_root');
            if ($crmRoot) {
                $crmRootReal = realpath($crmRoot);
                if ($crmRootReal) {
                    // LMS resources are on 'public' disk in CRM, so storage/app/public/{file_path}
                    $relative = 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $lesson->file_path), '\\/');
                    $fullPath = $crmRootReal . DIRECTORY_SEPARATOR . $relative;

                    $fullReal = realpath($fullPath);
                    if ($fullReal && is_file($fullReal) && strpos($fullReal, $crmRootReal) === 0) {
                        return response()->download($fullReal, $lesson->title . '.' . pathinfo($fullReal, PATHINFO_EXTENSION));
                    }
                }
            }
        }

        return back()->with('error', 'File not available.');
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
            LearnerLogger::log('learner.assignment.brief.access_denied', 'activity')
                ->status('blocked')
                ->severity('warning')
                ->humanMessage('Assignment brief access blocked: Enrolment not active.')
                ->save();
            abort(403);
        }
        $this->assertModuleContentAccessible($user->id, $assignment->course_id, $assignment->module_id);

        LearnerLogger::log('learner.assignment.brief.viewed', 'activity')
            ->humanMessage('Learner viewed assignment brief')
            ->save();

        if (blank($brief->sharepoint_item_id)) {
            return back()->with('error', 'Brief not available on SharePoint.');
        }

        $inline = request()->routeIs('portal.learner.assignment.brief.inline');
        return $sp->streamByItemId($brief->sharepoint_item_id, $brief->file_name ?? 'brief', $inline);
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
        $this->assertModuleContentAccessible($user->id, $assignment->course_id, $assignment->module_id);

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

        $downloadName = $brief->file_name ?: basename($fullReal);

        return response()->download($fullReal, $downloadName);
    }

    public function downloadGradingFile(Request $request, Assignment $assignment)
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

        // 3. Determine Path
        $path = null;
        if ($type === 'marking_sheet') {
            $path = $attempt->marking_sheet_path;
        } elseif ($type === 'feedback_file') {
            $path = $attempt->feedback_file_path;
        }

        if (blank($path)) {
            return back()->with('error', 'File not available.');
        }

        // 4. Download Logic
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return redirect()->away($path);
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

        if (!(int) $lesson->is_published) {
            abort(404);
        }

        if ($lesson->resource_type !== 'secure_document') {
            abort(400, 'This resource is not a secure document.');
        }

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is not active or payment is pending.');
        }
        $this->assertModuleContentAccessible($user->id, $lesson->course_id, $lesson->module_id);

        LearnerLogger::log('learner.secure_doc.view')
            ->courseId($lesson->course_id)
            ->lessonId($lesson->id)
            ->humanMessage('Learner accessed secure document viewer')
            ->save();

        return view('learner.lessons.secure_viewer', [
            'lesson' => $lesson,
            'enrolment' => $enrolment,
            'user' => $user,
        ]);
    }

    public function streamSecureDocument(Lesson $lesson)
    {
        $user = Auth::user();

        // Add debug logging
        \Log::info('Secure stream requested', [
            'lesson_id' => $lesson->id,
            'user_id' => auth()->id(),
            'type' => $lesson->resource_type ?? null,
            'file_path' => $lesson->file_path ?? null,
            'file_url' => $lesson->file_url ?? null,
            'sharepoint_url' => $lesson->sharepoint_item_id ?? null,
            'mime_type' => 'application/pdf',
        ]);

        if (!(int) $lesson->is_published || $lesson->resource_type !== 'secure_document') {
            abort(404, 'Secure document not found.');
        }

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if (!$this->checkAccess($enrolment)) {
            abort(403, 'Unauthorized access.');
        }
        $this->assertModuleContentAccessible($user->id, $lesson->course_id, $lesson->module_id);

        $filePath = $lesson->file_path;
        if (blank($filePath)) {
            \Log::error('Secure stream failed: no file path found', [
                'lesson_id' => $lesson->id,
            ]);
            abort(404, 'Document file path missing.');
        }

        $fullReal = null;
        $crmRoot = "D:\\Laravel\\test\\crm-directskills\\storage\\app";
        $relative = 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), '\\/');
        $fullPath = $crmRoot . DIRECTORY_SEPARATOR . $relative;

        if (file_exists($fullPath) && is_file($fullPath)) {
            $fullReal = $fullPath;
        } else {
            $crmRootConfig = config('services.crm.storage_root');
            if ($crmRootConfig) {
                $relativeConfig = 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), '\\/');
                $fullPathConfig = rtrim($crmRootConfig, '/\\') . DIRECTORY_SEPARATOR . $relativeConfig;
                if (file_exists($fullPathConfig) && is_file($fullPathConfig)) {
                    $fullReal = $fullPathConfig;
                }
            }
        }

        if (!$fullReal) {
            if (\Storage::disk('public')->exists($filePath)) {
                $fullReal = \Storage::disk('public')->path($filePath);
            } elseif (\Storage::disk('local')->exists($filePath)) {
                $fullReal = \Storage::disk('local')->path($filePath);
            }
        }

        \Log::info('Secure PDF stream debug', [
            'lesson_id' => $lesson->id,
            'file_path' => $filePath,
            'absolute_path' => $fullReal ?? null,
            'exists' => $fullReal ? file_exists($fullReal) : false,
            'size' => ($fullReal && file_exists($fullReal)) ? filesize($fullReal) : null,
        ]);

        if (!$fullReal || !file_exists($fullReal)) {
            \Log::error('PDF missing', [
                'lesson_id' => $lesson->id,
                'path' => $fullReal ?? 'null'
            ]);
            abort(404, 'File not found');
        }

        $absolutePath = $fullReal;
        $binary = file_get_contents($absolutePath);

        \Log::info('FORCED PDF RESPONSE RETURNING', [
            'lesson_id' => $lesson->id,
            'status' => 200,
            'bytes' => strlen($binary),
            'first_bytes' => substr($binary, 0, 5),
        ]);

        return response($binary, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Length', strlen($binary))
            ->header('Content-Disposition', 'inline; filename="' . basename($absolutePath) . '"')
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function securePdfData(Lesson $lesson)
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
        $this->assertModuleContentAccessible($user->id, $lesson->course_id, $lesson->module_id);

        $filePath = $lesson->file_path;
        if (blank($filePath)) {
            abort(404, 'Document file path missing.');
        }

        $fullReal = null;
        $crmRoot = "D:\\Laravel\\test\\crm-directskills\\storage\\app";
        $relative = 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), '\\/');
        $fullPath = $crmRoot . DIRECTORY_SEPARATOR . $relative;

        if (file_exists($fullPath) && is_file($fullPath)) {
            $fullReal = $fullPath;
        } else {
            $crmRootConfig = config('services.crm.storage_root');
            if ($crmRootConfig) {
                $relativeConfig = 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), '\\/');
                $fullPathConfig = rtrim($crmRootConfig, '/\\') . DIRECTORY_SEPARATOR . $relativeConfig;
                if (file_exists($fullPathConfig) && is_file($fullPathConfig)) {
                    $fullReal = $fullPathConfig;
                }
            }
        }

        if (!$fullReal) {
            if (\Storage::disk('public')->exists($filePath)) {
                $fullReal = \Storage::disk('public')->path($filePath);
            } elseif (\Storage::disk('local')->exists($filePath)) {
                $fullReal = \Storage::disk('local')->path($filePath);
            }
        }

        if (!$fullReal || !file_exists($fullReal)) {
            abort(404, 'File not found');
        }

        if (filesize($fullReal) > 50 * 1024 * 1024) {
            abort(413, 'PDF too large for inline secure viewer');
        }

        $binary = file_get_contents($fullReal);

        return response()->json([
            'success' => true,
            'filename' => basename($fullReal),
            'mime' => 'application/pdf',
            'data' => base64_encode($binary),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function selectUnit(Request $request, Enrolment $enrolment, CourseModule $module, UnitSelectionService $service)
    {
        $this->assertOwnedActiveEnrolment($enrolment);
        $service->selectOptional(Auth::id(), $enrolment->course, $module);
        return back()->with('success', 'Optional unit selected.');
    }

    public function removeUnit(Request $request, Enrolment $enrolment, CourseModule $module, UnitSelectionService $service)
    {
        $this->assertOwnedActiveEnrolment($enrolment);
        $service->removeOptional(Auth::id(), $enrolment->course, $module);
        return back()->with('success', 'Optional unit removed.');
    }

    public function finaliseUnitSelection(Enrolment $enrolment, UnitSelectionService $service)
    {
        $this->assertOwnedActiveEnrolment($enrolment);
        $service->finalise(Auth::id(), $enrolment->course);
        return back()->with('success', 'Your optional unit selection meets the course rules.');
    }

    private function assertOwnedActiveEnrolment(Enrolment $enrolment): void
    {
        if ((int) $enrolment->learner_id !== (int) Auth::id()) abort(403);
        $enrolment->loadMissing(['course', 'status']);
        if (!$this->checkAccess($enrolment) || !$enrolment->course?->usesCreditBasedCompletion()) abort(403);
    }

    private function assertModuleContentAccessible(int $learnerId, int $courseId, int $moduleId): void
    {
        $course = \App\Models\Website\Course::find($courseId);
        if (!$course?->usesCreditBasedCompletion()) return;
        $module = CourseModule::find($moduleId);
        if ($module?->isUnit() && $module?->unit_type === 'optional'
            && !LearnerCourseUnitSelection::where('learner_id', $learnerId)
                ->where('course_id', $courseId)->where('module_id', $moduleId)->exists()) {
            abort(403, 'Select this optional unit before accessing its content.');
        }
    }
}
