<?php

namespace Ritechoice23\Taggable\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'meta' => $this->meta,
            'usage_count' => $this->usage_count,
            'trending_score' => $this->trending_score,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activity_summary' => $this->when(
                $request->has('include_activity'),
                fn () => $this->getActivitySummary()
            ),
            'recent_activity' => $this->when(
                $request->has('include_recent'),
                fn () => [
                    'daily' => $this->getRecentActivityCount(1),
                    'weekly' => $this->getRecentActivityCount(7),
                    'monthly' => $this->getRecentActivityCount(30),
                ]
            ),
            'growth_metrics' => $this->when(
                $request->has('include_growth'),
                fn () => [
                    'weekly_growth_rate' => $this->getGrowthRate(7),
                    'monthly_growth_rate' => $this->getGrowthRate(30),
                ]
            ),
        ];
    }
}
