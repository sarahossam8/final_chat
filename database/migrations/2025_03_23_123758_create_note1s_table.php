<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('note1s', function (Blueprint $table) {
            $table->id();
            $table->text('text'); 
            $table->text('title'); 
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


    public function down(): void
    {
        Schema::dropIfExists('note1s');
    }
};