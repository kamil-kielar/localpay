<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->date('due_date');
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('paid_amount_cents')->default(0);
            $table->char('currency', 3)->default('PLN');
            $table->string('status')->default('due')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['lease_id', 'period']);
        });
        Schema::create('revenue_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('obligation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('PLN');
            $table->string('source')->default('offline');
            $table->string('provider_transaction_id')->nullable();
            $table->date('paid_on');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['source', 'provider_transaction_id']);
        });
        Schema::create('rent_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider');
            $table->string('status')->default('created')->index();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('PLN');
            $table->string('idempotency_key')->unique();
            $table->string('provider_order_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['obligation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_orders');
        Schema::dropIfExists('revenue_payments');
        Schema::dropIfExists('obligations');
    }
};
