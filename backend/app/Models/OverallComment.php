<?php
declare(strict_types=1);

namespace App\Models;

use App\Events\OverallCommentAdded;
use App\Events\OverallCommentReplyAdded;
use App\Http\Traits\CreatedUpdatedBy;
use App\Models\Traits\ReadStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverallComment extends BaseModel
{
    use HasFactory;
    use CreatedUpdatedBy;
    use SoftDeletes;
    use ReadStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'submission_id',
        'content',
        'reply_to_id',
        'parent_id',
    ];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::created(function ($overallComment) {
            if ($overallComment->parent_id) {
                OverallCommentReplyAdded::dispatch($overallComment);
            } else {
                OverallCommentAdded::dispatch($overallComment);
            }
        });
    }

    /**
     * The submission that owns the overall comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * The replies to an overall comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies(): HasMany
    {
        $thread_replies = $this->hasMany(OverallComment::class, 'parent_id');
        if ($thread_replies->count() > 0) {
            return $thread_replies;
        } else {
            return $this->hasMany(OverallComment::class, 'reply_to_id');
        }
    }

    /**
     * All commentors involved in the overall comment thread:
     * - anyone who has replied to the parent overall comment or its replies
     * - does not include the creator of the parent overall comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function commentors(): HasManyThrough
    {
        $parentComment = $this->parent_id ? $this->parent : $this;

        // Get all users who have replied to the parent overall comment
        return $this->hasManyThrough(
            User::class,
            OverallComment::class,
            'parent_id', // Foreign key on OverallComment table
            'id', // Foreign key on User table
            'id', // Local key on this table
            'created_by' // Local key on OverallComment table
        )->where('parent_id', $parentComment->id);
    }

    /**
     * The creator of the overall comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The updater of the overall comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The parent inline comment of this inline comment reply
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OverallComment::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            get: fn (int $value) => $this->trashed() ? '' : $value,
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $this->trashed() ? 'This comment has been deleted' : $value,
        );
    }
}
