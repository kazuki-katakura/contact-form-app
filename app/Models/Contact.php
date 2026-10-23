<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Contact extends Model
{
    use HasFactory;

    public const GENDER_MALE = 1;

    public const GENDER_FEMALE = 2;

    public const GENDER_OTHER = 3;

    public const GENDERS = [
        self::GENDER_MALE => '男性',
        self::GENDER_FEMALE => '女性',
        self::GENDER_OTHER => 'その他',
    ];

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'gender' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getGenderLabelAttribute(): string
    {
        return self::GENDERS[$this->gender] ?? '';
    }

    /**
     * 検索条件（keyword / gender / category_id / date）で絞り込む。
     * gender が 0 または未指定の場合は性別で絞り込まない。
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            foreach (preg_split('/[\s　]+/u', $keyword) as $term) {
                $query->where(function (Builder $query) use ($term) {
                    $query->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            }
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        return $query;
    }

    /**
     * 新着順（作成日時の降順、同時刻はID降順）に並べる。
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->latest()->latest('id');
    }

    public static function createWithTags(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $contact = self::create(Arr::except($attributes, ['tag_ids']));
            $contact->tags()->attach($attributes['tag_ids'] ?? []);

            return $contact;
        });
    }

    public function updateWithTags(array $attributes): self
    {
        DB::transaction(function () use ($attributes) {
            $this->update(Arr::except($attributes, ['tag_ids']));
            $this->tags()->sync($attributes['tag_ids'] ?? []);
        });

        return $this;
    }
}
