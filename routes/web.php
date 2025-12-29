<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnerCourseController;
use Illuminate\Support\Facades\Route;

// ========== AUTH ROUTES ==========
Route::get('/', [AuthController::class, 'showLoginForm'])
    ->name('portal.login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('portal.login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('portal.logout');

// ========== PROTECTED ROUTES (AFTER LOGIN) ==========
Route::middleware('auth')->group(function () {

    // Common dashboard route – optionally use if you want single entry
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('portal.dashboard');

    // Learner specific
    Route::get('/learner/dashboard', [DashboardController::class, 'learner'])
        ->name('portal.learner.dashboard');

    // Organization specific
    Route::get('/organization/dashboard', [DashboardController::class, 'organization'])
        ->name('portal.organization.dashboard');

    // Learner all courses
    Route::get('/learner/courses', [DashboardController::class, 'allCourses'])
    ->name('portal.learner.courses.all');

    // Account Settings (Profile + Password)
    Route::get('/settings/profile', [App\Http\Controllers\ProfileController::class, 'editAccount'])
        ->name('portal.settings.profile');

    Route::post('/settings/profile', [App\Http\Controllers\ProfileController::class, 'updateAccount'])
        ->name('portal.settings.profile.update');

    // Personal Information
    Route::get('/profile/personal', [App\Http\Controllers\ProfileController::class, 'editPersonal'])
        ->name('portal.profile.personal');
    Route::post('/profile/personal', [App\Http\Controllers\ProfileController::class, 'updatePersonal'])
        ->name('portal.profile.personal.update');

    // RPL Information
    Route::get('/profile/rpl', [App\Http\Controllers\ProfileController::class, 'editRpl'])
        ->name('portal.profile.rpl');
    Route::post('/profile/rpl', [App\Http\Controllers\ProfileController::class,'updateRpl'])
        ->name('portal.profile.rpl.update');

    // Disability Information
    Route::get('/profile/disability', [App\Http\Controllers\ProfileController::class, 'editDisability'])
        ->name('portal.profile.disability');
    Route::post('/profile/disability', [App\Http\Controllers\ProfileController::class,'updateDisability'])
        ->name('portal.profile.disability.update');

    // Learner Courses
    Route::get('/learner/course/{enrolment}', [LearnerCourseController::class, 'show'])
        ->name('portal.learner.course.show');

    // assignment submission
    Route::post('/learner/assignment/{assignment}/submit', [LearnerCourseController::class, 'submitAssignment'])
        ->name('portal.learner.assignment.submit');

    // Route::get('/learner/submissions/{submission}/view', [LearnerCourseController::class, 'viewSubmission'])
    //     ->name('portal.learner.submission.view');
    
    Route::get('/learner/submissions/{submission}/download', [LearnerCourseController::class, 'viewSubmission'])
        ->name('portal.learner.submission.view');

    Route::get('/learner/submissions/{submission}/inline', [LearnerCourseController::class, 'viewSubmission'])
        ->name('portal.learner.submission.inline');

    Route::get('/learner/submissions/{submission}/direct', [LearnerCourseController::class, 'submissionDirectLink'])
        ->name('portal.learner.submission.direct');
    
    Route::get('/learner/lessons/{lesson}', [LearnerCourseController::class, 'viewLesson'])
        ->name('portal.learner.lessons.show');

    // Lesson file (SharePoint proxy)
    Route::get('/learner/lessons/{lesson}/file/download', [LearnerCourseController::class, 'downloadLessonFile'])
        ->name('portal.learner.lesson.file.download');

    Route::get('/learner/lessons/{lesson}/file/inline', [LearnerCourseController::class, 'downloadLessonFile'])
        ->name('portal.learner.lesson.file.inline');

    // Assignment brief file (SharePoint proxy)
    // NOTE: $brief here is an AssignmentFile (or whatever model your $assignment->files uses)
    Route::get('/learner/assignment-brief/{brief}/download', [LearnerCourseController::class, 'downloadAssignmentBrief'])
        ->name('portal.learner.assignment.brief.download');

    Route::get('/learner/assignment-brief/{brief}/inline', [LearnerCourseController::class, 'downloadAssignmentBrief'])
        ->name('portal.learner.assignment.brief.inline');
    
    Route::get('/learner/assignment-brief/{brief}/local', [LearnerCourseController::class, 'downloadAssignmentBriefLocal'])
        ->name('portal.learner.assignment.brief.local');
});
