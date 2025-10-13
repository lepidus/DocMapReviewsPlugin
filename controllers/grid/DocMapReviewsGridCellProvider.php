<?php

/**
 * @file plugins/generic/docMapReviews/controllers/grid/DocMapReviewsGridCellProvider.php
 *
 * @class DocMapReviewsGridCellProvider
 * @ingroup plugins_generic_docMapReviews
 *
 * @brief Provide cells for DocMap Reviews grid.
 */

namespace APP\plugins\generic\docMapReviews\controllers\grid;

use PKP\controllers\grid\GridCellProvider;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxAction;
use PKP\notification\NotificationManager;
use APP\core\Application;

class DocMapReviewsGridCellProvider extends GridCellProvider
{
    public $_submissionId;

    public function setSubmissionId($submissionId)
    {
        $this->_submissionId = $submissionId;
    }

    public function getSubmissionId()
    {
        return $this->_submissionId;
    }

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $item = $row->getData();
        switch ($column->getId()) {
            case 'displayReviewsLabel':
                return array('label' => $item['label']);
            case 'displayReviews':
                return array('selected' => $item['displayReviews']);
            default:
                break;
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    public function notification($type, $message)
    {
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            $type,
            ['contents' => __($message)]
        );
    }

    /**
     * Get cell actions associated with this row/column combination
     *
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT)
    {
        $pref = $row->getData();
        $columnId = $column->getId();
        $router = $request->getRouter();
        $operation = $pref['displayReviews'] ? 'disallowReviewsToBeDisplayed' : 'allowReviewsToBeDisplayed';

        $actionArgs = [
            "submissionId" => $this->getSubmissionId()
        ];

        $actionUrl = $router->url($request, null, null, $operation, null, $actionArgs);

        $actionRequest = new AjaxAction($actionUrl);
        switch ($columnId) {
            case 'displayReviews':
                return array(
                    new LinkAction(
                        $operation,
                        $actionRequest,
                        __(""),
                        null
                    )
                );
        }

        return parent::getCellActions($request, $row, $column, $position);
    }
}
