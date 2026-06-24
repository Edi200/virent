<?php

use App\Models\Vehicle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        Vehicle::query()->orderBy('id')->each(function (Vehicle $vehicle): void {
            if (blank($vehicle->slug)) {
                $vehicle->slug = Vehicle::generateUniqueSlug($vehicle->name, $vehicle->id);
                $vehicle->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
