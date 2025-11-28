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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();       // HC, C1, C2, C3…
            $table->string('name', 100);                // Hành chính, Ca sáng…

            $table->time('start_time');                 // 08:00
            $table->time('end_time');                   // 17:00

            $table->integer('break_minutes')->default(60);
            $table->boolean('is_overnight')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
