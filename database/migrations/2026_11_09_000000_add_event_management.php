<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clan_events', function (Blueprint $table) {
            $table->string('status')->default('published')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone')->default('Asia/Manila');
        });

        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clan_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('response');
            $table->timestamps();
            $table->unique(['clan_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rsvps');
        Schema::table('clan_events', function (Blueprint $table) {
            $table->dropColumn(['status', 'start_time', 'end_time', 'timezone']);
        });
    }
};
