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
        Schema::table('short_urls', function (Blueprint $table) {
            // Drop the unique constraint on identifier
            $table->dropUnique(['identifier']);

            // Add the new unique composite key that should not
            // allow more than one identifier per domain at a time
            // soft deleting recycles the identifier
            $table->unique(['domain', 'identifier', 'deleted_at']);
        });
    }
};
