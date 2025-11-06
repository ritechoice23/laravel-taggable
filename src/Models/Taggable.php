<?php

namespace Ritechoice23\Taggable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Taggable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    protected function casts(): array
    {
        return [
            'tag_id' => 'integer',
            'taggable_id' => 'integer',
            'taggable_type' => 'string',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForTag($query, $tagId)
    {
        return $query->where('tag_id', $tagId);
    }

    public function scopeForTaggableType($query, $type)
    {
        return $query->where('taggable_type', $type);
    }

    public function scopeForTaggable($query, $taggableId, $taggableType)
    {
        return $query->where('taggable_id', $taggableId)
            ->where('taggable_type', $taggableType);
    }
}
