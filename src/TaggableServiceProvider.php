<?php

namespace Ritechoice23\Taggable;

use Illuminate\Database\Eloquent\Relations\Relation;
use Ritechoice23\Taggable\Commands\CalculateTagTrendingScoreCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TaggableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-taggable')
            ->hasConfigFile('taggable')
            ->hasMigrations(
                '2025_06_11_000001_create_tags_table',
                '2025_06_11_000002_create_taggables_table'
            )
            ->hasCommands([
                CalculateTagTrendingScoreCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->registerMorphMap();
    }

    private function registerMorphMap(): void
    {
        Relation::morphMap([
            'tag' => \Ritechoice23\Taggable\Models\Tag::class,
            'taggable' => \Ritechoice23\Taggable\Models\Taggable::class,
        ]);
    }
}
