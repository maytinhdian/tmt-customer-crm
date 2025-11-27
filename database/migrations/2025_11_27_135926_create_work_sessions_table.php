<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id')->index();

            // Ngày làm việc chính (theo ca/lịch), không phải checked_at
            $table->date('work_date')->index();

            // Mã ca (HC, C1, C2...)
            $table->string('shift_code', 50)->nullable();

            // Liên kết về log IN/OUT gốc
            $table->unsignedBigInteger('check_in_log_id')->nullable()->index();
            $table->unsignedBigInteger('check_out_log_id')->nullable()->index();

            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();

            // Tổng phút trong session (đã trừ break theo rule)
            $table->integer('total_minutes')->nullable();

            // 'open', 'completed', 'missing_out', 'manual', ...
            $table->string('status', 20)->default('open');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};

