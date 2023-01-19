<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $array = $this->withoutRelations()->toArray([
            'username',
            'name',
            'email',
        ]);
        unset($array['profile_metadata']);

        return $array;
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
     * @return \App\Models\Role
     */
    public function getHighestPrivilegedRole()
    {
        return $this->roles->sortBy('id')->first();
    }

    /**
     * Check if user has given publication role
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
        $username = explode('@', $email)[0];
        if (User::where('username', $username)->exists()) {
            $unique = $username . '_' . Str::random(2) . random_int(0, 50);
            $username = self::generateUniqueUsername($unique);
        }

        return $username;
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
}
