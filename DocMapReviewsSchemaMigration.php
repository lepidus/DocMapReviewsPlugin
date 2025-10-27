<?php

/**
 * @file plugins/generic/docMapReviews/DocMapReviewsSchemaMigration.php
 *
 * @class DocMapReviewsSchemaMigration
 * @brief Describe database table structures.
 */

namespace APP\plugins\generic\docMapReviews;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocMapReviewsSchemaMigration extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('display_reviews_preferences')) {
            Schema::create('display_reviews_preferences', function (Blueprint $table) {
                $table->bigInteger('submission_id');
                $table->boolean('display_reviews');
            });
        }

        $this->migratePrereviewDisplayPreferences();
    }

    /**
     * Migrate display preferences from PrereviewPlugin to DocMapReviews
     * Uses a single query with JOIN to avoid N+1 problem
     */
    private function migratePrereviewDisplayPreferences(): void
    {
        if (!Schema::hasTable('prereview_settings')) {
            error_log('DocMapReviews Migration: prereview_settings table not found, skipping migration');
            return;
        }

        try {
            DB::beginTransaction();

            $displayPreferences = DB::table('prereview_settings')
                ->join('publications', 'prereview_settings.publication_id', '=', 'publications.publication_id')
                ->leftJoin('display_reviews_preferences', 'publications.submission_id', '=', 'display_reviews_preferences.submission_id')
                ->where('prereview_settings.setting_name', '=', 'prereview:authorization')
                ->where('prereview_settings.setting_value', '=', 'display')
                ->whereNull('display_reviews_preferences.submission_id') // Only non-migrated
                ->select('publications.submission_id')
                ->distinct()
                ->get();

            $migratedCount = $displayPreferences->count();

            if ($migratedCount > 0) {
                $records = $displayPreferences->map(function ($item) {
                    return [
                        'submission_id' => $item->submission_id,
                        'display_reviews' => true
                    ];
                })->toArray();

                DB::table('display_reviews_preferences')->insert($records);
            }

            DB::commit();

            error_log("DocMapReviews Migration: Successfully migrated {$migratedCount} display preferences");
        } catch (Throwable $e) {
            DB::rollBack();
            error_log('DocMapReviews Migration Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
