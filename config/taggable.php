<?php

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
