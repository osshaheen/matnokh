<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // pickup_dropoff = has a start AND end point; pickup_only = one pickup point.
            $table->enum('point_type', ['pickup_dropoff', 'pickup_only'])->default('pickup_dropoff')->after('icon');
        });
        if (Schema::hasColumn('services', 'base_price')) {
            Schema::table('services', fn (Blueprint $t) => $t->dropColumn('base_price'));
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('point_type');
            $table->decimal('base_price', 8, 2)->default(0);
        });
    }
};
