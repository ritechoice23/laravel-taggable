<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ritechoice23\Taggable\Models\Tag;

uses(RefreshDatabase::class);

test('it calculates trending score as zero for unused tags', function () {
    $tag = Tag::create(['name' => 'Unused', 'usage_count' => 0, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBe(0.0);
});

test('it updates trending score for a tag', function () {
    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 50, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeGreaterThan(0);
});

test('it calculates trending scores for all tags', function () {
    Tag::create(['name' => 'laravel', 'usage_count' => 50, 'trending_score' => 0]);
    Tag::create(['name' => 'php', 'usage_count' => 30, 'trending_score' => 0]);
    Tag::create(['name' => 'vue', 'usage_count' => 0, 'trending_score' => 0]);

    Tag::calculateAllTrendingScores();

    $tags = Tag::all();
    expect($tags->where('name', 'laravel')->first()->trending_score)->toBeGreaterThan(0)
        ->and($tags->where('name', 'php')->first()->trending_score)->toBeGreaterThan(0)
        ->and($tags->where('name', 'vue')->first()->trending_score)->toBe(0.0);
});

test('it respects config weights for trending calculation', function () {
    config([
        'taggable.trending.weights' => [
            'volume' => 0.5,
            'recency' => 0.2,
            'velocity' => 0.2,
            'freshness' => 0.1,
        ],
    ]);

    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 100, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeNumeric();
});

test('it respects config time periods for trending calculation', function () {
    config([
        'taggable.trending.time_periods' => [
            'daily' => 2,
            'weekly' => 14,
            'monthly' => 60,
            'velocity_comparison' => 28,
        ],
    ]);

    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 50, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeNumeric();
});

test('it respects config scoring parameters', function () {
    config([
        'taggable.trending.scoring' => [
            'volume_normalization' => 100,
            'freshness_decay' => 3,
            'velocity_multiplier' => 25,
        ],
    ]);

    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 50, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeNumeric();
});

test('it caps trending score at 100', function () {
    config([
        'taggable.trending.momentum_bonuses' => [
            'daily_activity' => 5.0,
            'weekly_threshold_40' => 5.0,
            'weekly_threshold_60' => 5.0,
        ],
    ]);

    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 1000, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeLessThanOrEqual(100);
});

test('it updates tag timestamp when calculating trending score', function () {
    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 50, 'trending_score' => 0]);
    $oldTimestamp = $tag->updated_at;

    sleep(1);
    $tag->calculateTrendingScore();

    expect($tag->fresh()->updated_at)->not->toBe($oldTimestamp);
});

test('it uses default config when not set', function () {
    config(['taggable' => []]);

    $tag = Tag::create(['name' => 'Laravel', 'usage_count' => 50, 'trending_score' => 0]);

    $tag->calculateTrendingScore();

    expect($tag->fresh()->trending_score)->toBeNumeric()
        ->toBeGreaterThanOrEqual(0);
});
