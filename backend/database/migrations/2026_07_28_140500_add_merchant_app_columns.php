<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('is_open')->default(true)->after('is_active');   // متاح — يستقبل الطلبات
            $table->boolean('prep_mode')->default(false)->after('is_open');  // وضع التجهيز
            $table->boolean('auto_accept')->default(false)->after('prep_mode');
            $table->decimal('rating', 3, 2)->default(5)->after('auto_accept');
        });
    }
    public function down(): void
    {
        Schema::table('merchants', fn (Blueprint $t) => $t->dropColumn(['is_open', 'prep_mode', 'auto_accept', 'rating']));
    }
};
