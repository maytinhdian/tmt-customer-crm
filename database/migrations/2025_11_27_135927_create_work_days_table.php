<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_days', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id')->index();
            $table->date('work_date')->index();

            $table->string('shift_code', 50)->nullable();

            // Tổng hợp theo ngày
            $table->integer('total_work_minutes')->default(0);
            $table->integer('paid_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);

            // 'normal', 'absent', 'holiday', 'day_off', ...
            $table->string('status', 20)->default('normal');

            $table->timestamp('first_check_in_at')->nullable();
            $table->timestamp('last_check_out_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_days');
    }
};
