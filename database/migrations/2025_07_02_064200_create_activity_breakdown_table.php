<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_breakdown', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type'); // e.g., 'Driving', 'Idle', 'Working'
            $table->decimal('hours', 8, 2); // Duration in hours
            $table->decimal('percentage', 5, 2); // Percentage of total time
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_breakdown');
    }
};
