<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('type', ['earning', 'deduction']); // earning = tunjangan, deduction = potongan
            $table->enum('category', ['fixed', 'variable', 'benefit', 'tax', 'insurance', 'loan', 'other']);
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula']);
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable(); // untuk percentage-based
            $table->string('percentage_base')->nullable(); // 'basic_salary', 'gross_salary', etc.
            $table->text('formula')->nullable(); // untuk custom formula
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
