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
            if (!$enrolment->latestOrder || $enrolment->latestOrder->status_id !== 1) {
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
            'submission_file' => 'required|file|max:20480',
        ]);

        // file + original name
        $file = $request->file('submission_file');
        $originalName = $file->getClientOriginalName();

        // load relations (must exist on Assignment model)
        $assignment->load(['module', 'course']); // if assignment->course relation exists

        $courseTitle = $assignment->course?->title ?? ($enrolment->course?->title ?? 'Course');
        $moduleTitle = $assignment->module?->title ?? 'Module';

        // folder names
        $courseFolder = $sp->safeName($courseTitle);                          // Course_Name
        $learnerFolder = 'ICOL_ID_' . str_pad($user->id, 5, '0', STR_PAD_LEFT);   // ICOL_ID_00001
        $modFolder = $sp->safeName($moduleTitle);                          // Module_1
        $assFolder = $sp->safeName($assignment->title);                    // Assignment_1

        // file name safe
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower($file->getClientOriginalExtension());
        $fileName = $sp->safeName($baseName) . '.' . $ext;

        // create folder structure
        $path1 = $sp->ensureFolder('', $courseFolder);
        $path2 = $sp->ensureFolder($path1, $learnerFolder);
        $path3 = $sp->ensureFolder($path2, $modFolder);
        $path4 = $sp->ensureFolder($path3, $assFolder);

        // upload to SharePoint
        $size = $file->getSize(); // bytes

        if ($size <= 3.5 * 1024 * 1024) {
            $uploaded = $sp->uploadSmallFile($path4, $fileName, $file->getRealPath());
        } else {
            $uploaded = $sp->uploadLargeFile($path4, $fileName, $file->getRealPath());
        }

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

        // DB save (NO file_path)
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'learner_id' => $user->id,
            'file_name' => $originalName,
            'status' => 'submitted',
            'attempt_no' => $attemptNo + 1,

            'sharepoint_item_id' => $uploaded['id'] ?? null,
            'sharepoint_path' => $courseFolder . '/' . $learnerFolder . '/' . $modFolder . '/' . $assFolder . '/' . $fileName,
            'sharepoint_url' => $uploaded['webUrl'] ?? null,
        ]);

        return back()->with('success', 'Your assignment file has been submitted');
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
            $inline
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

        // published only
        if (!(int) $lesson->is_published) {
            abort(404);
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

        if (blank($lesson->sharepoint_item_id)) {
            return back()->with('error', 'Lesson file not available on SharePoint.');
        }

        $inline = request()->routeIs('portal.learner.lesson.file.inline');

        $name = $lesson->file_name ?? $lesson->title . '.pdf'; // adjust if you store
        return $sp->streamByItemId($lesson->sharepoint_item_id, $name, $inline);
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
}
