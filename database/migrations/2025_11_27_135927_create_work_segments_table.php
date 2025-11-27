<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_segments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('session_id')->index();
            $table->unsignedBigInteger('employee_id')->index();

            // Loại segment: NORMAL, OT, NIGHT, LATE, EARLY_LEAVE...
            $table->string('type', 30)->default('NORMAL');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Số phút trong segment này
            $table->integer('minutes')->nullable();

            // Hệ số lương (1.0 = bình thường, 1.5, 2.0…)
            $table->decimal('pay_rate_multiplier', 5, 2)->default(1.00);

            $table->timestamps();

            $table->index(['session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_segments');
    }
};
