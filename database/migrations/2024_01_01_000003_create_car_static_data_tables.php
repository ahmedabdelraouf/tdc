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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('hex_code')->nullable();
            $table->timestamps();
        });

        Schema::create('shapes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Sedan, SUV, Coupe, etc.
            $table->timestamps();
        });

        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Petrol-92, Petrol-95, Diesel, EV kWh
            $table->string('unit')->default('liter'); // liter, kWh
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cylinders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained()->onDelete('cascade');
            $table->integer('count'); // 4, 6, 8, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cylinders');
        Schema::dropIfExists('fuel_types');
        Schema::dropIfExists('shapes');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('years');
        Schema::dropIfExists('models');
        Schema::dropIfExists('brands');
    }
};
