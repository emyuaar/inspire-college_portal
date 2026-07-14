<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AccountSetupTokenService
{
    public const STATUS_CREATED = 'created';
    public const STATUS_VALID = 'valid';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_ALREADY_SETUP = 'already_setup';

    public function issueForUser(User|int $user, ?int $createdBy = null): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $now = now();

        return DB::connection('mysql_portal')->transaction(function () use ($userId, $createdBy, $rawToken, $tokenHash, $now) {
            $portalUser = User::query()->whereKey($userId)->lockForUpdate()->first();

            if (! $portalUser) {
                return ['status' => self::STATUS_INVALID];
            }

            if ($this->hasCompletedSetup($portalUser)) {
                return ['status' => self::STATUS_ALREADY_SETUP, 'user' => $portalUser];
            }

            DB::connection('mysql_portal')->table('account_setup_tokens')
                ->where('user_id', $portalUser->id)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::connection('mysql_portal')->table('account_setup_tokens')->insert([
                'user_id' => $portalUser->id,
                'email' => $portalUser->email_address,
                'token_hash' => $tokenHash,
                'legacy' => false,
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::connection('mysql_portal')->hasTable('learner_activations')) {
                DB::connection('mysql_portal')->table('learner_activations')
                    ->where('email', $portalUser->email_address)
                    ->delete();
            }

            return [
                'status' => self::STATUS_CREATED,
                'token' => $rawToken,
                'user' => $portalUser,
            ];
        });
    }

    public function setupUrl(string $rawToken): string
    {
        return rtrim((string) env('LEARNER_PORTAL_URL', config('app.url')), '/') . '/activate/' . $rawToken;
    }

    public function inspect(string $rawToken, ?string $email = null): array
    {
        $match = $this->findToken($rawToken, $email);

        if (! $match) {
            return ['status' => self::STATUS_INVALID];
        }

        $token = $match['token'];
        $user = User::query()->find($token->user_id);

        if (! $user || ! $this->emailMatches($token->email, $user->email_address)) {
            return ['status' => self::STATUS_INVALID, 'token' => $token];
        }

        if ($email && ! $this->emailMatches($email, $user->email_address)) {
            return ['status' => self::STATUS_INVALID, 'token' => $token, 'user' => $user];
        }

        if ($token->used_at || $this->hasCompletedSetup($user)) {
            return ['status' => self::STATUS_ALREADY_SETUP, 'token' => $token, 'user' => $user];
        }

        if ($token->revoked_at) {
            return ['status' => self::STATUS_INVALID, 'token' => $token, 'user' => $user];
        }

        return ['status' => self::STATUS_VALID, 'token' => $token, 'user' => $user];
    }

    public function consume(string $rawToken, string $password, ?string $email = null): array
    {
        $inspection = $this->inspect($rawToken, $email);

        if ($inspection['status'] !== self::STATUS_VALID) {
            return $inspection;
        }

        return DB::connection('mysql_portal')->transaction(function () use ($inspection, $rawToken, $password, $email) {
            $now = now();
            $token = DB::connection('mysql_portal')->table('account_setup_tokens')
                ->where('id', $inspection['token']->id)
                ->lockForUpdate()
                ->first();

            if (! $token || ! $this->rawTokenMatches($rawToken, $token)) {
                return ['status' => self::STATUS_INVALID];
            }

            $user = User::query()->whereKey($token->user_id)->lockForUpdate()->first();

            if (! $user || ! $this->emailMatches($token->email, $user->email_address)) {
                return ['status' => self::STATUS_INVALID, 'token' => $token];
            }

            if ($email && ! $this->emailMatches($email, $user->email_address)) {
                return ['status' => self::STATUS_INVALID, 'token' => $token, 'user' => $user];
            }

            if ($token->used_at || $this->hasCompletedSetup($user)) {
                return ['status' => self::STATUS_ALREADY_SETUP, 'token' => $token, 'user' => $user];
            }

            if ($token->revoked_at) {
                return ['status' => self::STATUS_INVALID, 'token' => $token, 'user' => $user];
            }

            $user->password = Hash::make($password);
            $user->password_set_at = $now;
            if ((int) $user->status_id === 1) {
                $user->status_id = 2;
            }
            $user->save();

            DB::connection('mysql_portal')->table('account_setup_tokens')
                ->where('id', $token->id)
                ->update([
                    'used_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::connection('mysql_portal')->table('account_setup_tokens')
                ->where('user_id', $user->id)
                ->where('id', '!=', $token->id)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            return ['status' => self::STATUS_VALID, 'token' => $token, 'user' => $user->fresh()];
        });
    }

    private function findToken(string $rawToken, ?string $email): ?array
    {
        $token = DB::connection('mysql_portal')->table('account_setup_tokens')
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if ($token) {
            return ['token' => $token];
        }

        if (! $email) {
            return null;
        }

        $user = User::query()->where('email_address', $email)->first();
        if (! $user) {
            return null;
        }

        $legacyTokens = DB::connection('mysql_portal')->table('account_setup_tokens')
            ->where('user_id', $user->id)
            ->where('legacy', true)
            ->get();

        foreach ($legacyTokens as $legacyToken) {
            if (Hash::check($rawToken, $legacyToken->token_hash)) {
                return ['token' => $legacyToken];
            }
        }

        return null;
    }

    private function rawTokenMatches(string $rawToken, object $token): bool
    {
        if ((bool) $token->legacy) {
            return Hash::check($rawToken, $token->token_hash);
        }

        return hash_equals($token->token_hash, hash('sha256', $rawToken));
    }

    private function hasCompletedSetup(User $user): bool
    {
        return ! empty($user->password_set_at);
    }

    private function emailMatches(?string $left, ?string $right): bool
    {
        if ($left === null || $right === null) {
            return false;
        }

        return hash_equals(strtolower($left), strtolower($right));
    }
}
