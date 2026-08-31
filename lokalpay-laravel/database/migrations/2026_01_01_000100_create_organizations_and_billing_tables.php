<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->unsignedSmallInteger('property_limit');
            $table->json('features');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->string('billing_email')->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('provider_customer_id')->nullable()->index();
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->string('status')->default('incomplete')->index();
            $table->timestamp('current_period_end')->nullable();
            $table->unsignedBigInteger('last_event_created_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamps();
        });
        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreignId('current_subscription_id')->nullable()
                ->after('plan_id')->constrained('subscriptions')->nullOnDelete();
        });
        Schema::create('saas_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('kind')->default('subscription');
            $table->string('status')->default('created')->index();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('PLN');
            $table->string('idempotency_key')->unique();
            $table->string('provider_order_id')->nullable()->unique();
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_orders');
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_subscription_id');
        });
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('plans');
    }
};
