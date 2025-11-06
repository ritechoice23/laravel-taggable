<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ritechoice23\Taggable\Models\Tag;

uses(RefreshDatabase::class);

test('it auto generates slug on creation', function () {
    $tag = Tag::create([
        'name' => 'laravel framework',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    expect($tag->slug)->toBe('laravel-framework');
});

test('it generates unique slugs for duplicate names', function () {
    Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);
    $tag2 = Tag::create(['name' => 'laravel-framework', 'usage_count' => 0, 'trending_score' => 0]);

    expect($tag2->slug)->toBe('laravel-framework');
});

test('it updates slug when name changes', function () {
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);

    $tag->update(['name' => 'vue.js']);

    expect($tag->fresh()->slug)->toBe('vuejs');
});

test('it can find tags by slug', function () {
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);

    $found = Tag::whereSlug('laravel')->first();

    expect($found->id)->toBe($tag->id);
});

test('it can find tags by name', function () {
    Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);
    Tag::create(['name' => 'php', 'usage_count' => 0, 'trending_score' => 0]);

    $results = Tag::whereName('lar')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('laravel');
});

test('it can get popular tags', function () {
    Tag::create(['name' => 'laravel', 'usage_count' => 100, 'trending_score' => 0]);
    Tag::create(['name' => 'php', 'usage_count' => 50, 'trending_score' => 0]);
    Tag::create(['name' => 'vue', 'usage_count' => 75, 'trending_score' => 0]);

    $popular = Tag::wherePopular(2)->get();

    expect($popular)->toHaveCount(2)
        ->and($popular->first()->name)->toBe('laravel')
        ->and($popular->last()->name)->toBe('vue');
});

test('it can get trending tags', function () {
    Tag::create(['name' => 'laravel', 'usage_count' => 100, 'trending_score' => 50.5]);
    Tag::create(['name' => 'php', 'usage_count' => 50, 'trending_score' => 25.2]);
    Tag::create(['name' => 'vue', 'usage_count' => 75, 'trending_score' => 37.8]);

    $trending = Tag::whereTrending(2)->get();

    expect($trending)->toHaveCount(2)
        ->and($trending->first()->name)->toBe('laravel')
        ->and($trending->last()->name)->toBe('vue');
});

test('it uses slug as route key', function () {
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);

    expect($tag->getRouteKeyName())->toBe('slug');
});
