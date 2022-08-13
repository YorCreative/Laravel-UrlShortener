<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('short_urls', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index()->unique();
            $table->string('hashed')->index()->unique();
            $table->text('plain_text');
            $table->bigInteger('activation')->nullable()->index();
            $table->bigInteger('expiration')->nullable()->index();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('limit')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('short_urls');
    }
};
