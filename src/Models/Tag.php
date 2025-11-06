<?php

namespace Ritechoice23\Taggable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Ritechoice23\Taggable\Database\Factories\TagFactory;

/**
 * Tag Model
 *
 * Represents a tag that can be attached to any taggable model.
 * Includes trending score calculation and usage tracking.
 *
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property array|null $meta
 * @property int $usage_count
 * @property float $trending_score
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'meta',
        'usage_count',
        'trending_score',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'usage_count' => 'integer',
            'trending_score' => 'float',
        ];
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tag) {
            $tag->name = strtolower(trim($tag->name));

            if (empty($tag->slug)) {
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->name = strtolower(trim($tag->name));
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });
    }

    public function taggables(): HasMany
    {
        return $this->hasMany(\Ritechoice23\Taggable\Models\Taggable::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function scopeWherePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeWhereTrending($query, $limit = 10)
    {
        return $query->orderBy('trending_score', 'desc')->limit($limit);
    }

    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'like', '%'.$name.'%');
    }

    public function scopeWhereSlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeWhereRecentlyActive($query, int $days = 7)
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    public function scopeWhereHighGrowth($query, int $days = 7)
    {
        return $query->whereHas('taggables', function ($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        }, '>', 0);
    }

    public function incrementCount(): void
    {
        $this->increment('usage_count');
    }

    public function decrementCount(): void
    {
        $this->decrement('usage_count');
    }

    public function updateCount(): void
    {
        $count = $this->taggables()->count();
        $this->update(['usage_count' => $count]);
    }

    public function getRecentActivityCount(int $days = 7): int
    {
        return $this->taggables()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function getGrowthRate(int $days = 7): float
    {
        $currentPeriod = $this->taggables()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        $previousPeriod = $this->taggables()
            ->where('created_at', '>=', now()->subDays($days * 2))
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        if ($previousPeriod === 0) {
            return $currentPeriod > 0 ? 100 : 0;
        }

        return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2);
    }

    public function getActivitySummary(): array
    {
        return [
            'total_count' => $this->usage_count,
            'trending_score' => $this->trending_score,
            'daily_activity' => $this->getRecentActivityCount(1),
            'weekly_activity' => $this->getRecentActivityCount(7),
            'monthly_activity' => $this->getRecentActivityCount(30),
            'weekly_growth_rate' => $this->getGrowthRate(7),
            'last_activity' => $this->updated_at,
        ];
    }

    public function setMeta(string $key, $value): self
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->meta = $meta;
        $this->save();

        return $this;
    }

    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta ?? [];

        return $meta[$key] ?? $default;
    }

    public function removeMeta(string $key): self
    {
        $meta = $this->meta ?? [];

        if (isset($meta[$key])) {
            unset($meta[$key]);
            $this->meta = $meta;
            $this->save();
        }

        return $this;
    }

    public function hasMeta(string $key): bool
    {
        $meta = $this->meta ?? [];

        return isset($meta[$key]);
    }

    public function generateUniqueSlug($name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function calculateTrendingScore(): void
    {
        $score = static::calculateScoreForTag($this);
        $this->update([
            'trending_score' => $score,
            'updated_at' => now(),
        ]);
    }

    public static function calculateAllTrendingScores(): void
    {
        $tags = static::all();

        foreach ($tags as $tag) {
            $tag->calculateTrendingScore();
        }
    }

    protected static function calculateScoreForTag(Tag $tag): float
    {
        $now = now();
        $currentCount = $tag->usage_count;

        if ($currentCount === 0) {
            return 0;
        }

        $config = config('taggable.trending', []);
        $weights = $config['weights'] ?? [
            'volume' => 0.25,
            'recency' => 0.30,
            'velocity' => 0.25,
            'freshness' => 0.20,
        ];
        $timePeriods = $config['time_periods'] ?? [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'velocity_comparison' => 14,
        ];
        $scoring = $config['scoring'] ?? [
            'volume_normalization' => 50,
            'freshness_decay' => 2,
            'velocity_multiplier' => 50,
        ];

        $dailyUsage = $tag->taggables()
            ->where('created_at', '>=', $now->copy()->subDays($timePeriods['daily']))
            ->count();

        $weeklyUsage = $tag->taggables()
            ->where('created_at', '>=', $now->copy()->subDays($timePeriods['weekly']))
            ->count();

        $monthlyUsage = $tag->taggables()
            ->where('created_at', '>=', $now->copy()->subDays($timePeriods['monthly']))
            ->count();

        $tagAge = $now->diffInDays($tag->updated_at);
        $freshnessScore = max(0, 100 - ($tagAge * $scoring['freshness_decay']));

        $velocityScore = static::calculateVelocityScore($tag, $now, $timePeriods, $scoring);

        $volumeScore = min(($currentCount / $scoring['volume_normalization']) * 100, 100);

        $recencyScore = $currentCount > 0
            ? (($weeklyUsage / $currentCount) * 100)
            : 0;

        $trendingScore = (
            ($volumeScore * $weights['volume']) +
            ($recencyScore * $weights['recency']) +
            ($velocityScore * $weights['velocity']) +
            ($freshnessScore * $weights['freshness'])
        );

        $trendingScore = static::applyMomentumBonuses($trendingScore, $dailyUsage, $weeklyUsage, $monthlyUsage);

        return round(min($trendingScore, 100), 2);
    }

    protected static function calculateVelocityScore(Tag $tag, $now, array $timePeriods, array $scoring): float
    {
        $weeklyPeriod = $timePeriods['weekly'] ?? 7;
        $velocityComparisonPeriod = $timePeriods['velocity_comparison'] ?? 14;
        $velocityMultiplier = $scoring['velocity_multiplier'] ?? 50;

        $currentWeekUsage = $tag->taggables()
            ->where('created_at', '>=', $now->copy()->subDays($weeklyPeriod))
            ->count();

        $previousWeekUsage = $tag->taggables()
            ->where('created_at', '>=', $now->copy()->subDays($velocityComparisonPeriod))
            ->where('created_at', '<', $now->copy()->subDays($weeklyPeriod))
            ->count();

        if ($previousWeekUsage === 0) {
            return $currentWeekUsage > 0 ? 100 : 0;
        }

        $velocityRatio = $currentWeekUsage / $previousWeekUsage;

        return min($velocityRatio * $velocityMultiplier, 100);
    }

    protected static function applyMomentumBonuses(float $score, int $daily, int $weekly, int $monthly): float
    {
        $config = config('taggable.trending.momentum_bonuses', []);
        $dailyBonus = $config['daily_activity'] ?? 1.1;
        $weeklyThreshold40Bonus = $config['weekly_threshold_40'] ?? 1.15;
        $weeklyThreshold60Bonus = $config['weekly_threshold_60'] ?? 1.2;

        if ($daily > 0) {
            $score *= $dailyBonus;
        }

        if ($weekly > $monthly * 0.4) {
            $score *= $weeklyThreshold40Bonus;
        }

        if ($weekly > $monthly * 0.6) {
            $score *= $weeklyThreshold60Bonus;
        }

        return $score;
    }
}
