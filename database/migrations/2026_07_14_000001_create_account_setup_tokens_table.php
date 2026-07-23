<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')->create('account_setup_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email')->index();
            $table->string('token_hash')->unique();
            $table->boolean('legacy')->default(false);
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        $this->migrateRecoverableLegacySetupTokens();
        $this->revokeDuplicateActiveTokens();
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->dropIfExists('account_setup_tokens');
    }

    private function migrateRecoverableLegacySetupTokens(): void
    {
        if (! Schema::connection('mysql_portal')->hasTable('users')) {
            return;
        }

        $hasPasswordSetAt = Schema::connection('mysql_portal')->hasColumn('users', 'password_set_at');
        $now = now();

        if (Schema::connection('mysql_portal')->hasTable('learner_activations')) {
            DB::connection('mysql_portal')->table('learner_activations')
                ->orderBy('email')
                ->chunk(200, function ($activations) use ($hasPasswordSetAt, $now) {
                    foreach ($activations as $activation) {
                        $user = $this->unsetupUserByEmail($activation->email, $hasPasswordSetAt);
                        if (! $user || empty($activation->token)) {
                            continue;
                        }

                        DB::connection('mysql_portal')->table('account_setup_tokens')->updateOrInsert(
                            ['token_hash' => hash('sha256', $activation->token)],
                            [
                                'user_id' => $user->id,
                                'email' => $user->email_address,
                                'legacy' => false,
                                'created_at' => $activation->created_at ?? $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                });

            DB::connection('mysql_portal')->table('learner_activations')->delete();
        }

        if (Schema::connection('mysql_portal')->hasTable('password_reset_tokens')) {
            DB::connection('mysql_portal')->table('password_reset_tokens')
                ->orderBy('email')
                ->chunk(200, function ($tokens) use ($hasPasswordSetAt, $now) {
                    foreach ($tokens as $token) {
                        $user = $this->unsetupUserByEmail($token->email, $hasPasswordSetAt);
                        if (! $user || empty($token->token)) {
                            continue;
                        }

                        DB::connection('mysql_portal')->table('account_setup_tokens')->updateOrInsert(
                            ['token_hash' => $token->token],
                            [
                                'user_id' => $user->id,
                                'email' => $user->email_address,
                                'legacy' => true,
                                'created_at' => $token->created_at ?? $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                });
        }
    }

    private function unsetupUserByEmail(?string $email, bool $hasPasswordSetAt): ?object
    {
        if (! $email) {
            return null;
        }

        $query = DB::connection('mysql_portal')->table('users')
            ->where('email_address', $email);

        if ($hasPasswordSetAt) {
            $query->whereNull('password_set_at');
        }

        return $query->first();
    }

    private function revokeDuplicateActiveTokens(): void
    {
        $userIds = DB::connection('mysql_portal')->table('account_setup_tokens')
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $tokens = DB::connection('mysql_portal')->table('account_setup_tokens')
                ->where('user_id', $userId)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->pluck('id');

            $keep = $tokens->shift();
            if ($keep && $tokens->isNotEmpty()) {
                DB::connection('mysql_portal')->table('account_setup_tokens')
                    ->whereIn('id', $tokens->all())
                    ->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
};
