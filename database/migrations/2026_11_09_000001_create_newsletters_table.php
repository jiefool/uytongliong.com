<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('slug')->unique();
            $table->date('issue_date');
            $table->string('summary', 500)->nullable();
            $table->longText('body');
            $table->string('cover_image_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
