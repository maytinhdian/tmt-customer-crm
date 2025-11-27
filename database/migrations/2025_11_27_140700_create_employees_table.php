<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Mã nhân viên (duy nhất)
            $table->string('employee_code', 50)->unique();

            // Họ tên
            $table->string('full_name', 150);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();

            // Thông tin cơ bản
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();

            // Thông tin công việc
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('hired_date')->nullable();
            $table->string('default_shift_code', 50)->nullable(); // HC, C1, C2, C3…

            // Liên hệ
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();

            // Trạng thái nhân viên
            $table->string('status', 20)->default('active'); // active, inactive, resigned

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
