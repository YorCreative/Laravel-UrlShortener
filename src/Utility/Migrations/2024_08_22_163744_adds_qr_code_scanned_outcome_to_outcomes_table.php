<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
                    'id' => 9,
                    'name' => 'client_scanned_qrcode',
                    'alias' => 'The short url\'s QR code was scanned.',
                ],
            ]);
    }
};
