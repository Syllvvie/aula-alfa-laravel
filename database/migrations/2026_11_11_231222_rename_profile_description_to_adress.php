<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('profile', 'description') && ! Schema::hasColumn('profile', 'address')) {
            Schema::table('profile', fn (Blueprint $t) => $t->renameColumn('description', 'address'));
        }
        Schema::table('profile', fn (Blueprint $t) => $t->string('address')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('profile', fn (Blueprint $t) => $t->renameColumn('address', 'description'));
    }
};
