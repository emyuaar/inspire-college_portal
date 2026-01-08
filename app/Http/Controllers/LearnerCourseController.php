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
use App\Services\SharePointService;

class LearnerCourseController extends Controller
{
    public function show(Enrolment $enrolment)
    {
        $user = Auth::user();

        if ($enrolment->learner_id != $user->id) {
            abort(403, 'You are not allowed to view this course.');
        }

        if ((int) $enrolment->status_id !== 2) {

            // Optional: better messages
            if ((int) $enrolment->status_id === 3) {
                return redirect()
                    ->route('portal.learner.dashboard')
                    ->with('error', 'Your enrolment was denied. Please refill your information and submit again.');
            }

            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is awaiting approval.');
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
            ])
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->get();

        return view('learner.courses.show', [
            'user'      => $user,
            'enrolment' => $enrolment,
            'course'    => $course,
            'modules'   => $modules,
        ]);
    }

    public function submitAssignment(Request $request, Assignment $assignment, SharePointService $sp)
    {
        $user = Auth::user();

        // enrolment check (CRM DB)
        $enrolment = Enrolment::with('course') // so we can access $enrolment->course->title if needed
            ->where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if ((int) $enrolment->status_id !== 2) {
            return back()->with('error', 'Your enrolment is not approved yet.');
        }

        $request->validate([
            'submission_file' => 'required|file|max:20480',
        ]);

        // file + original name
        $file         = $request->file('submission_file');
        $originalName = $file->getClientOriginalName();

        // load relations (must exist on Assignment model)
        $assignment->load(['module', 'course']); // if assignment->course relation exists

        $courseTitle = $assignment->course?->title ?? ($enrolment->course?->title ?? 'Course');
        $moduleTitle = $assignment->module?->title ?? 'Module';

        // folder names
        $courseFolder = $sp->safeName($courseTitle);                          // Course_Name
        $dsFolder     = 'DS_ID_' . str_pad($user->id, 5, '0', STR_PAD_LEFT);   // DS_ID_00001
        $modFolder    = $sp->safeName($moduleTitle);                          // Module_1
        $assFolder    = $sp->safeName($assignment->title);                    // Assignment_1

        // file name safe
        $baseName  = pathinfo($originalName, PATHINFO_FILENAME);
        $ext       = strtolower($file->getClientOriginalExtension());
        $fileName  = $sp->safeName($baseName) . '.' . $ext;

        // create folder structure
        $path1 = $sp->ensureFolder('', $courseFolder);
        $path2 = $sp->ensureFolder($path1, $dsFolder);
        $path3 = $sp->ensureFolder($path2, $modFolder);
        $path4 = $sp->ensureFolder($path3, $assFolder);

        // upload to SharePoint
        $size = $file->getSize(); // bytes

        if ($size <= 3.5 * 1024 * 1024) {
            $uploaded = $sp->uploadSmallFile($path4, $fileName, $file->getRealPath());
        } else {
            $uploaded = $sp->uploadLargeFile($path4, $fileName, $file->getRealPath());
        }

        // DB save (NO file_path)
        AssignmentSubmission::create([
            'assignment_id'      => $assignment->id,
            'learner_id'         => $user->id,
            'file_name'          => $originalName,
            'status'             => 'submitted',

            'sharepoint_item_id' => $uploaded['id'] ?? null,
            'sharepoint_path'    => $courseFolder.'/'.$dsFolder.'/'.$modFolder.'/'.$assFolder.'/'.$fileName,
            'sharepoint_url'     => $uploaded['webUrl'] ?? null,
        ]);

        return back()->with('success', 'Your assignment file has been submitted (uploaded to SharePoint).');
    }

    public function viewSubmission(AssignmentSubmission $submission, SharePointService $sp)
    {
        $user = Auth::user();

        if ((int)$submission->learner_id !== (int)$user->id) {
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

        if ((int)$submission->learner_id !== (int)$user->id) {
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

        if ((int) $enrolment->status_id !== 2) {
            return redirect()
                ->route('portal.learner.dashboard')
                ->with('error', 'Your enrolment is not approved yet.');
        }

        // published only
        if (!(int)$lesson->is_published) {
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
            'lesson'   => $lesson,
            'module'   => $module,
            'enrolment'=> $enrolment,
            'prev'     => $prev,
            'next'     => $next,
        ]);
    }

    public function downloadLessonFile(Lesson $lesson, SharePointService $sp)
    {
        $user = Auth::user();

        // learner enrolment check for this lesson's course (approved only)
        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        if ((int)$enrolment->status_id !== 2) {
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
        if (!$assignment) abort(404);

        $enrolment = Enrolment::where('learner_id', $user->id)
            ->where('course_id', $assignment->course_id)
            ->firstOrFail();

        if ((int)$enrolment->status_id !== 2) {
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

        if ((int)$enrolment->status_id !== 2) {
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
}
