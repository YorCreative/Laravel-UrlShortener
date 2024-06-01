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
                    'id' => 7,
                    'name' => 'routing_terminated_by_client',
                    'alias' => 'The short url routing was terminated by the client.',
                ],
            ]);
    }
};
