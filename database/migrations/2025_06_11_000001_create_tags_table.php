<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->json('meta')->nullable();
            $table->integer('usage_count')->default(0);
            $table->decimal('trending_score', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['name']);
            $table->index(['slug']);
            $table->index(['usage_count']);
            $table->index(['trending_score']);
            $table->index(['created_at']);

            $table->unique(['name']);
            $table->unique(['slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
