<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained();
            $table->foreignId('model_id')->constrained();
            $table->foreignId('year_id')->constrained();
            $table->foreignId('color_id')->constrained();
            $table->foreignId('shape_id')->constrained();
            $table->foreignId('fuel_type_id')->constrained();
            $table->string('vin_number')->nullable();
            $table->string('plate_number')->nullable();
            $table->unsignedBigInteger('kilometers')->default(0);
            $table->integer('cylinder')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('images')->nullable();
            $table->string('license_front')->nullable();
            $table->string('license_back')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('maintenance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['fuel', 'maintenance', 'other']);
            $table->foreignId('fuel_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maintenance_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('quantity', 10, 2)->nullable(); // liters, kWh, etc.
            $table->decimal('price_per_unit', 10, 2)->nullable();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'type', 'date']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // create, update, delete, login, etc.
            $table->string('module'); // users, cars, expenses, etc.
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'module', 'created_at']);
        });

        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('maintenance_categories');
        Schema::dropIfExists('cars');
    }
};
