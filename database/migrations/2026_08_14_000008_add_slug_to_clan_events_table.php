<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clan_events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        DB::table('clan_events')->orderBy('id')->each(function (object $event) {
            $base = Str::slug($event->title) ?: 'clan-event';
            DB::table('clan_events')->where('id', $event->id)->update(['slug' => "{$base}-{$event->id}"]);
        });
    }

    public function down(): void
    {
        Schema::table('clan_events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
