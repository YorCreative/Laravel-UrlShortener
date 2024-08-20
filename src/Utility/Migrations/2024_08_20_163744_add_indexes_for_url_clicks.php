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
        Schema::table('short_url_clicks', function (Blueprint $table) {
            $table->index('short_url_id');
            $table->index('location_id');
            $table->index('outcome_id');

            $table->index(['short_url_id', 'outcome_id'], 'clicks_url_outcome_index');
        });
    }
};
