<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', array_column(BookingStatus::cases(), 'value'))
                ->default(BookingStatus::Pending->value);
            $table->decimal('total_price', 10, 2);
            $table->decimal('deposit_amount', 10, 2);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->boolean('with_operator')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
