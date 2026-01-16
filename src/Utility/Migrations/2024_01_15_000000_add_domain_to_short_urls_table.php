<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            // Add domain column - nullable for backwards compatibility
            $table->string('domain')->nullable()->after('id')->index();

            // Drop existing unique constraints
            $table->dropUnique(['identifier']);
            $table->dropUnique(['hashed']);

            // Add composite unique constraints (domain + identifier, domain + hashed)
            // NULL domain values are treated as the default domain
            $table->unique(['domain', 'identifier'], 'short_urls_domain_identifier_unique');
            $table->unique(['domain', 'hashed'], 'short_urls_domain_hashed_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropUnique('short_urls_domain_identifier_unique');
            $table->dropUnique('short_urls_domain_hashed_unique');

            $table->unique('identifier');
            $table->unique('hashed');

            $table->dropColumn('domain');
        });
    }
};
