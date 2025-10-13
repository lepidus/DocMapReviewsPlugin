<?php

/**
 * @file plugins/generic/docMapReviews/DocMapReviewsSchemaMigration.inc.php
 *
 * @class DocMapReviewsSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class DocMapReviewsSchemaMigration extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void {
        Capsule::schema()->create('display_reviews_preferences', function (Blueprint $table) {
            $table->bigInteger('submission_id');
            $table->boolean('display_reviews');
        });
    }
}