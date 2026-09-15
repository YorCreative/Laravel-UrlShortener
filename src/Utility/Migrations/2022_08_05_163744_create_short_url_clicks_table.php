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
        Schema::connection($this->getConnection())->create('short_url_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('short_url_id');
            $table->unsignedBigInteger('location_id');
            $table->integer('outcome_id');
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
        Schema::connection($this->getConnection())->dropIfExists('short_url_clicks');
    }
};
