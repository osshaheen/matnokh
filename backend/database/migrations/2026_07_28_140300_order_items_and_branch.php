<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // order line items (orders had no detail before)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->json('addons')->nullable();           // [{name, price}]
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();
            $table->index('order_id');
        });

        // which branch fulfils the order
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('merchant_id')->constrained()->nullOnDelete();
        });

        // widen status to a string so the merchant flow (preparing/ready/rejected)
        // fits without fighting an enum alter.
        Schema::table('orders', fn (Blueprint $t) => $t->string('status', 30)->default('pending')->change());
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
        Schema::dropIfExists('order_items');
    }
};
