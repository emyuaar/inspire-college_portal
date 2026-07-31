<?php

namespace Tests\Feature;

use App\Http\Controllers\Payment\CheckoutController;
use App\Http\Controllers\Partner\CoursePurchaseController;
use App\Models\Crm\Enrolment;
use App\Models\User;
use App\Services\CrmNotificationService;
use App\Services\LearnerActivationService;
use App\Services\PartnerCoursePricingService;
use App\Services\PaymentProcessingService;
use App\Services\StripeSessionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class PartnerStripePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['mysql_portal', 'mysql_crm'] as $connection) {
            config(['database.connections.' . $connection => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]]);
            DB::purge($connection);
        }
        $this->createPortalSchema();
        $this->createCrmSchema();
    }

    public function test_staged_partner_payment_allocates_and_activates_once(): void
    {
        $this->seedStagedPayment();
        $service = $this->serviceWithActivationOnce();

        $first = $service->processOrderPayment(
            $this->session(), $this->metadata()
        );
        $second = $service->processOrderPayment(
            $this->session(), $this->metadata()
        );

        $this->assertTrue($first['newly_processed']);
        $this->assertTrue($first['activated']);
        $this->assertTrue($second['already_processed']);
        $this->assertSame(9001, $second['portal_learner_id']);
        $this->assertDatabaseCount('payments', 1, 'mysql_crm');
        $this->assertDatabaseHas('orders', [
            'id' => 79, 'status_id' => 1,
            'deposit_paid_amount' => 550, 'stripe_payment_id' => 'pi_test_partner',
        ], 'mysql_crm');
        $this->assertDatabaseHas('partner_learner_installments', [
            'id' => 501, 'status' => 'paid',
            'paid_amount' => 550, 'payment_reference' => 'cs_test_partner',
        ], 'mysql_crm');
        $this->assertDatabaseHas('enrolments', [
            'id' => 77, 'payment_status' => 'paid',
            'activation_status' => 'active', 'learner_id' => 9001,
        ], 'mysql_crm');
    }

    public function test_existing_portal_learner_payment_is_supported(): void
    {
        $this->seedStagedPayment();
        DB::connection('mysql_portal')->table('users')->insert([
            'id' => 10, 'email_address' => 'existing@example.test',
        ]);
        DB::connection('mysql_crm')->table('partner_learner_installments')->delete();
        DB::connection('mysql_crm')->table('orders')->where('id', 79)
            ->update(['learner_id' => 10, 'partner_learner_id' => null]);
        DB::connection('mysql_crm')->table('enrolments')->where('id', 77)
            ->update(['learner_id' => 10, 'partner_learner_id' => null]);

        $result = $this->serviceWithActivationOnce()->processOrderPayment(
            $this->session(), $this->metadata([
                'learner_id' => 10, 'partner_learner_id' => null,
            ])
        );
        $this->assertTrue($result['newly_processed']);
        $this->assertDatabaseHas('orders', ['id' => 79, 'status_id' => 1], 'mysql_crm');
    }

    public function test_invalid_partner_relationship_is_rejected(): void
    {
        $this->seedStagedPayment();
        $activation = Mockery::mock(LearnerActivationService::class);
        $activation->shouldNotReceive('activate');
        $this->expectException(\DomainException::class);

        (new PaymentProcessingService($activation))->processOrderPayment(
            $this->session(), $this->metadata(['partner_id' => 999])
        );
    }

    public function test_amount_mismatch_is_rejected_without_a_payment(): void
    {
        $this->seedStagedPayment();
        $activation = Mockery::mock(LearnerActivationService::class);
        $activation->shouldNotReceive('activate');
        try {
            (new PaymentProcessingService($activation))->processOrderPayment(
                $this->session(['amount_total' => 54_999]), $this->metadata()
            );
            $this->fail('Expected amount mismatch.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('payments', 0, 'mysql_crm');
        }
    }

    public function test_currency_mismatch_is_rejected_without_a_payment(): void
    {
        $this->seedStagedPayment();
        $activation = Mockery::mock(LearnerActivationService::class);
        $activation->shouldNotReceive('activate');
        try {
            (new PaymentProcessingService($activation))->processOrderPayment(
                $this->session(['currency' => 'usd']), $this->metadata()
            );
            $this->fail('Expected currency mismatch.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('payments', 0, 'mysql_crm');
        }
    }

    public function test_reconciliation_repairs_a_paid_pending_order(): void
    {
        $this->seedStagedPayment();
        $stripe = Mockery::mock(StripeSessionService::class);
        $stripe->shouldReceive('retrieve')->once()
            ->with('cs_test_partner')->andReturn(
                $this->session(['metadata' => (object) $this->metadata()])
            );
        $this->app->instance(StripeSessionService::class, $stripe);
        $this->app->instance(
            PaymentProcessingService::class, $this->serviceWithActivationOnce()
        );

        $this->artisan('stripe:reconcile-session cs_test_partner')
            ->expectsOutputToContain('Session reconciled successfully.')
            ->assertSuccessful();
        $this->assertDatabaseHas('orders', ['id' => 79, 'status_id' => 1], 'mysql_crm');
    }

    public function test_paid_order_cannot_start_another_checkout(): void
    {
        $this->seedStagedPayment();
        DB::connection('mysql_portal')->table('users')->insert([
            ['id' => 2257, 'org_id' => 2257, 'email_address' => 'partner@example.test'],
            ['id' => 10, 'org_id' => 2257, 'email_address' => 'learner@example.test'],
        ]);
        DB::connection('mysql_crm')->table('orders')->where('id', 79)
            ->update(['learner_id' => 10, 'partner_learner_id' => null, 'status_id' => 1]);
        DB::connection('mysql_crm')->table('enrolments')->where('id', 77)
            ->update(['learner_id' => 10, 'partner_learner_id' => null]);
        Auth::setUser(User::findOrFail(2257));

        $request = Request::create('/partner/learners/10/pay', 'POST', [
            'enrolment_id' => 77,
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $pricing = Mockery::mock(PartnerCoursePricingService::class);
        $pricing->shouldNotReceive('calculatePricing');

        $response = (new CheckoutController())->createCheckoutSession($request, 10, $pricing);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('already paid', $request->session()->get('error'));
    }

    public function test_proof_submission_waits_for_admin_approval(): void
    {
        Storage::fake('public');
        $this->seedStagedPayment();
        DB::connection('mysql_portal')->table('users')->insert([
            'id' => 2257, 'org_id' => 2257,
            'email_address' => 'partner@example.test',
        ]);
        Auth::setUser(User::findOrFail(2257));

        $notifications = Mockery::mock(CrmNotificationService::class);
        $notifications->shouldReceive('notifyAdminsForProofSubmission')->once();
        $this->app->instance(CrmNotificationService::class, $notifications);

        $request = Request::create('/partner/enrolments/77/submit-proof', 'POST', [
            'payment_reference' => 'bank-ref',
        ], [], [
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $response = (new CoursePurchaseController())->submitProof($request, 77);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('partner_learner_installments', [
            'id' => 501, 'status' => 'awaiting_approval',
            'submitted_amount' => 550,
        ], 'mysql_crm');
        $this->assertDatabaseHas('partner_payment_proofs', [
            'order_id' => 79, 'status' => 'awaiting_approval',
        ], 'mysql_crm');
        $this->assertDatabaseHas('enrolments', [
            'id' => 77, 'payment_status' => 'pending',
            'activation_status' => 'pending',
        ], 'mysql_crm');
    }

    private function seedStagedPayment(): void
    {
        DB::connection('mysql_crm')->table('partner_learners')->insert([
            'id' => 11, 'partner_id' => 2257,
            'activation_status' => 'pending',
        ]);
        DB::connection('mysql_crm')->table('enrolments')->insert([
            'id' => 77, 'learner_id' => null, 'partner_learner_id' => 11,
            'partner_id' => 2257, 'status_id' => 6,
            'payment_status' => 'pending', 'activation_status' => 'pending',
        ]);
        DB::connection('mysql_crm')->table('orders')->insert([
            'id' => 79, 'learner_id' => null, 'partner_learner_id' => 11,
            'enrolment_id' => 77, 'amount' => 550,
            'status_id' => 3, 'payment_mode' => 'installment',
            'plan_deposit_amount' => 550, 'deposit_paid_amount' => 0,
        ]);
        DB::connection('mysql_crm')->table('partner_learner_installments')->insert([
            'id' => 501, 'partner_id' => 2257, 'learner_id' => 11,
            'enrolment_id' => 77, 'order_id' => 79,
            'installment_no' => 0, 'due_date' => '2026-07-13',
            'installment_amount' => 550, 'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }

    private function session(array $overrides = []): object
    {
        return (object) array_merge([
            'id' => 'cs_test_partner', 'payment_status' => 'paid',
            'payment_intent' => (object) ['id' => 'pi_test_partner', 'created' => 1_752_665_280],
            'amount_total' => 55_000, 'currency' => 'gbp',
            'customer_details' => (object) [
                'email' => 'learner@example.test', 'name' => 'Partner Learner',
            ],
        ], $overrides);
    }

    private function metadata(array $overrides = []): array
    {
        return array_merge([
            'order_id' => 79, 'enrolment_id' => 77,
            'partner_id' => 2257, 'partner_learner_id' => 11,
            'learner_id' => null, 'type' => 'order_payment',
        ], $overrides);
    }

    private function serviceWithActivationOnce(): PaymentProcessingService
    {
        $activation = Mockery::mock(LearnerActivationService::class);
        $activation->shouldReceive('activate')->once()->andReturnUsing(
            function (Enrolment $enrolment) {
                $enrolment->learner_id = 9001;
                $enrolment->activation_status = 'active';
                $enrolment->welcome_email_sent_at = now();
                $enrolment->save();
                return $enrolment;
            }
        );
        return new PaymentProcessingService($activation);
    }

    private function createPortalSchema(): void
    {
        Schema::connection('mysql_portal')->create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('sur_name')->nullable();
            $table->string('email_address')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->boolean('crm_approved')->default(false);
            $table->timestamps();
        });
    }

    private function createCrmSchema(): void
    {
        Schema::connection('mysql_crm')->create('enrolment_status', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
        Schema::connection('mysql_crm')->create('partner_learners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('partner_id');
            $table->string('payment_status')->nullable();
            $table->string('activation_status')->default('pending');
            $table->timestamps();
        });
        Schema::connection('mysql_crm')->create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('partner_learner_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('activation_status')->default('pending');
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('mysql_crm')->create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('partner_learner_id')->nullable();
            $table->unsignedBigInteger('enrolment_id');
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('payment_mode')->nullable();
            $table->decimal('plan_deposit_amount', 10, 2)->default(0);
            $table->decimal('deposit_paid_amount', 10, 2)->default(0);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->string('stripe_payment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('mysql_crm')->create('order_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('installment_no');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_payment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('mysql_crm')->create('partner_learner_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('learner_id');
            $table->unsignedBigInteger('enrolment_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('installment_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->decimal('submitted_amount', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql_crm')->create('partner_payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_learner_installment_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('enrolment_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->decimal('submitted_amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('proof_path');
            $table->text('notes')->nullable();
            $table->string('status')->default('awaiting_approval');
            $table->timestamps();
        });
        Schema::connection('mysql_crm')->create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedInteger('amount_pence');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->string('method');
            $table->string('currency', 3);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('reference')->nullable();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        DB::connection('mysql_crm')->table('enrolment_status')->insert([
            'id' => 2, 'status' => 'active',
        ]);
    }
}
