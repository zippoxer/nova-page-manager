<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OptimistDigital\NovaPageManager\NovaPageManager;

class MakeSlugLocalePublishedParentidPairUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pagesTableName = NovaPageManager::getPagesTableName();

        if (DB::connection()->getDriverName() === 'pgsql') {
            $key = DB::select(
                DB::raw(
                    "SELECT tablename, indexname, indexdef
                    FROM pg_indexes
                    WHERE schemaname = 'public'
                        AND tablename = 'nova_page_manager_pages'
                        AND indexname LIKE 'nova_page_manager_pages%'
                        AND indexname LIKE '%locale_slug_published_unique'"
                )
            );
        } else {
            $key = DB::select(
                DB::raw(
                    'SHOW KEYS
                    FROM nova_page_manager_pages
                    WHERE Key_name LIKE "nova_page_manager_pages%"
                    AND Key_name LIKE "%locale_slug_published_unique"'
                )
            );
        }

        $indexValue = empty($key) ? 'nova_page_manager' : 'nova_page_manager_pages';

        Schema::table($pagesTableName, function ($table) use ($indexValue) {
            $table->dropUnique("{$indexValue}_locale_slug_published_unique");
            $table->unique(['locale', 'slug', 'published', 'parent_id'], 'nova_page_manager_locale_slug_published_parent_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $pagesTableName = NovaPageManager::getPagesTableName();

        Schema::table($pagesTableName, function ($table) {
            $table->dropUnique('nova_page_manager_locale_slug_published_parent_id_unique');
            $table->unique(['locale', 'slug', 'published'], 'nova_page_manager_locale_slug_published_unique');
        });
    }
}
