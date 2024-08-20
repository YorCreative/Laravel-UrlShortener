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
        Schema::table('short_url_tracings', function (Blueprint $table) {
            $table->index(['utm_source(100)', 'utm_medium(100)', 'utm_campaign(100)', 'utm_content(100)', 'utm_term(100)'], 'utm_composite_index');
        });
    }
};
