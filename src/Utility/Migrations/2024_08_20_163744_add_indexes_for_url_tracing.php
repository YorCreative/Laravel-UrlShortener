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
            $table->index(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], 'utm_composite_index');
        });
    }
};
