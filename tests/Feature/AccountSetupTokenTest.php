<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSetupTokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountSetupTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['mysql_portal', 'mysql_crm'] as $connection) {
            config(["database.connections.{$connection}" => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            DB::purge($connection);
        }

        $this->createPortalSchema();
        $this->createCrmSchema();
    }

    public function test_partner_created_learner_gets_setup_token_that_does_not_expire_by_age(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);

        $issued = app(AccountSetupTokenService::class)->issueForUser($user);

        $this->assertSame(AccountSetupTokenService::STATUS_CREATED, $issued['status']);
        $this->assertDatabaseCount('account_setup_tokens', 1, 'mysql_portal');

        DB::connection('mysql_portal')->table('account_setup_tokens')->update([
            'created_at' => now()->subMonths(8),
            'updated_at' => now()->subMonths(8),
        ]);

        $inspection = app(AccountSetupTokenService::class)->inspect($issued['token'], $user->email_address);

        $this->assertSame(AccountSetupTokenService::STATUS_VALID, $inspection['status']);
    }

    public function test_learner_can_set_password_once_and_used_token_cannot_be_reused(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);
        $issued = app(AccountSetupTokenService::class)->issueForUser($user);

        $result = app(AccountSetupTokenService::class)->consume($issued['token'], 'StrongPass1!', $user->email_address);

        $this->assertSame(AccountSetupTokenService::STATUS_VALID, $result['status']);
        $this->assertTrue(Hash::check('StrongPass1!', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_set_at);
        $this->assertDatabaseHas('account_setup_tokens', [
            'user_id' => $user->id,
        ], 'mysql_portal');
        $this->assertNotNull(DB::connection('mysql_portal')->table('account_setup_tokens')->value('used_at'));

        $second = app(AccountSetupTokenService::class)->consume($issued['token'], 'AnotherPass1!', $user->email_address);

        $this->assertSame(AccountSetupTokenService::STATUS_ALREADY_SETUP, $second['status']);
        $this->assertTrue(Hash::check('StrongPass1!', $user->fresh()->password));
    }

    public function test_setup_form_saves_password_marks_token_used_and_reuse_shows_clear_message(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);
        $issued = app(AccountSetupTokenService::class)->issueForUser($user);

        $this->mock(\App\Services\MicrosoftGraphService::class, function ($mock) {
            $mock->shouldReceive('provisionLearner')->once();
        });

        $response = $this->post('/reset-password', [
            'token' => $issued['token'],
            'email' => $user->email_address,
            'flow' => 'account_setup',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHas('success', 'Your password has been set successfully. You can now log in.');
        $this->assertNotNull(DB::connection('mysql_portal')->table('account_setup_tokens')->value('used_at'));

        $reuse = $this->get('/activate/' . $issued['token']);

        $reuse->assertRedirect(route('portal.login'));
        $reuse->assertSessionHas('info', 'Your account has already been set up. Please sign in.');
    }

    public function test_generating_new_setup_token_revokes_previous_token(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);

        $first = app(AccountSetupTokenService::class)->issueForUser($user);
        $second = app(AccountSetupTokenService::class)->issueForUser($user);

        $this->assertSame(AccountSetupTokenService::STATUS_CREATED, $second['status']);
        $this->assertSame(AccountSetupTokenService::STATUS_INVALID, app(AccountSetupTokenService::class)->inspect($first['token'], $user->email_address)['status']);
        $this->assertSame(AccountSetupTokenService::STATUS_VALID, app(AccountSetupTokenService::class)->inspect($second['token'], $user->email_address)['status']);
        $this->assertSame(1, DB::connection('mysql_portal')->table('account_setup_tokens')->whereNull('used_at')->whereNull('revoked_at')->count());
    }

    public function test_revoked_token_and_wrong_email_token_combination_are_rejected(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);
        $issued = app(AccountSetupTokenService::class)->issueForUser($user);

        $this->assertSame(AccountSetupTokenService::STATUS_INVALID, app(AccountSetupTokenService::class)->inspect($issued['token'], 'wrong@example.test')['status']);

        DB::connection('mysql_portal')->table('account_setup_tokens')->update(['revoked_at' => now()]);

        $this->assertSame(AccountSetupTokenService::STATUS_INVALID, app(AccountSetupTokenService::class)->inspect($issued['token'], $user->email_address)['status']);
    }

    public function test_existing_learner_with_password_is_not_sent_initial_setup_link(): void
    {
        $user = $this->createLearner(['password_set_at' => now()]);

        $issued = app(AccountSetupTokenService::class)->issueForUser($user);

        $this->assertSame(AccountSetupTokenService::STATUS_ALREADY_SETUP, $issued['status']);
        $this->assertSame(0, DB::connection('mysql_portal')->table('account_setup_tokens')->count());
    }

    public function test_duplicate_setup_generation_leaves_only_one_active_token(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);

        app(AccountSetupTokenService::class)->issueForUser($user);
        app(AccountSetupTokenService::class)->issueForUser($user);
        app(AccountSetupTokenService::class)->issueForUser($user);

        $this->assertSame(1, DB::connection('mysql_portal')->table('account_setup_tokens')->whereNull('used_at')->whereNull('revoked_at')->count());
        $this->assertSame(2, DB::connection('mysql_portal')->table('account_setup_tokens')->whereNotNull('revoked_at')->count());
    }

    public function test_legacy_setup_token_from_password_reset_table_remains_valid_without_age_check(): void
    {
        $user = $this->createLearner(['password_set_at' => null]);
        $rawToken = 'legacy-setup-token';

        DB::connection('mysql_portal')->table('account_setup_tokens')->insert([
            'user_id' => $user->id,
            'email' => $user->email_address,
            'token_hash' => Hash::make($rawToken),
            'legacy' => true,
            'created_at' => now()->subMonths(4),
            'updated_at' => now()->subMonths(4),
        ]);

        $inspection = app(AccountSetupTokenService::class)->inspect($rawToken, $user->email_address);

        $this->assertSame(AccountSetupTokenService::STATUS_VALID, $inspection['status']);
    }

    public function test_normal_forgot_password_token_still_expires_by_config(): void
    {
        config(['auth.passwords.users.expire' => 60]);
        $user = $this->createLearner(['password_set_at' => now()]);
        $rawToken = 'normal-reset-token';

        DB::connection('mysql_portal')->table('password_reset_tokens')->insert([
            'email' => $user->email_address,
            'token' => Hash::make($rawToken),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->get('/reset-password/' . $rawToken . '?email=' . urlencode($user->email_address));

        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHas('error', 'This password reset link has expired.');
    }

    private function createLearner(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'org_id' => 10,
            'first_name' => 'Learner',
            'sur_name' => 'One',
            'email_address' => 'learner' . random_int(1000, 9999) . '@example.test',
            'password' => Hash::make('placeholder'),
            'status_id' => 1,
            'crm_approved' => 1,
            'password_set_at' => null,
        ], $overrides));
    }

    private function createPortalSchema(): void
    {
        Schema::connection('mysql_portal')->create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id')->default(0);
            $table->string('first_name')->nullable();
            $table->string('sur_name')->nullable();
            $table->string('email_address')->nullable()->unique();
            $table->string('password')->nullable();
            $table->unsignedInteger('status_id')->nullable();
            $table->boolean('crm_approved')->default(false);
            $table->timestamp('crm_approved_at')->nullable();
            $table->timestamp('password_set_at')->nullable();
            $table->string('ms_user_id')->nullable();
            $table->text('ms_error_message')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('mysql_portal')->create('account_setup_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email')->index();
            $table->string('token_hash')->unique();
            $table->boolean('legacy')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_portal')->create('learner_activations', function (Blueprint $table) {
            $table->string('token', 64)->primary();
            $table->string('email')->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('mysql_portal')->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createCrmSchema(): void
    {
        Schema::connection('mysql_crm')->create('partner_learners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('activation_status')->nullable();
            $table->string('account_status')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_crm')->create('user_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('contact')->nullable();
            $table->date('dob')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });
    }
}
