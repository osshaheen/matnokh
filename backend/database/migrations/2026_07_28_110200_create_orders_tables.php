<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pickup_address')->nullable();
            $table->string('drop_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'wallet'])->default('cash');
            $table->boolean('is_paid')->default(false);
            $table->enum('status', ['pending', 'accepted', 'picked_up', 'on_the_way', 'delivered', 'canceled', 'returned'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('orders');
    }
};
