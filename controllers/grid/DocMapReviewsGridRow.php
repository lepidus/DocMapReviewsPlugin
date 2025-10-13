<?php

/**
 * @brief Handle item grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class DocMapReviewsGridRow extends GridRow {
    /** @var boolean */
    var $_readOnly;

    /**
     * Constructor
     */
    function __construct($readOnly = false) {
        $this->_readOnly = $readOnly;
        parent::__construct();
    }

    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize()
     */
    function initialize($request, $template = null) {
        parent::initialize($request, $template);
    }

    /**
     * Determine if this grid row should be read only.
     * @return boolean
     */
    function isReadOnly() {
        return $this->_readOnly;
    }
}
