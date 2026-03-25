<?php

namespace App\Core\Auth\Services;

use App\Core\Auth\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Str;

class AccessTokenService
{
    public function createForUser(User $user, string $name = 'frontend', ?int $ttlMinutes = null, array $metadata = []): string
    {
        $plainTextToken = Str::random(64);

        PersonalAccessToken::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => $ttlMinutes ? now()->addMinutes($ttlMinutes) : null,
            'metadata' => $metadata,
        ]);

        return $plainTextToken;
    }

    public function findValidToken(string $plainTextToken): ?PersonalAccessToken
    {
        $token = PersonalAccessToken::query()
            ->with('user')
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if ($token === null) {
            return null;
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return null;
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        return $token;
    }

    public function revokeCurrent(?PersonalAccessToken $token): void
    {
        if ($token !== null) {
            $token->delete();
        }
    }
}
