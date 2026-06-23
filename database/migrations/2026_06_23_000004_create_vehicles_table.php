<?php

use App\Enums\VehicleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('weekly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->decimal('deposit_amount', 10, 2);
            $table->enum('status', array_column(VehicleStatus::cases(), 'value'))
                ->default(VehicleStatus::Available->value);
            $table->text('description')->nullable();
            $table->json('specs')->nullable();
            $table->string('requires_license_type')->nullable();
            $table->boolean('available_with_operator')->default(false);
            $table->decimal('operator_daily_rate', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
