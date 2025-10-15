<?php

/**
 * @file plugins/generic/docMapReviews/controllers/grid/DocMapReviewsGridRow.php
 *
 * @class DocMapReviewsGridRow
 * @ingroup plugins_generic_docMapReviews
 *
 * @brief Handle DocMap Reviews grid row requests.
 */

namespace APP\plugins\generic\docMapReviews\controllers\grid;

use PKP\controllers\grid\GridRow;

class DocMapReviewsGridRow extends GridRow
{
    /** @var boolean */
    public $_readOnly;

    /**
     * Constructor
     */
    public function __construct($readOnly = false)
    {
        $this->_readOnly = $readOnly;
        parent::__construct();
    }

    //
    // Overridden template methods
    //
    /**
     * Determine if this grid row should be read only.
     * @return boolean
     */
    public function isReadOnly()
    {
        return $this->_readOnly;
    }
}
