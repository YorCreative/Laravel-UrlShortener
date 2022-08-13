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
        Schema::create('short_url_locations', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->index();
            $table->string('countryName')->nullable();
            $table->string('countryCode')->nullable()->index();
            $table->string('regionName')->nullable();
            $table->string('regionCode')->nullable()->index();
            $table->string('cityName')->nullable();
            $table->string('zipCode')->nullable()->index();
            $table->string('postalCode')->nullable()->index();
            $table->float('latitude', 10)->nullable()->index();
            $table->float('longitude', 10)->nullable()->index();
            $table->string('timezone')->nullable();
            $table->string('metroCode')->nullable()->index();
            $table->string('areaCode')->nullable()->index();
            $table->string('isoCode')->nullable()->index();

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
        Schema::dropIfExists('short_url_locations');
    }
};
