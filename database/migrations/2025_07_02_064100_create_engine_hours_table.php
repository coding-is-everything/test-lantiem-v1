<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('engine_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('hours', 8, 2); // Engine hours with 2 decimal places
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('engine_hours');
    }
};
