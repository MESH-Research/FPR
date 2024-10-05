<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CleanAdminHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publication extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'home_page_content',
        'new_submission_content',
        'is_publicly_visible',
        'is_accepting_submissions',
    ];

    protected $rules = [
        'name' => 'max:256|unique:publications,name|required',
        'is_publicly_visible' => 'boolean',
        'is_accepting_submissions' => 'boolean',
    ];

    protected $casts = [
        'home_page_content' => CleanAdminHtml::class,
        'new_submission_content' => CleanAdminHtml::class,
    ];

    /**
     * Mutator: Trim name attribute before persisting
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * Scope only publically visible publications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsPubliclyVisible($query)
    {
        return $query->where('is_publicly_visible', true);
    }

    /**
     * Scope only publications that are accepting submissions
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsAcceptingSubmissions($query)
    {
        return $query->where('is_accepting_submissions', true);
    }

    /**
     * Users that belong to a publication
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot(['id', 'user_id', 'role_id', 'publication_id']);
    }

    /**
     * Publication administrators relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function publicationAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivotValue('role_id', Role::PUBLICATION_ADMINISTRATOR_ROLE_ID);
    }

    /**
     * Editors relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivotValue('role_id', Role::EDITOR_ROLE_ID);
    }

    /**
     * Submissions that belong to a publication
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Style Criteria Relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function styleCriterias(): HasMany
    {
        return $this->hasMany(StyleCriteria::class);
    }

    /**
     * Return the currently logged in users role
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
     * Return the effective role of a user on a submission taking into account parent roles they may have.
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

        if ($user->hasRole(Role::APPLICATION_ADMINISTRATOR)) {
            return (int)Role::PUBLICATION_ADMINISTRATOR_ROLE_ID;
        }

        return $this->getMyRole();
    }

    public function scopeSearch(Builder $query, mixed $search): Builder
    {
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        return $query;
    }
}
