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
            $table->json('headers')->after('outcome_id')->nullable();
            $table->string('headers_signature')->after('headers')->nullable();

            $table->index('headers_signature');
        });
    }
};
