<?php

/**
 * @file plugins/generic/docMapReviews/classes/DisplayReviewsPreferenceDAO.php
 *
 * @class DisplayReviewsPreferenceDAO
 * @ingroup plugins_generic_docMapReviews
 *
 * DAO for Display Reviews Preferences.
 */

namespace APP\plugins\generic\docMapReviews\classes;

use Illuminate\Support\Facades\DB;
use PKP\db\DAO;
use APP\plugins\generic\docMapReviews\classes\DisplayReviewsPreference;

class DisplayReviewsPreferenceDAO extends DAO
{
    /**
     * Get DisplayReviewsPreference by submission ID.
     * @param int $submissionId Submission ID
     * @return ?DisplayReviewsPreference
     */
    public function getBySubmissionId(int $submissionId): ?DisplayReviewsPreference
    {
        $row = DB::table('display_reviews_preferences')
            ->where('submission_id', '=', $submissionId)
            ->first();

        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Get or create DisplayReviewsPreference by submission ID.
     * If preference doesn't exist, creates one with displayReviews = false.
     * @param int $submissionId Submission ID
     * @return DisplayReviewsPreference
     */
    public function getOrCreate(int $submissionId): DisplayReviewsPreference
    {
        $preference = $this->getBySubmissionId($submissionId);

        if (!$preference) {
            $preference = $this->newDataObject();
            $preference->setSubmissionId($submissionId);
            $preference->setDisplayReviews(false);
            $this->insertObject($preference);
        }

        return $preference;
    }

    /**
     * Insert a DisplayReviewsPreference.
     * @param DisplayReviewsPreference $preference
     */
    public function insertObject(DisplayReviewsPreference $preference): void
    {
        DB::table('display_reviews_preferences')->insert([
            'submission_id' => $preference->getSubmissionId(),
            'display_reviews' => (int) $preference->getDisplayReviews(),
        ]);
    }

    /**
     * Update a DisplayReviewsPreference.
     * @param DisplayReviewsPreference $preference
     */
    public function updateObject(DisplayReviewsPreference $preference): void
    {
        DB::table('display_reviews_preferences')
            ->where('submission_id', '=', $preference->getSubmissionId())
            ->update([
                'display_reviews' => (int) $preference->getDisplayReviews(),
            ]);
    }

    /**
     * Delete DisplayReviewsPreference by submission ID.
     * @param int $submissionId
     */
    public function deleteBySubmissionId(int $submissionId): void
    {
        DB::table('display_reviews_preferences')
            ->where('submission_id', '=', $submissionId)
            ->delete();
    }

    /**
     * Get the id of the last inserted DisplayReviewsPreference.
     * @return int
     */
    public function getInsertId(): int
    {
        return parent::getInsertId();
    }

    /**
     * Generate a new DisplayReviewsPreference object.
     * @return DisplayReviewsPreference
     */
    public function newDataObject()
    {
        return new DisplayReviewsPreference();
    }

    /**
     * Return a new DisplayReviewsPreference object from a given row.
     * @param array $row
     * @return DisplayReviewsPreference
     */
    public function _fromRow(array $row): DisplayReviewsPreference
    {
        $preference = $this->newDataObject();
        $preference->setSubmissionId($row['submission_id']);
        $preference->setDisplayReviews($row['display_reviews']);

        return $preference;
    }

}
