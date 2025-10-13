<?php

/**
 * @file plugins/generic/docMapReviews/classes/DisplayReviewsPreference.inc.php
 *
 * @class DisplayReviewsPreference
 * @ingroup plugins_generic_docMapReviews
 *
 * Data object representing a Display Reviews Preference.
 */

class DisplayReviewsPreference extends DataObject {

    /**
     * Get submission ID.
     * @return int
     */
    function getSubmissionId(){
        return $this->getData('submissionId');
    }

    /**
     * Set submission ID.
     * @param $submissionId int
     */
    function setSubmissionId($submissionId) {
        return $this->setData('submissionId', $submissionId);
    }

    /**
     * Get display reviews flag.
     * @return bool
     */
    function getDisplayReviews(){
        return $this->getData('displayReviews');
    }

    /**
     * Set display reviews flag.
     * @param $displayReviews bool
     */
    function setDisplayReviews($displayReviews) {
        return $this->setData('displayReviews', $displayReviews);
    }

}