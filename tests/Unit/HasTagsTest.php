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

test('it can attach tags using tag method', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->tag('laravel');

    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toBe('laravel');
});

test('it can attach multiple tags at once', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->tag(['laravel', 'php', 'testing']);

    expect($model->tags)->toHaveCount(3);
});

test('it can detach tags using untag method', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->untag('laravel');

    expect($model->fresh()->tags)->toHaveCount(1)
        ->and($model->fresh()->tags->first()->name)->toBe('php');
});

test('it can detach all tags using untag all method', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php', 'testing']);

    $model->untagAll();

    expect($model->fresh()->tags)->toHaveCount(0);
});

test('it can retag model replacing all tags', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    $model->retag(['vue', 'javascript']);

    $tags = $model->fresh()->tags->pluck('name')->toArray();
    expect($tags)->toHaveCount(2)
        ->toContain('vue')
        ->toContain('javascript')
        ->not->toContain('laravel')
        ->not->toContain('php');
});

test('it auto creates tags when attaching by name', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect(Tag::all())->toHaveCount(0);

    $model->tag('new-tag');

    expect(Tag::all())->toHaveCount(1)
        ->and(Tag::first()->name)->toBe('new-tag');
});

test('it can attach tags by id', function () {
    $model = TestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);

    $model->tag($tag->id);

    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toBe('laravel');
});

test('it can attach tags using tag model', function () {
    $model = TestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);

    $model->tag($tag);

    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toBe('laravel');
});

test('it increments usage count when attaching', function () {
    $model = TestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 5, 'trending_score' => 0]);

    $model->tag($tag);

    expect($tag->fresh()->usage_count)->toBe(6);
});

test('it decrements usage count when detaching', function () {
    $model = TestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 5, 'trending_score' => 0]);
    $model->tag($tag);

    $model->untag($tag);

    expect($tag->fresh()->usage_count)->toBe(5);
});

test('it does not attach duplicate tags', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->tag('laravel');
    $model->tag('laravel');

    expect($model->fresh()->tags)->toHaveCount(1);
});

test('it updates tag timestamps when attaching', function () {
    $model = TestModel::create(['name' => 'Test']);
    $tag = Tag::create(['name' => 'laravel', 'usage_count' => 0, 'trending_score' => 0]);
    $oldTimestamp = $tag->updated_at;

    sleep(1);
    $model->tag($tag);

    expect($tag->fresh()->updated_at)->not->toBe($oldTimestamp);
});

test('it can check if model has tag', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag('laravel');

    expect($model->hasTag('laravel'))->toBeTrue()
        ->and($model->hasTag('php'))->toBeFalse();
});

test('it can check if model has any tag', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php']);

    expect($model->hasAnyTag(['laravel', 'vue']))->toBeTrue()
        ->and($model->hasAnyTag(['javascript', 'vue']))->toBeFalse();
});

test('it can check if model has all tags', function () {
    $model = TestModel::create(['name' => 'Test']);
    $model->tag(['laravel', 'php', 'testing']);

    expect($model->hasAllTags(['laravel', 'php']))->toBeTrue()
        ->and($model->hasAllTags(['laravel', 'vue']))->toBeFalse();
});

test('it can scope models with tag', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->tag('laravel');
    $model2->tag('php');

    $results = TestModel::withTag('laravel')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Model 1');
});

test('it can scope models with any tag', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);
    $model3 = TestModel::create(['name' => 'Model 3']);

    $model1->tag('laravel');
    $model2->tag('php');
    $model3->tag('vue');

    $results = TestModel::withAnyTag(['laravel', 'php'])->get();

    expect($results)->toHaveCount(2);
});

test('it can scope models with all tags', function () {
    $model1 = TestModel::create(['name' => 'Model 1']);
    $model2 = TestModel::create(['name' => 'Model 2']);

    $model1->tag(['laravel', 'php']);
    $model2->tag('laravel');

    $results = TestModel::withAllTags(['laravel', 'php'])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Model 1');
});

class TestModel extends Model
{
    use HasTags;

    protected $fillable = ['name'];
}
