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

use PKP\db\DAO;
use PKP\db\DAOResultFactory;
use APP\plugins\generic\docMapReviews\classes\DisplayReviewsPreference;

class DisplayReviewsPreferenceDAO extends DAO
{
    /**
     * Get DisplayReviewsPreference by submission ID.
     * @param $submissionId int Submission ID
     * @return DisplayReviewsPreference
     */
    public function getBySubmissionId($submissionId)
    {
        $result = $this->retrieve(
            'SELECT * FROM display_reviews_preferences WHERE submission_id = ?',
            [$submissionId]
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Insert a DisplayReviewsPreference.
     * @param $preference DisplayReviewsPreference
     * @return Void
     */
    public function insertObject($preference)
    {
        $this->update(
            'INSERT INTO display_reviews_preferences (submission_id, display_reviews) VALUES (?, ?)',
            array(
                $preference->getSubmissionId(),
                (bool) $preference->getDisplayReviews(),
            )
        );
    }

    public function deleteBySubmissionId($submissionId)
    {
        $this->update(
            'DELETE FROM display_reviews_preferences WHERE submission_id = ?',
            array(
                (int) $submissionId,
            )
        );
    }

    public function allowDisplayReviews($submissionId)
    {
        $this->update(
            'UPDATE display_reviews_preferences SET display_reviews = ? WHERE submission_id = ?',
            array(
                true,
                $submissionId,
            )
        );
    }

    public function disallowDisplayReviews($submissionId)
    {
        $this->update(
            'UPDATE display_reviews_preferences SET display_reviews = ? WHERE submission_id = ?',
            array(
                false,
                $submissionId,
            )
        );
    }

    /**
     * Get the id of the last inserted DisplayReviewsPreference.
     * @return int
     */
    public function getInsertId()
    {
        return parent::_getInsertId('display_reviews_preferences', 'id');
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
     * @return DisplayReviewsPreference
     */
    public function _fromRow($row)
    {
        $preference = $this->newDataObject();
        $preference->setSubmissionId($row['submission_id']);
        $preference->setDisplayReviews($row['display_reviews']);

        return $preference;
    }

}
