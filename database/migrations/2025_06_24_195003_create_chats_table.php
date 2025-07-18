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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->text('text'); 
            $table->unsignedBigInteger('users_id'); 
            $table->text('output_text')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();

            
            $table->foreign('users_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
