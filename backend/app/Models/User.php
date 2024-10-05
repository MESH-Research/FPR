<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRoles;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'profile_metadata',
        'staged',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'profile_metadata' => 'array',
    ];

    /**
     * Model booted
     *
     * Clear email_verified_at and trigger a new verification notification if email field is updated.
     *
     * @return void
     */
    public static function booted(): void
    {
        static::updated(function ($model) {
            if ($model->isDirty('email')) {
                $model->sendEmailVerificationNotification();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('email')) {
                $model->email_verified_at = null;
            }
        });
    }

    /**
     * Lowercase email before saving to persistance.
     *
     * @param string $value
     * @return void
     */
    public function setEmailAttribute(?string $value): void
    {
        $email = $value ? strtolower($value) : $value;
        $this->attributes['email'] = $email;
    }

    /**
     * Returns a hash to use for email verification
     *
     * @param string $expires Timestamp
     * @return string
     */
    public function makeEmailVerificationHash(string $expires): string
    {
        return hash_hmac(
            'sha256',
            "{$this->getKey()}#{$this->getEmailForVerification()}#{$expires}",
            Config::get('app.key')
        );
    }

    /**
     * Return URL for email verification
     *
     * @return string
     */
    public function getEmailVerificationUrl(): string
    {
        $expires = (string)Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60))->timestamp;
        $hash = $this->makeEmailVerificationHash($expires);

        return url("verify-email/{$expires}/{$hash}");
    }

    /**
     * Verify an email verification token.
     *
     * @param string $token Token to validate
     * @param string $expires Supplied expiration for token
     * @return bool
     */
    public function verifyEmailToken(string $token, string $expires): bool
    {
        return hash_equals($this->makeEmailVerificationHash($expires), $token);
    }

    /**
     * Get the indexable data array for the model
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this
            ->withoutRelations()
            ->setVisible([
                'id',
                'username',
                'name',
                'email',
            ])
            ->toArray();
    }

    /**
     * Submissions that belong to the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function submissions(): BelongsToMany
    {
        return $this->belongsToMany(Submission::class)
            ->withTimestamps()
            ->withPivot(['id', 'user_id', 'role_id', 'submission_id']);
    }

    /**
     * Publications that belong to the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class)
            ->withTimestamps()
            ->withPivot('role_id');
    }

    /**
     * External Identity Providers that belong to the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function externalIdentityProviders(): HasMany
    {
        return $this->hasMany(ExternalIdentityProvider::class);
    }

    /**
     * Return the highest privileged role ID for the user in the following order:
     * 1. Application Administrator
     * 2. Publication Administrator
     * 3. Editor
     * 4. Review Coordinator
     * 5. Reviewer
     * 6. Submitter
     *
     * @return int|null
     */
    public function getHighestPrivilegedRole(): int|null
    {
        if ($this->hasRole(Role::APPLICATION_ADMINISTRATOR)) {
            return (int)Role::APPLICATION_ADMINISTRATOR_ROLE_ID;
        }
        if ($this->publications->isNotEmpty()) {
            return PublicationUser::where('user_id', $this->id)->min('role_id');
        }
        if ($this->submissions->isNotEmpty()) {
            return SubmissionUser::where('user_id', $this->id)->min('role_id');
        }

        return null;
    }

    /**
     * Check if user has a role for a publication
     *
     * @param int | array $role Role id to check, use * to check for any role.
     * @param int $publicationId Publication to check for role on
     * @return bool
     */
    public function hasPublicationRole($role, $publicationId)
    {
        $publications = $this->publications()->wherePivot('publication_id', $publicationId);

        if ($role === '*') {
            return $publications->exists();
        }

        if (is_array($role)) {
            return $publications->wherePivotIn('role_id', $role)->exists();
        } else {
            return $publications->wherePivot('role_id', $role)->exists();
        }
    }

    /**
     * Check if user has given submission role
     *
     * @param int|array $role Role id to check
     * @param int $submissionId Submission to check for role on
     * @return bool
     */
    public function hasSubmissionRole($role, $submissionId)
    {
        $submissions = $this->submissions()->wherePivot('submission_id', $submissionId);

        if ($role === '*') {
            return $submissions->exists();
        }

        if (is_array($role)) {
            return $submissions->wherePivotIn('role_id', $role)->exists();
        } else {
            return $submissions->wherePivot('role_id', null, $role)->exists();
        }
    }

    /**
     * Create a staged user based on a supplied email and
     * assign a default username and password
     *
     * @param string $email
     * @return \App\Models\User
     */
    public static function createStagedUser(string $email)
    {
        return User::create([
            'username' => User::generateUniqueUsername($email),
            'email' => $email,
            'password' => Hash::make(bin2hex(random_bytes(30))),
            'staged' => 1,
        ]);
    }

    /**
     * Generate a username from an email address and ensure it
     * does not conflict with existing usernames in the application
     * by appending random text to the username when necessary
     *
     * @param string $email
     * @return String
     */
    public static function generateUniqueUsername(string $email)
    {
        if ($email === '') {
            $username = 'user';
        } else {
            $username = explode('@', $email)[0];
        }
        if (User::where('username', $username)->exists()) {
            $unique = $username . '_'
                . self::generateString(random_int(1, 2))
                . str_replace(['0', '1'], '2', (string)random_int(0, 50))
                . self::generateString(random_int(1, 2));
            $username = self::generateUniqueUsername($unique);
        }

        return $username;
    }

    /**
     * Generate a string of random and non-similar-looking characters
     *
     * @param int $length
     * @return string
     */
    private static function generateString($length = 1): string
    {
        $string = '';
        $no_similar_chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++) {
            $random_int = rand(0, strlen($no_similar_chars) - 1);
            $string .= $no_similar_chars[$random_int];
        }

        return $string;
    }

    /**
     * The identifiable label of the user meant for public display,
     * which prioritizes displaying the user's name over the username
     *
     * @return string
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->attributes['name'] ?: $this->attributes['username'];
    }

    public function scopeSearch(Builder $query, mixed $search): Builder
    {
        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }
        return $query;
    }
}
