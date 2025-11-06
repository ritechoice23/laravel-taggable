# 🏷️ Laravel Taggable - The Developer's Choice

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ritechoice23/laravel-taggable.svg?style=flat-square)](https://packagist.org/packages/ritechoice23/laravel-taggable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ritechoice23/laravel-taggable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ritechoice23/laravel-taggable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ritechoice23/laravel-taggable/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ritechoice23/laravel-taggable/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ritechoice23/laravel-taggable.svg?style=flat-square)](https://packagist.org/packages/ritechoice23/laravel-taggable)

**The most intuitive and powerful Laravel tagging package that just works.** Add `$post->tag('laravel')` to any model and get intelligent tag normalization, trending analytics, powerful queries, and bulletproof edge case handling - all with zero configuration required.

## Installation

You can install the package via composer:

```bash
composer require ritechoice23/laravel-taggable
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-taggable-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-taggable-config"
```

The config file allows you to customize the trending score calculation:

```php
return [
    'trending' => [
        'weights' => [
            'volume' => 0.25,
            'recency' => 0.30,
            'velocity' => 0.25,
            'freshness' => 0.20,
        ],
        'time_periods' => [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'velocity_comparison' => 14,
        ],
        'scoring' => [
            'volume_normalization' => 50,
            'freshness_decay' => 2,
            'velocity_multiplier' => 50,
        ],
        'momentum_bonuses' => [
            'daily_activity' => 1.1,
            'weekly_threshold_40' => 1.15,
            'weekly_threshold_60' => 1.2,
        ],
    ],
];
```

Optionally, you can publish the views using

## Usage

### Add the Trait to Your Model

```php
use Ritechoice23\Taggable\Traits\HasTags;

class Post extends Model
{
    use HasTags;
}
```

### Tagging Models

The package provides a clean, intuitive API for managing tags using natural method names:

```php
$post = Post::find(1);

// Attach tags (accepts strings, IDs, or Tag models)
$post->tag('laravel');
$post->tag(['php', 'programming']);

// Detach tags
$post->untag('laravel');
$post->untag(['php', 'programming']);

// Detach all tags
$post->untagAll();

// Replace all existing tags (retag)
$post->retag(['laravel', 'php']);
```

### Checking Tags

```php
// Check if model has a specific tag
if ($post->hasTag('laravel')) {
    // ...
}

// Check if model has any of the given tags
if ($post->hasAnyTag(['laravel', 'php'])) {
    // ...
}

// Check if model has all of the given tags
if ($post->hasAllTags(['laravel', 'php'])) {
    // ...
}
```

### Querying Tagged Models

```php
// Get posts with a specific tag
$laravelPosts = Post::withTag('laravel')->get();

// Get posts with any of the given tags
$phpPosts = Post::withAnyTag(['laravel', 'php'])->get();

// Get posts with all of the given tags
$taggedPosts = Post::withAllTags(['laravel', 'php'])->get();
```

#### Available Query Scopes

**For Tagged Models (using HasTags trait):**

-   `withTag($tag)` - Get models with a specific tag (by name, slug, ID, or Tag model)
-   `withAnyTag($tags)` - Get models with any of the given tags
-   `withAllTags($tags)` - Get models with all of the given tags

**For Tag Model:**

-   `wherePopular($limit)` - Get most popular tags ordered by usage count
-   `whereTrending($limit)` - Get trending tags ordered by trending score
-   `whereName($name)` - Search tags by name (supports partial match)
-   `whereSlug($slug)` - Find tags by slug (exact match)
-   `whereRecentlyActive($days)` - Get tags active within the specified number of days
-   `whereHighGrowth($days)` - Get tags with high growth in the specified period

### Working with Tags

```php
use Ritechoice23\Taggable\Models\Tag;

// Create a tag
$tag = Tag::create([
    'name' => 'Laravel',
    'meta' => [
        'color' => '#FF2D20',
        'description' => 'A PHP framework for web artisans',
        'icon' => 'laravel-icon',
    ],
]);

// Or set meta values individually
$tag->setMeta('color', '#FF2D20');
$tag->setMeta('description', 'A PHP framework for web artisans');

// Get meta values
$color = $tag->getMeta('color');
$description = $tag->getMeta('description', 'No description');

// Check if meta key exists
if ($tag->hasMeta('color')) {
    // ...
}

// Remove a meta key
$tag->removeMeta('icon');

// Get popular tags (ordered by usage count)
$popularTags = Tag::wherePopular(10)->get();

// Get trending tags (ordered by trending score)
$trendingTags = Tag::whereTrending(10)->get();

// Search tags by name
$searchResults = Tag::whereName('laravel')->get();

// Find tags by slug
$tag = Tag::whereSlug('laravel')->first();

// Get recently active tags
$activeTags = Tag::whereRecentlyActive(7)->get(); // Active in last 7 days

// Get tags with high growth
$growingTags = Tag::whereHighGrowth(7)->get(); // Growing in last 7 days

// Calculate trending scores
Tag::calculateAllTrendingScores();

// Or calculate for a specific tag
$tag->calculateTrendingScore();

// Get tag activity summary
$summary = $tag->getActivitySummary();
// Returns: total_count, trending_score, daily_activity, weekly_activity,
// monthly_activity, weekly_growth_rate, last_activity

// Manual count management (usually handled automatically)
$tag->incrementCount();  // Increment usage count
$tag->decrementCount();  // Decrement usage count
$tag->updateCount();     // Recalculate count from actual usage

// Get activity metrics
$recentActivity = $tag->getRecentActivityCount(7);  // Count in last 7 days
$growthRate = $tag->getGrowthRate(7);              // Growth percentage
```

### Configurable Trending Scores

The trending score calculation is fully configurable. You can adjust weights, time periods, and bonuses to match your application's needs:

```php
'trending' => [
    'weights' => [
        'volume' => 0.25,
        'recency' => 0.30,
        'velocity' => 0.25,
        'freshness' => 0.20,
    ],

    'time_periods' => [
        'daily' => 1,
        'weekly' => 7,
        'monthly' => 30,
        'velocity_comparison' => 14,
    ],

    'scoring' => [
        'volume_normalization' => 50,
        'freshness_decay' => 2,
        'velocity_multiplier' => 50,
    ],

    'momentum_bonuses' => [
        'daily_activity' => 1.1,
        'weekly_threshold_40' => 1.15,
        'weekly_threshold_60' => 1.2,
    ],
],
```

### Artisan Commands

Calculate trending scores for all tags:

```bash
php artisan tags:calculate-trending
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Daramola Babatunde Ebenezer](https://github.com/ritechoice23)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
