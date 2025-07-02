<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create distance table
        if (!Schema::hasTable('distance')) {
            Schema::create('distance', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->integer('value');
                $table->timestamps();
            });
        }

        // Create engine_hours table
        if (!Schema::hasTable('engine_hours')) {
            Schema::create('engine_hours', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->decimal('hours', 8, 2);
                $table->timestamps();
            });
        }

        // Create activity_breakdown table
        if (!Schema::hasTable('activity_breakdown')) {
            Schema::create('activity_breakdown', function (Blueprint $table) {
                $table->id();
                $table->string('activity_type');
                $table->decimal('hours', 8, 2);
                $table->decimal('percentage', 5, 2);
                $table->date('date');
                $table->timestamps();
            });
        }

        // Create messages_received table
        if (!Schema::hasTable('messages_received')) {
            Schema::create('messages_received', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->integer('count');
                $table->string('message_type')->nullable();
                $table->text('details')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distance');
        Schema::dropIfExists('engine_hours');
        Schema::dropIfExists('activity_breakdown');
        Schema::dropIfExists('messages_received');
    }
};
