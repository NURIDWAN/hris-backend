<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription Plans
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Professional, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->integer('max_employees')->default(0); // 0 = unlimited
            $table->integer('max_users')->default(0); // 0 = unlimited
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Company Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            $table->string('status'); // active, cancelled, expired, pending
            $table->string('billing_cycle'); // monthly, yearly
            $table->dateTime('started_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->timestamps();
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status'); // pending, paid, cancelled, refunded
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->text('description')->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('due_at');
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamps();
        });

        // Add subscription_plan_id to companies table if needed
        if (! Schema::hasColumn('companies', 'subscription_plan_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('subscription_plan_id')->nullable()->after('settings')->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'subscription_plan_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['subscription_plan_id']);
                $table->dropColumn('subscription_plan_id');
            });
        }

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
