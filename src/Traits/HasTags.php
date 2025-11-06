<?php

namespace Ritechoice23\Taggable\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ritechoice23\Taggable\Models\Tag;
use Ritechoice23\Taggable\Models\Taggable;

/**
 * HasTags Trait
 *
 * Provides tagging functionality to Eloquent models.
 * Use this trait on any model that needs to be tagged.
 */
trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withTimestamps();
    }

    public function taggables(): MorphMany
    {
        return $this->morphMany(Taggable::class, 'taggable');
    }

    public function tag($tags): void
    {
        $tagIds = $this->resolveToTagIds($tags);

        $currentTagIds = $this->tags()->pluck('tags.id')->toArray();
        $newTagIds = array_diff($tagIds, $currentTagIds);

        if (! empty($newTagIds)) {
            $this->tags()->attach($newTagIds);

            Tag::whereIn('id', $newTagIds)->increment('usage_count');
            Tag::whereIn('id', $newTagIds)->update(['updated_at' => now()]);
        }
    }

    public function untag($tags): void
    {
        $tagIds = $this->resolveToTagIds($tags);

        $currentTagIds = $this->tags()->pluck('tags.id')->toArray();
        $toDetachIds = array_intersect($tagIds, $currentTagIds);

        if (! empty($toDetachIds)) {
            $this->tags()->detach($toDetachIds);

            Tag::whereIn('id', $toDetachIds)->decrement('usage_count');
            Tag::whereIn('id', $toDetachIds)->update(['updated_at' => now()]);
        }
    }

    public function untagAll(): void
    {
        $currentTagIds = $this->tags()->pluck('tags.id')->toArray();

        if (! empty($currentTagIds)) {
            $this->tags()->detach();

            Tag::whereIn('id', $currentTagIds)->decrement('usage_count');
            Tag::whereIn('id', $currentTagIds)->update(['updated_at' => now()]);
        }
    }

    public function retag($tags): void
    {
        $tagIds = $this->resolveToTagIds($tags);
        $currentTagIds = $this->tags()->pluck('tags.id')->toArray();

        $toAttach = array_diff($tagIds, $currentTagIds);
        $toDetach = array_diff($currentTagIds, $tagIds);

        $this->tags()->sync($tagIds);

        if (! empty($toAttach)) {
            Tag::whereIn('id', $toAttach)->increment('usage_count');
        }
        if (! empty($toDetach)) {
            Tag::whereIn('id', $toDetach)->decrement('usage_count');
        }

        $affectedIds = array_merge($toAttach, $toDetach);
        if (! empty($affectedIds)) {
            Tag::whereIn('id', $affectedIds)->update(['updated_at' => now()]);
        }
    }

    public function hasTag($tag): bool
    {
        if (is_string($tag)) {
            return $this->tags()->where('tags.name', $tag)->exists() ||
                $this->tags()->where('tags.slug', $tag)->exists();
        }

        if (is_int($tag)) {
            return $this->tags()->where('tags.id', $tag)->exists();
        }

        if ($tag instanceof Tag) {
            return $this->tags()->where('tags.id', $tag->id)->exists();
        }

        return false;
    }

    public function hasAnyTag($tags): bool
    {
        if (is_string($tags) || is_int($tags) || $tags instanceof Tag) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            if ($this->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllTags($tags): bool
    {
        if (is_string($tags) || is_int($tags) || $tags instanceof Tag) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            if (! $this->hasTag($tag)) {
                return false;
            }
        }

        return true;
    }

    public function scopeWithTag($query, $tag)
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            if (is_string($tag)) {
                $q->where('tags.name', $tag)->orWhere('tags.slug', $tag);
            } elseif (is_int($tag)) {
                $q->where('tags.id', $tag);
            } elseif ($tag instanceof Tag) {
                $q->where('tags.id', $tag->id);
            }
        });
    }

    public function scopeWithAnyTag($query, $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            if (! is_array($tags)) {
                $tags = [$tags];
            }

            $tagIds = [];
            $tagNames = [];

            foreach ($tags as $tag) {
                if (is_string($tag)) {
                    $tagNames[] = $tag;
                } elseif (is_int($tag)) {
                    $tagIds[] = $tag;
                } elseif ($tag instanceof Tag) {
                    $tagIds[] = $tag->id;
                }
            }

            $q->where(function ($query) use ($tagIds, $tagNames) {
                if (! empty($tagIds)) {
                    $query->whereIn('tags.id', $tagIds);
                }
                if (! empty($tagNames)) {
                    $query->orWhereIn('tags.name', $tagNames)->orWhereIn('tags.slug', $tagNames);
                }
            });
        });
    }

    public function scopeWithAllTags($query, $tags)
    {
        if (! is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            $query->withTag($tag);
        }

        return $query;
    }

    private function resolveToTagIds($tags): array
    {
        if (! is_array($tags)) {
            $tags = [$tags];
        }

        $tagIds = [];
        $tagNames = [];

        foreach ($tags as $tag) {
            if (is_null($tag) || (is_string($tag) && trim($tag) === '')) {
                continue;
            } elseif (is_int($tag)) {
                $tagIds[] = $tag;
            } elseif (is_string($tag)) {
                $normalizedName = strtolower(trim($tag));
                if ($normalizedName !== '') {
                    $tagNames[] = $normalizedName;
                }
            } elseif ($tag instanceof Tag) {
                $tagIds[] = $tag->id;
            } elseif (is_bool($tag)) {
                $tagNames[] = $tag ? '1' : '0';
            } else {
                $normalizedName = strtolower(trim((string) $tag));
                if ($normalizedName !== '') {
                    $tagNames[] = $normalizedName;
                }
            }
        }

        if (! empty($tagNames)) {
            $tagNames = array_unique($tagNames);

            $foundByName = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
            $foundBySlug = Tag::whereIn('slug', $tagNames)->pluck('id', 'slug');

            foreach ($tagNames as $nameOrSlug) {
                if (isset($foundByName[$nameOrSlug])) {
                    $tagIds[] = $foundByName[$nameOrSlug];
                } elseif (isset($foundBySlug[$nameOrSlug])) {
                    $tagIds[] = $foundBySlug[$nameOrSlug];
                } else {
                    $newTag = Tag::create([
                        'name' => $nameOrSlug,
                        'usage_count' => 0,
                        'trending_score' => 0,
                    ]);
                    $tagIds[] = $newTag->id;
                }
            }
        }

        return array_unique($tagIds);
    }
}
