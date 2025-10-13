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

class DocMapReviewsSchemaMigration extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void
    {
        Schema::create('display_reviews_preferences', function (Blueprint $table) {
            $table->bigInteger('submission_id');
            $table->boolean('display_reviews');
        });
    }
}
