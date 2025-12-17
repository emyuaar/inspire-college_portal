<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Crm\Enrolment;
use App\Models\CourseModule;
use App\Models\Assignment;
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
        $uploaded = $sp->uploadSmallFile($path4, $fileName, $file->getRealPath());

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
}
