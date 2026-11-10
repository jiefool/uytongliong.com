<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generational_messages', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->string('video_path')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('generational_messages')->whereNull('body')->update(['body' => '']);
        Schema::table('generational_messages', function (Blueprint $table) {
            $table->dropColumn('video_path');
            $table->text('body')->nullable(false)->change();
        });
    }
};
