<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // Nhân viên
            $table->unsignedBigInteger('employee_id')->index();

            // Thông tin thiết bị / nguồn log
            $table->string('device_id', 100)->nullable()->index();
            $table->string('source', 50)->nullable(); // vd: 'zkteco', 'hikvision', 'manual'

            // IN / OUT / UNKNOWN
            $table->string('direction', 10)->nullable(); // 'IN', 'OUT', ...

            // Thời điểm chấm
            $table->timestamp('checked_at')->index();

            // Một số thông tin raw từ máy chấm công
            $table->string('work_code', 50)->nullable();
            $table->string('area', 100)->nullable();
            $table->json('raw_payload')->nullable();

            // Status để flag log lỗi, trùng, bỏ qua…
            $table->string('status', 20)->default('valid'); // 'valid', 'duplicate', 'ignored', ...

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
