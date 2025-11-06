<?php

namespace Ritechoice23\Taggable\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TagCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'popular_tags' => $this->getPopularTags(),
                'trending_tags' => $this->getTrendingTags(),
                'recently_active_tags' => $this->getRecentlyActiveTags(),
                'fastest_growing_tags' => $this->getFastestGrowingTags(),
                'analytics' => $this->getAnalytics(),
            ],
        ];
    }

    private function getPopularTags(): array
    {
        return $this->collection
            ->sortByDesc('usage_count')
            ->take(5)
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'usage_count' => $tag->usage_count,
                    'updated_at' => $tag->updated_at,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getTrendingTags(): array
    {
        return $this->collection
            ->sortByDesc('trending_score')
            ->take(5)
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'trending_score' => $tag->trending_score,
                    'usage_count' => $tag->usage_count,
                    'updated_at' => $tag->updated_at,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getRecentlyActiveTags(): array
    {
        return $this->collection
            ->sortByDesc('updated_at')
            ->take(5)
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'updated_at' => $tag->updated_at,
                    'recent_activity' => $tag->getRecentActivityCount(7),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getFastestGrowingTags(): array
    {
        return $this->collection
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'growth_rate' => $tag->getGrowthRate(7),
                    'usage_count' => $tag->usage_count,
                ];
            })
            ->sortByDesc('growth_rate')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function getAnalytics(): array
    {
        $totalTags = $this->collection->count();
        $totalTaggables = $this->collection->sum('usage_count');
        $avgTrendingScore = $this->collection->avg('trending_score');
        $recentlyActive = $this->collection->filter(function ($tag) {
            return $tag->updated_at >= now()->subDays(7);
        })->count();

        return [
            'total_tags' => $totalTags,
            'total_taggables' => $totalTaggables,
            'average_trending_score' => round($avgTrendingScore, 2),
            'recently_active_count' => $recentlyActive,
            'activity_rate' => $totalTags > 0 ? round(($recentlyActive / $totalTags) * 100, 2) : 0,
        ];
    }
}
