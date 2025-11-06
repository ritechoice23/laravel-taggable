<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ritechoice23\Taggable\Models\Tag;

uses(RefreshDatabase::class);

test('it can set meta value', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->setMeta('color', '#FF2D20');

    expect($tag->fresh()->getMeta('color'))->toBe('#FF2D20');
});

test('it can set multiple meta values', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->setMeta('color', '#FF2D20');
    $tag->setMeta('description', 'A PHP framework');
    $tag->setMeta('icon', 'laravel-icon');

    $fresh = $tag->fresh();
    expect($fresh->getMeta('color'))->toBe('#FF2D20')
        ->and($fresh->getMeta('description'))->toBe('A PHP framework')
        ->and($fresh->getMeta('icon'))->toBe('laravel-icon');
});

test('it can get meta value with default', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    expect($tag->getMeta('color', '#000000'))->toBe('#000000')
        ->and($tag->getMeta('nonexistent'))->toBeNull();
});

test('it can remove meta key', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'meta' => [
            'color' => '#FF2D20',
            'description' => 'A PHP framework',
        ],
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->removeMeta('color');

    $fresh = $tag->fresh();
    expect($fresh->hasMeta('color'))->toBeFalse()
        ->and($fresh->hasMeta('description'))->toBeTrue();
});

test('it can check if meta key exists', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'meta' => [
            'color' => '#FF2D20',
        ],
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    expect($tag->hasMeta('color'))->toBeTrue()
        ->and($tag->hasMeta('description'))->toBeFalse();
});

test('it handles null meta gracefully', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    expect($tag->getMeta('color'))->toBeNull()
        ->and($tag->hasMeta('color'))->toBeFalse();

    $tag->setMeta('color', '#FF2D20');
    expect($tag->fresh()->getMeta('color'))->toBe('#FF2D20');
});

test('it can create tag with meta in create method', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'meta' => [
            'color' => '#FF2D20',
            'description' => 'A PHP framework',
            'icon' => 'laravel-icon',
        ],
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    expect($tag->getMeta('color'))->toBe('#FF2D20')
        ->and($tag->getMeta('description'))->toBe('A PHP framework')
        ->and($tag->getMeta('icon'))->toBe('laravel-icon');
});

test('it can update existing meta values', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'meta' => [
            'color' => '#FF2D20',
        ],
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->setMeta('color', '#000000');

    expect($tag->fresh()->getMeta('color'))->toBe('#000000');
});

test('removing non-existent meta key does not throw error', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->removeMeta('nonexistent');

    expect($tag->fresh()->hasMeta('nonexistent'))->toBeFalse();
});

test('meta can store various data types', function () {
    $tag = Tag::create([
        'name' => 'laravel',
        'usage_count' => 0,
        'trending_score' => 0,
    ]);

    $tag->setMeta('string', 'value');
    $tag->setMeta('number', 42);
    $tag->setMeta('boolean', true);
    $tag->setMeta('array', ['item1', 'item2']);
    $tag->setMeta('object', ['key' => 'value']);

    $fresh = $tag->fresh();
    expect($fresh->getMeta('string'))->toBe('value')
        ->and($fresh->getMeta('number'))->toBe(42)
        ->and($fresh->getMeta('boolean'))->toBe(true)
        ->and($fresh->getMeta('array'))->toBe(['item1', 'item2'])
        ->and($fresh->getMeta('object'))->toBe(['key' => 'value']);
});
