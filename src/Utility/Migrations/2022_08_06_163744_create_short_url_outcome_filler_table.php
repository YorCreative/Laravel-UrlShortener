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
                    'id' => 1,
                    'name' => 'success_routed',
                    'alias' => 'The Short Url was routed successfully.',
                ],
                [
                    'id' => 2,
                    'name' => 'success_protected',
                    'alias' => 'The Short Url was opened and has a protected password.',
                ],
                [
                    'id' => 3,
                    'name' => 'failure_protected',
                    'alias' => 'The Short Url received an incorrect password and was not routed.',
                ],
                [
                    'id' => 4,
                    'name' => 'failure_limit',
                    'alias' => 'The Short Url open limit was reached and was not routed.',
                ],
                [
                    'id' => 5,
                    'name' => 'failure_expiration',
                    'alias' => 'The Short Url has expired and was not routed.',
                ],
                [
                    'id' => 6,
                    'name' => 'failure_activation',
                    'alias' => 'The Short Url has not activated yet and was not routed.',
                ],
            ]);
    }
};
