<?php
declare(strict_types=1);

namespace App\Models;

use App\Events\SubmissionStatusUpdated;
use App\Http\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class Submission extends Model implements Auditable
{
    use HasFactory;
    use CreatedUpdatedBy;
    use \OwenIt\Auditing\Auditable;

    public const DRAFT = 0;
    public const INITIALLY_SUBMITTED = 1;
    public const RESUBMISSION_REQUESTED = 2;
    public const RESUBMITTED = 3;
    public const AWAITING_REVIEW = 4;
    public const REJECTED = 5;
    public const ACCEPTED_AS_FINAL = 6;
    public const EXPIRED = 7;
    public const UNDER_REVIEW = 8;
    public const AWAITING_DECISION = 9;
    public const REVISION_REQUESTED = 10;
    public const ARCHIVED = 11;
    public const DELETED = 12;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($submission) {
            $changes = $submission->getChanges();
            SubmissionStatusUpdated::dispatchIf(array_key_exists('status', $changes), $submission);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'publication_id',
        'status',
        'status_change_comment',
        'content_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_name',
    ];

    /**
     * The publication that the submission belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'publication_id');
    }

    /** Users with reviewer role
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivotValue('role_id', Role::REVIEWER_ROLE_ID);
    }

    /**
     * Users with a review_coordinator role
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reviewCoordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivotValue('role_id', Role::REVIEW_COORDINATOR_ROLE_ID);
    }

    /**
     * Users with submitter role
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function submitters(): BelongsToMany
    {
         return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivotValue('role_id', Role::SUBMITTER_ROLE_ID);
    }

    /**
     * Users that belong to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot(['id', 'user_id', 'role_id', 'submission_id']);
    }

    /**
     * File uploads that belong to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class, 'submission_id');
    }

    /**
     * Primary content that belongs to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(SubmissionContent::class);
    }

    /**
     * Content history that belongs to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contentHistory(): HasMany
    {
        return $this->hasMany(SubmissionContent::class);
    }

    /**
     * Inline comments that belong to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inlineComments(): HasMany
    {
        return $this->hasMany(InlineComment::class)->whereNull('parent_id');
    }

    /**
     * Overall comments that belong to the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overallComments(): HasMany
    {
        return $this->hasMany(OverallComment::class)->whereNull('parent_id');
    }

    /**
     * User that created the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User that most recently updated the submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return string
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'DRAFT',
            1 => 'INITIALLY_SUBMITTED',
            2 => 'RESUBMISSION_REQUESTED',
            3 => 'RESUBMITTED',
            4 => 'AWAITING_REVIEW',
            5 => 'REJECTED',
            6 => 'ACCEPTED_AS_FINAL',
            7 => 'EXPIRED',
            8 => 'UNDER_REVIEW',
            9 => 'AWAITING_DECISION',
            10 => 'REVISION_REQUESTED',
            11 => 'ARCHIVED',
            12 => 'DELETED',
        ];

        return $statuses[(int)$this->status];
    }

    /**
     * Get the logged in users assigned role for this submission
     *
     * @return int|null
     */
    public function getMyRole(): int|null
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return $this->users()->wherePivot('user_id', $user->id)->first()->pivot->role_id ?? null;
    }

    /**
     * Get the logged in users role taking into account parent roles granted to the user
     *
     * @return int|null
     */
    public function getEffectiveRole(): int|null
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $publicationRole = $this->publication->getEffectiveRole();

        if ($publicationRole !== null) {
            return (int)Role::REVIEW_COORDINATOR_ROLE_ID;
        }

        return $this->getMyRole();
    }

    /**
     * Create a staged user and attach them as a reviewer to this submisison
     *
     * @param string $email
     * @return \App\Models\User|void
     */
    public function stageReviewer(string $email)
    {
        if (!$email) {
            return; // TODO: throw an error
        }
        $user = User::createStagedUser($email);
        $this->reviewers()->attach($user);

        return $user;
    }

    /**
     * Create a staged user and attach them as a review coordinator to this submisison
     *
     * @param string $email
     * @return \App\Models\User|void
     */
    public function stageReviewCoordinator(string $email)
    {
        if (!$email) {
            return; // TODO: throw an error
        }
        $user = User::createStagedUser($email);
        $this->reviewCoordinators()->attach($user);

        return $user;
    }
}
