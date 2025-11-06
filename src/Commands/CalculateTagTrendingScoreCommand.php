<?php

namespace Ritechoice23\Taggable\Commands;

use Illuminate\Console\Command;
use Ritechoice23\Taggable\Models\Tag;

class CalculateTagTrendingScoreCommand extends Command
{
    protected $signature = 'tags:calculate-trending-scores';

    protected $description = 'Calculate trending scores for all tags';

    public function handle(): int
    {
        $this->info('Calculating trending scores for tags...');

        try {
            Tag::calculateAllTrendingScores();
            $this->info('Trending scores calculated successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to calculate trending scores: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
