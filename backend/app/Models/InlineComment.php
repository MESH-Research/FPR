<?php
declare(strict_types=1);

namespace App\Models;

use App\Events\InlineCommentAdded;
use App\Events\InlineCommentReplyAdded;
use App\Http\Traits\CreatedUpdatedBy;
use App\Models\Traits\ReadStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class InlineComment extends BaseModel
{
    use HasFactory;
    use CreatedUpdatedBy;
    use SoftDeletes;
    use ReadStatus;

    protected $casts = [
        'style_criteria' => 'json',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'submission_id',
        'content',
        'style_criteria',
        'parent_id',
        'read_at',
        'reply_to_id',
        'from',
        'to',
    ];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::created(function ($inlineComment) {
            if ($inlineComment->parent_id) {
                InlineCommentReplyAdded::dispatch($inlineComment);
            } else {
                InlineCommentAdded::dispatch($inlineComment);
            }
        });
    }

    /**
     * The submission that owns the inline comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * The replies to an inline comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies(): HasMany
    {
        $thread_replies = $this->hasMany(InlineComment::class, 'parent_id');
        if ($thread_replies->count() > 0) {
            return $thread_replies;
        } else {
            return $this->hasMany(InlineComment::class, 'reply_to_id');
        }
    }

    /**
     * All commentors involved in the inline comment thread:
     * - anyone who has replied to the parent inline comment or its replies
     * - does not include the creator of the parent inline comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function commentors(): HasManyThrough
    {
        $parentComment = $this->parent_id ? $this->parent : $this;

        // Get all users who have replied to the parent inline comment
        return $this->hasManyThrough(
            User::class,
            InlineComment::class,
            'parent_id', // Foreign key on InlineComment table
            'id', // Foreign key on User table
            'id', // Local key on this table
            'created_by' // Local key on InlineComment table
        )->where('parent_id', $parentComment->id);
    }

    /**
     * The creator of the inline comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The updater of the inline comment
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
        return $this->belongsTo(InlineComment::class, 'parent_id');
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

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function styleCriteria(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($this->reply_to_id) {
                    return [];
                }

                return $this->trashed() ? [] : json_decode($attributes['style_criteria']);
            }
        );
    }
}
