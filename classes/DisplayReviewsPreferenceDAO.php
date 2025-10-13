<?php

import('lib.pkp.classes.db.DAO');
import('plugins.generic.docMapReviews.classes.DisplayReviewsPreference');

class DisplayReviewsPreferenceDAO extends DAO {
    /**
     * Get DisplayReviewsPreference by submission ID.
     * @param $submissionId int Submission ID
     * @return DisplayReviewsPreference
     */
    function getBySubmissionId($submissionId) {
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
    function insertObject($preference) {
        $this->update(
            'INSERT INTO display_reviews_preferences (submission_id, display_reviews) VALUES (?, ?)',
            array(
                $preference->getSubmissionId(),
                (bool) $preference->getDisplayReviews(),
            )
        );
    }

    function deleteBySubmissionId($submissionId) {
        $this->update(
            'DELETE FROM display_reviews_preferences WHERE submission_id = ?',
            array(
                (int) $submissionId,
            )
        );
    }

    function allowDisplayReviews($submissionId) {
        $this->update(
            'UPDATE display_reviews_preferences SET display_reviews = ? WHERE submission_id = ?',
            array(
                true,
                $submissionId,
            )
        );
    }

    function disallowDisplayReviews($submissionId) {
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
    function getInsertId() {
        return parent::_getInsertId('display_reviews_preferences', 'id');
    }

    /**
     * Generate a new DisplayReviewsPreference object.
     * @return DisplayReviewsPreference
     */
    function newDataObject() {
        return new DisplayReviewsPreference();
    }

    /**
     * Return a new DisplayReviewsPreference object from a given row.
     * @return DisplayReviewsPreference
     */
    function _fromRow($row) {
        $preference = $this->newDataObject();
        $preference->setSubmissionId($row['submission_id']);
        $preference->setDisplayReviews($row['display_reviews']);

        return $preference;
    }

}