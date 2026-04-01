<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnerCourseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB; // Added for debug route

// ========== AUTH ROUTES ==========
Route::get('/', [AuthController::class, 'showLoginForm'])
    ->name('portal.login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('portal.login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('portal.logout');

// DEBUG ROUTE
Route::get('/debug-webhook', function () {
    $statuses = \App\Models\Crm\EnrolmentStatus::all();
    $output = "";
    foreach ($statuses as $s) {
        $output .= "ID: {$s->id} - Status: {$s->status} <br>";
    }
    return $output;
});
// Public Webhook (Stripe)
Route::post('/stripe/webhook', [\App\Http\Controllers\Payment\StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

// ========== PROTECTED ROUTES (AFTER LOGIN) ==========
Route::middleware('auth')->group(function () {

    // Common dashboard route – optionally use if you want single entry
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('portal.dashboard');

    // PARTNER / ORGANIZATION ROUTES
    Route::prefix('partner')->name('partner.')->middleware('role:partner')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Partner\DashboardController::class, 'index'])
            ->name('dashboard');
            
        Route::get('/enrolments/pending-plans', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'pendingPlans'])
            ->name('enrolments.pending_plans');

        Route::get('/learners', [\App\Http\Controllers\Partner\PartnerLearnerController::class, 'index'])
            ->name('learners.index');

        Route::get('/learners/create', [\App\Http\Controllers\Partner\PartnerLearnerController::class, 'create'])
            ->name('learners.create');

        Route::post('/learners', [\App\Http\Controllers\Partner\PartnerLearnerController::class, 'store'])
            ->name('learners.store');

        Route::get('/learners/{learner}', [\App\Http\Controllers\Partner\PartnerLearnerController::class, 'show'])
            ->name('learners.show');

        // Partner Courses List
        Route::get('/courses', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'index'])
            ->name('courses.index');

        Route::get('/learners/{learner}/courses/create', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'create'])
            ->name('courses.create');

        Route::post('/learners/{learner}/courses', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'store'])
            ->name('courses.store');

        // Course Plan Selection
        Route::get('/enrolments/{enrolment}/plan', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'choosePlan'])
            ->name('enrolments.choose_plan');

        Route::post('/enrolments/{enrolment}/plan', [\App\Http\Controllers\Partner\CoursePurchaseController::class, 'updatePlan'])
            ->name('enrolments.update_plan');

        Route::post('/learners/{learner}/pay', [\App\Http\Controllers\Payment\CheckoutController::class, 'createCheckoutSession'])
            ->name('checkout');

        // Manual Installment Payment
        Route::get('/installments', [\App\Http\Controllers\Partner\InstallmentController::class, 'index'])
            ->name('installments.index');

        Route::get('/installments/{installment}/checkout', [\App\Http\Controllers\Partner\InstallmentController::class, 'createCheckoutSession'])
            ->name('installments.checkout');

        Route::post('/installments/{installment}/pay', [\App\Http\Controllers\Partner\InstallmentController::class, 'storePayment'])
            ->name('installments.pay');

        // Coupon Validation
        Route::post('/coupon/validate', [\App\Http\Controllers\Api\CouponValidationController::class, 'validateCoupon'])
            ->name('coupon.validate');

        Route::get('/transactions', [\App\Http\Controllers\Partner\TransactionController::class, 'index'])
            ->name('transactions.index');

        // Partner Profile
        Route::get('/profile', [\App\Http\Controllers\Partner\PartnerProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::post('/profile', [\App\Http\Controllers\Partner\PartnerProfileController::class, 'update'])
            ->name('profile.update');

        // Support Tickets
        Route::get('/support', [\App\Http\Controllers\Partner\SupportTicketController::class, 'index'])
            ->name('support.index');
        Route::get('/support/create', [\App\Http\Controllers\Partner\SupportTicketController::class, 'create'])
            ->name('support.create');
        Route::post('/support', [\App\Http\Controllers\Partner\SupportTicketController::class, 'store'])
            ->name('support.store');
        Route::get('/support/{ticket}', [\App\Http\Controllers\Partner\SupportTicketController::class, 'show'])
            ->name('support.show');
        Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Partner\SupportTicketController::class, 'reply'])
            ->name('support.reply');

        // Partner Notifications
        Route::get('/notifications', [\App\Http\Controllers\Partner\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/notifications/dropdown', [\App\Http\Controllers\Partner\NotificationController::class, 'dropdown'])
            ->name('notifications.dropdown');
        Route::get('/notifications/{notification}/read', [\App\Http\Controllers\Partner\NotificationController::class, 'markRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Partner\NotificationController::class, 'markAllRead'])
            ->name('notifications.read_all');
    });

    // LEARNER SPECIFIC ROUTES
    Route::middleware('role:learner')->group(function () {
        // Learner Dashboard
        Route::get('/learner/dashboard', [DashboardController::class, 'learner'])
            ->name('portal.learner.dashboard');

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
        Route::post('/profile/rpl', [App\Http\Controllers\ProfileController::class, 'updateRpl'])
            ->name('portal.profile.rpl.update');

        // Disability Information
        Route::get('/profile/disability', [App\Http\Controllers\ProfileController::class, 'editDisability'])
            ->name('portal.profile.disability');
        Route::post('/profile/disability', [App\Http\Controllers\ProfileController::class, 'updateDisability'])
            ->name('portal.profile.disability.update');

        // Learner Courses
        Route::get('/learner/course/{enrolment}', [LearnerCourseController::class, 'show'])
            ->middleware('installment.access')
            ->name('portal.learner.course.show');

        // assignment submission
        Route::post('/learner/assignment/{assignment}/submit', [LearnerCourseController::class, 'submitAssignment'])
            ->name('portal.learner.assignment.submit');

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
        Route::get('/learner/assignment-brief/{brief}/download', [LearnerCourseController::class, 'downloadAssignmentBrief'])
            ->name('portal.learner.assignment.brief.download');

        Route::get('/learner/assignment-brief/{brief}/inline', [LearnerCourseController::class, 'downloadAssignmentBrief'])
            ->name('portal.learner.assignment.brief.inline');

        Route::get('/learner/assignment-brief/{brief}/local', [LearnerCourseController::class, 'downloadAssignmentBriefLocal'])
            ->name('portal.learner.assignment.brief.local');

        // CRM Grading - File Download
        Route::get('/learner/assignment/{assignment}/grade-file', [LearnerCourseController::class, 'downloadGradingFile'])
            ->name('portal.learner.assignment.grading.download');
    });
});
