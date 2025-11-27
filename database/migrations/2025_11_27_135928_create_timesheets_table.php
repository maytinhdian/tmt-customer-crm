<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id')->index();

            // Chu kỳ tính lương (theo tháng, 2 tuần, tùy)
            $table->date('period_start_date')->index();
            $table->date('period_end_date')->index();

            $table->integer('total_work_minutes')->default(0);
            $table->integer('total_overtime_minutes')->default(0);
            $table->integer('total_paid_minutes')->default(0);

            // 'open', 'locked', 'approved'
            $table->string('status', 20)->default('open');

            $table->timestamp('locked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'employee_id',
                'period_start_date',
                'period_end_date',
            ], 'timesheets_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
