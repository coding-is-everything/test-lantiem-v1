<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages_received', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('count'); // Number of messages received
            $table->string('message_type')->nullable(); // Optional: Type of message if needed
            $table->text('details')->nullable(); // Optional: Additional details about the messages
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages_received');
    }
};
