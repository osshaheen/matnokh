<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Subscription business model — commission is not used. Drop the leftover columns.
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'commission')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('commission'));
        }
        if (Schema::hasColumn('merchants', 'commission_rate')) {
            Schema::table('merchants', fn (Blueprint $t) => $t->dropColumn('commission_rate'));
        }
        if (Schema::hasColumn('subscription_plans', 'commission_rate')) {
            Schema::table('subscription_plans', fn (Blueprint $t) => $t->dropColumn('commission_rate'));
        }
    }
    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->decimal('commission', 10, 2)->default(0));
        Schema::table('merchants', fn (Blueprint $t) => $t->decimal('commission_rate', 5, 2)->default(10));
        Schema::table('subscription_plans', fn (Blueprint $t) => $t->decimal('commission_rate', 5, 2)->nullable());
    }
};
