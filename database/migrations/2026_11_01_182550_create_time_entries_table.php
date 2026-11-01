<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            // Employees are never deleted, so their history is protected (restrict, not cascade).
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('description', 255);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            // Equals employee_id while the timer runs and NULL once it is stopped. The unique
            // index below lets the database itself refuse a second running timer per employee
            // (MySQL has no partial indexes; NULLs never collide in a unique index).
            $table->unsignedBigInteger('running_employee_id')
                ->nullable()
                ->storedAs('CASE WHEN ended_at IS NULL THEN employee_id END');
            $table->unique('running_employee_id');

            // Report for one employee over a period: WHERE employee_id = ? AND started_at < ?
            $table->index(['employee_id', 'started_at']);
            // Report for all employees over a period: WHERE started_at < ? ORDER BY started_at
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
