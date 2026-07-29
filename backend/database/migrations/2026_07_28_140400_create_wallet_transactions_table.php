<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['funding', 'withdrawal']);   // تمويل طلب / سحب
            $table->decimal('amount', 12, 2);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'done', 'rejected'])->default('done');
            $table->string('method')->nullable();              // bank / wallet ...
            $table->string('ref')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['merchant_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('wallet_transactions'); }
};
