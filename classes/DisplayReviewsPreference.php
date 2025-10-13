<?php

/**
 * @file plugins/generic/docMapReviews/classes/DisplayReviewsPreference.php
 *
 * @class DisplayReviewsPreference
 * @ingroup plugins_generic_docMapReviews
 *
 * Data object representing a Display Reviews Preference.
 */

namespace APP\plugins\generic\docMapReviews\classes;

use PKP\core\DataObject;

class DisplayReviewsPreference extends DataObject
{
    /**
     * Get submission ID.
     * @return int
     */
    public function getSubmissionId()
    {
        return $this->getData('submissionId');
    }

    /**
     * Set submission ID.
     * @param $submissionId int
     */
    public function setSubmissionId($submissionId)
    {
        return $this->setData('submissionId', $submissionId);
    }

    /**
     * Get display reviews flag.
     * @return bool
     */
    public function getDisplayReviews()
    {
        return $this->getData('displayReviews');
    }

    /**
     * Set display reviews flag.
     * @param $displayReviews bool
     */
    public function setDisplayReviews($displayReviews)
    {
        return $this->setData('displayReviews', $displayReviews);
    }

}
