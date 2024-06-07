<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('short_url_outcomes')
            ->insert([
                [
                    'id' => 8,
                    'name' => 'routing_page_loaded',
                    'alias' => 'The short url routing was initiated by a client.',
                ],
            ]);
    }
};
