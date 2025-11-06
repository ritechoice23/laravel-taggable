<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ritechoice23\Taggable\Models\Tag;
use Ritechoice23\Taggable\Traits\HasTags;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

test('it handles empty array when tagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag([]);

    expect($model->tags)->toHaveCount(0);
});

test('it handles empty array when untagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->untag([]);

    expect($model->fresh()->tags)->toHaveCount(2);
});

test('it handles empty array when retagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->retag([]);

    expect($model->fresh()->tags)->toHaveCount(0);
});

test('it handles null parameter when tagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(null);

    expect($model->tags)->toHaveCount(0);
});

test('it handles null parameter when untagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->untag(null);

    expect($model->fresh()->tags)->toHaveCount(2);
});

test('it handles null parameter when retagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->retag(null);

    expect($model->fresh()->tags)->toHaveCount(0);
});

test('it handles whitespace in tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['  laravel  ', ' php ']);

    $tags = $model->tags->pluck('name')->toArray();
    expect($tags)->toContain('laravel')
        ->toContain('php');
});

test('it handles mixed case tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['Laravel', 'PHP', 'javascript']);

    $tags = $model->tags->pluck('name')->toArray();
    expect($tags)->toContain('laravel')
        ->toContain('php')
        ->toContain('javascript');
});

test('it handles duplicate tag names in array', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['laravel', 'laravel', 'php', 'php']);

    expect($model->tags)->toHaveCount(2);
});

test('it handles very long tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $longTagName = str_repeat('a', 100);

    $model->tag($longTagName);

    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toHaveLength(100);
});

test('it handles special characters in tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['tag-with-dash', 'tag_with_underscore', 'tag.with.dot']);

    expect($model->tags)->toHaveCount(3);
});

test('it handles unicode characters in tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['🚀', 'café', '测试']);

    expect($model->tags)->toHaveCount(3);
});

test('it handles numeric tag names', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag(['123', '456.78']);

    expect($model->tags)->toHaveCount(2);
});

test('it handles boolean values when tagging', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);

    $model->tag([true, false, 0, 1]);

    // Should convert to strings
    $tagNames = $model->tags->pluck('name')->toArray();
    expect($tagNames)->toContain('1')
        ->toContain('0');
});

test('it handles mass untagging with non-existent tags', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->untag(['laravel', 'non-existent', 'also-fake']);

    expect($model->fresh()->tags)->toHaveCount(1)
        ->and($model->fresh()->tags->first()->name)->toBe('php');
});

test('it handles tag operations on unsaved model', function () {
    $model = new EdgeCaseTestModel(['name' => 'Test']);

    expect(fn () => $model->tag('laravel'))->toThrow(\Exception::class);
});

test('it handles concurrent tag operations', function () {
    $model1 = EdgeCaseTestModel::create(['name' => 'Model 1']);
    $model2 = EdgeCaseTestModel::create(['name' => 'Model 2']);

    // Both models tag the same tag simultaneously
    $model1->tag('shared-tag');
    $model2->tag('shared-tag');

    $sharedTag = Tag::where('name', 'shared-tag')->first();
    expect($sharedTag->usage_count)->toBe(2);
});

test('it handles tagging with mixed input types', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'existing-tag', 'usage_count' => 0]);

    $model->tag(['string-tag', $tag->id, $tag]);

    expect($model->tags)->toHaveCount(2);
});

test('it handles untagging with mixed input types', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $tag1 = Tag::create(['name' => 'tag1', 'usage_count' => 0]);
    $tag2 = Tag::create(['name' => 'tag2', 'usage_count' => 0]);

    $model->tag([$tag1, $tag2, 'tag3']);

    expect($model->tags)->toHaveCount(3);

    $model->untag(['tag1', $tag2->id, $tag2]);

    expect($model->fresh()->tags)->toHaveCount(1)
        ->and($model->fresh()->tags->first()->name)->toBe('tag3');
});

test('it maintains usage count consistency during edge case operations', function () {
    $model = EdgeCaseTestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'test-tag', 'usage_count' => 5]);

    // Tag and untag multiple times
    $model->tag('test-tag');
    expect($tag->fresh()->usage_count)->toBe(6);

    $model->untag('test-tag');
    expect($tag->fresh()->usage_count)->toBe(5);

    $model->tag('test-tag');
    expect($tag->fresh()->usage_count)->toBe(6);

    $model->retag(['other-tag']);
    expect($tag->fresh()->usage_count)->toBe(5);
});

class EdgeCaseTestModel extends Model
{
    use HasTags;

    protected $table = 'test_models';

    protected $fillable = ['name'];
}
