<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id(); $table->string('code', 40)->unique(); $table->string('name'); $table->text('description')->nullable(); $table->unsignedSmallInteger('display_order')->default(0); $table->boolean('is_active')->default(true); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->softDeletes(); $table->index(['is_active','display_order']);
        });
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id(); $table->string('code', 40)->unique(); $table->string('name'); $table->string('type', 80)->default('ruangan'); $table->string('building')->nullable(); $table->string('floor')->nullable(); $table->string('person_in_charge')->nullable(); $table->text('description')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes(); $table->index(['type','is_active']);
        });
        Schema::create('inventory_conditions', function (Blueprint $table) {
            $table->id(); $table->string('code', 40)->unique(); $table->string('name'); $table->string('severity', 40)->default('normal'); $table->unsignedSmallInteger('display_order')->default(0); $table->boolean('is_system')->default(true); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['is_active','display_order']);
        });
        Schema::create('inventory_measurement_units', function (Blueprint $table) {
            $table->id(); $table->string('code', 40)->unique(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id(); $table->string('code', 60)->unique(); $table->string('name')->index(); $table->foreignId('inventory_category_id')->constrained()->restrictOnDelete(); $table->foreignId('inventory_measurement_unit_id')->constrained()->restrictOnDelete(); $table->foreignId('primary_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete(); $table->string('tracking_type', 20)->default('quantity')->index(); $table->string('brand')->nullable(); $table->string('model')->nullable(); $table->text('description')->nullable(); $table->unsignedSmallInteger('acquisition_year')->nullable()->index(); $table->date('acquisition_date')->nullable(); $table->string('acquisition_source')->nullable()->index(); $table->string('funding_source')->nullable()->index(); $table->decimal('acquisition_value', 15, 2)->nullable(); $table->string('photo_path')->nullable(); $table->text('notes')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('inventory_item_units', function (Blueprint $table) {
            $table->id(); $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete(); $table->string('unit_code', 80)->unique(); $table->string('inventory_number', 100)->nullable()->unique(); $table->string('serial_number', 100)->nullable()->unique(); $table->foreignId('inventory_location_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('inventory_condition_id')->nullable()->constrained()->nullOnDelete(); $table->string('status', 30)->default('active')->index(); $table->date('acquisition_date')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->softDeletes(); $table->index(['inventory_item_id','inventory_location_id','inventory_condition_id'], 'inv_unit_item_loc_cond_idx');
        });
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id(); $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete(); $table->foreignId('inventory_location_id')->constrained()->restrictOnDelete(); $table->foreignId('inventory_condition_id')->constrained()->restrictOnDelete(); $table->unsignedInteger('quantity')->default(0); $table->timestamps(); $table->unique(['inventory_item_id','inventory_location_id','inventory_condition_id'], 'inventory_balance_unique'); $table->index(['inventory_location_id','inventory_condition_id'], 'inv_balance_loc_cond_idx');
        });
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id(); $table->string('transaction_number', 80)->unique(); $table->date('transaction_date')->index(); $table->string('type', 40)->index(); $table->string('status', 30)->default('draft')->index(); $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete(); $table->foreignId('inventory_item_unit_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('from_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete(); $table->foreignId('to_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete(); $table->foreignId('from_condition_id')->nullable()->constrained('inventory_conditions')->nullOnDelete(); $table->foreignId('to_condition_id')->nullable()->constrained('inventory_conditions')->nullOnDelete(); $table->unsignedInteger('quantity'); $table->string('reference_number')->nullable(); $table->string('reason'); $table->text('notes')->nullable(); $table->string('attachment_path')->nullable(); $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_transactions')->nullOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('posted_at')->nullable(); $table->timestamps(); $table->index(['type','transaction_date']);
        });
        Schema::create('inventory_stock_opnames', function (Blueprint $table) {
            $table->id(); $table->string('opname_number', 80)->unique(); $table->foreignId('inventory_location_id')->constrained()->restrictOnDelete(); $table->date('opname_date')->index(); $table->string('status', 40)->default('draft')->index(); $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete(); $table->text('notes')->nullable(); $table->timestamp('posted_at')->nullable(); $table->timestamps();
        });
        Schema::create('inventory_stock_opname_lines', function (Blueprint $table) {
            $table->id(); $table->foreignId('inventory_stock_opname_id')->constrained()->cascadeOnDelete(); $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete(); $table->foreignId('inventory_condition_id')->constrained()->restrictOnDelete(); $table->unsignedInteger('system_quantity')->default(0); $table->unsignedInteger('physical_quantity')->default(0); $table->integer('difference_quantity')->default(0); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['inventory_stock_opname_id','inventory_item_id','inventory_condition_id'], 'inventory_opname_line_unique');
        });
    }
    public function down(): void
    { foreach (['inventory_stock_opname_lines','inventory_stock_opnames','inventory_transactions','inventory_balances','inventory_item_units','inventory_items','inventory_measurement_units','inventory_conditions','inventory_locations','inventory_categories'] as $table) Schema::dropIfExists($table); }
};
