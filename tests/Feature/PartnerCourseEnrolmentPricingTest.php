<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerCourseEnrolmentPricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['mysql_portal', 'mysql_crm', 'mysql_website'] as $connection) {
            config(["database.connections.{$connection}" => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            DB::purge($connection);
        }

        $this->createPortalSchema();
        $this->createWebsiteSchema();
        $this->createCrmSchema();
    }

    public function test_partner_enrolment_persists_discounted_two_month_order_and_immutable_snapshot(): void
    {
        DB::connection('mysql_portal')->table('users')->insert([
            ['id' => 10, 'org_id' => 10, 'first_name' => 'Partner', 'sur_name' => 'Company', 'email_address' => 'partner@example.test', 'password' => 'x', 'crm_approved' => 1],
            ['id' => 11, 'org_id' => 10, 'first_name' => 'Learner', 'sur_name' => 'One', 'email_address' => 'learner@example.test', 'password' => 'x', 'crm_approved' => 1],
        ]);

        DB::connection('mysql_website')->table('courses')->insert([
            'id' => 20,
            'title' => 'Partner Test Course',
            'regular_price' => '600.00',
            'sale_price' => '0.00',
        ]);

        DB::connection('mysql_crm')->table('partner_assigned_courses')->insert([
            'id' => 30,
            'partner_id' => 10,
            'course_id' => 20,
            'discount_type' => 'percentage',
            'discount_value' => '20.00',
            'allow_full_payment' => 1,
            'allow_two_months' => 1,
            'allow_three_months' => 1,
            'allow_installments' => 0,
            'status' => 'active',
        ]);

        $partner = User::findOrFail(10);
        $response = $this->actingAs($partner)->post('/partner/learners/11/enrol/20/plan', [
            'plan_type' => 'two_months',
            'course_price' => '0.01',
            'discount_value' => '99.99',
            'installment_amounts' => ['0.01', '0.01'],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('partner.learners.show', 11));

        $this->assertDatabaseHas('orders', [
            'amount' => '240.00',
            'plan_full_amount' => '480.00',
            'plan_deposit_amount' => '240.00',
            'plan_months' => 1,
            'payment_mode' => 'installment',
        ], 'mysql_crm');

        $this->assertDatabaseCount('partner_learner_installments', 2, 'mysql_crm');
        $this->assertDatabaseHas('partner_learner_installments', ['installment_no' => 0, 'installment_amount' => '240.00', 'total_amount' => '480.00'], 'mysql_crm');
        $this->assertDatabaseHas('partner_learner_installments', ['installment_no' => 1, 'installment_amount' => '240.00', 'total_amount' => '480.00'], 'mysql_crm');

        $snapshot = DB::connection('mysql_crm')->table('enrolment_pricing_snapshots')->first();
        $snapshotJson = json_decode($snapshot->snapshot_json, true);

        $this->assertSame('600.00', $snapshotJson['original_course_fee']);
        $this->assertSame('percentage', $snapshotJson['partner_discount_type']);
        $this->assertSame('20.00', $snapshotJson['partner_discount_value']);
        $this->assertSame('120.00', $snapshotJson['partner_discount_amount']);
        $this->assertSame('480.00', $snapshotJson['final_course_fee']);
        $this->assertSame('two_months', $snapshotJson['selected_payment_plan']);
        $this->assertSame(['240.00', '240.00'], $snapshotJson['installment_amounts']);
        $this->assertSame(30, $snapshotJson['partner_course_assignment_id']);

        DB::connection('mysql_website')->table('courses')->where('id', 20)->update(['regular_price' => '900.00']);
        DB::connection('mysql_crm')->table('partner_assigned_courses')->where('id', 30)->update(['discount_value' => '50.00']);

        $unchanged = json_decode(DB::connection('mysql_crm')->table('enrolment_pricing_snapshots')->value('snapshot_json'), true);
        $this->assertSame('480.00', $unchanged['final_course_fee']);
        $this->assertSame('20.00', $unchanged['partner_discount_value']);
    }

    private function createPortalSchema(): void
    {
        Schema::connection('mysql_portal')->create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id')->default(0);
            $table->string('first_name')->nullable();
            $table->string('sur_name')->nullable();
            $table->string('email_address')->nullable();
            $table->string('password')->nullable();
            $table->boolean('crm_approved')->default(false);
            $table->string('stripe_customer_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function createWebsiteSchema(): void
    {
        Schema::connection('mysql_website')->create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('regular_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    private function createCrmSchema(): void
    {
        Schema::connection('mysql_crm')->create('partner_assigned_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('course_id');
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->boolean('allow_full_payment')->default(true);
            $table->boolean('allow_two_months')->default(false);
            $table->boolean('allow_three_months')->default(false);
            $table->boolean('allow_installments')->default(false);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_crm')->create('enrolment_status', function (Blueprint $table) {
            $table->id();
            $table->string('status')->unique();
            $table->timestamps();
        });

        Schema::connection('mysql_crm')->create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('partner_learner_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql_crm')->create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('partner_learner_id')->nullable();
            $table->unsignedBigInteger('enrolment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_mode')->nullable();
            $table->decimal('plan_deposit_amount', 10, 2)->nullable();
            $table->integer('plan_months')->nullable();
            $table->decimal('plan_monthly_amount', 10, 2)->nullable();
            $table->decimal('plan_full_amount', 10, 2)->nullable();
            $table->string('plan_title')->nullable();
            $table->json('plan_meta')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->date('deposit_grace_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql_crm')->create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
        });

        Schema::connection('mysql_crm')->create('order_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->integer('installment_no')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql_crm')->create('partner_learner_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('learner_id');
            $table->unsignedBigInteger('enrolment_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('course_id');
            $table->string('plan_type');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->decimal('installment_amount', 10, 2);
            $table->integer('installments_count');
            $table->integer('installment_no');
            $table->date('due_date');
            $table->string('status')->default('pending');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::connection('mysql_crm')->create('enrolment_pricing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('enrolment_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('pricing_plan_id')->nullable();
            $table->integer('pricing_plan_version')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->decimal('regular_fee', 10, 2)->default(0);
            $table->decimal('discounted_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('initial_deposit', 10, 2)->default(0);
            $table->integer('installment_months')->default(0);
            $table->decimal('installment_amount', 10, 2)->default(0);
            $table->decimal('total_payable', 10, 2)->default(0);
            $table->string('selected_plan_name')->nullable();
            $table->string('selected_plan_type')->nullable();
            $table->timestamps();
        });
    }
}
