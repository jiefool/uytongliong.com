<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('address')->nullable()->after('gender');
            $table->string('google_maps_url', 2048)->nullable()->after('address');
            $table->string('facebook_url', 2048)->nullable()->after('google_maps_url');
            $table->string('instagram_url', 2048)->nullable()->after('facebook_url');
            $table->string('tiktok_url', 2048)->nullable()->after('instagram_url');
            $table->string('x_url', 2048)->nullable()->after('tiktok_url');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn(['address', 'google_maps_url', 'facebook_url', 'instagram_url', 'tiktok_url', 'x_url']);
        });
    }
};
