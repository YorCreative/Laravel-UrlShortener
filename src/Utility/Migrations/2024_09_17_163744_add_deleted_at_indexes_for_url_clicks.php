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
            $table->index(['outcome_id', 'deleted_at'], 'clicks_url_outcome_deleted_at_index');
            $table->index(['short_url_id', 'outcome_id', 'deleted_at']);
        });
    }
};
