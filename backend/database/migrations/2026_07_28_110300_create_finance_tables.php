<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->morphs('requester'); // Driver | Merchant
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['bank', 'wallet', 'cash'])->default('bank');
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('orders_limit')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->enum('status', ['active', 'expired', 'canceled'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('withdraws');
    }
};
