<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The connection the package's tables live on.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return config('urlshortener.database.connection');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->getConnection())->create('short_urls', function (Blueprint $table) {
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
        Schema::connection($this->getConnection())->dropIfExists('short_urls');
    }
};
