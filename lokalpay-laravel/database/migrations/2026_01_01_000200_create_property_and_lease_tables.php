<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('postal_code', 12)->nullable();
            $table->unsignedBigInteger('purchase_cost_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
        });
        Schema::create('leases', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tenant_name');
            $table->string('tenant_email')->nullable()->index();
            $table->string('tenant_phone')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('monthly_rent_cents');
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tenant_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('properties');
    }
};
