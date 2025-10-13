<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DocMapReviewsGridCellProvider extends GridCellProvider {
    var $_submissionId;

    function setSubmissionId($submissionId) {
        $this->_submissionId = $submissionId;
    }

    function getSubmissionId() {
        return $this->_submissionId;
    }

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
     */
    function getTemplateVarsFromRowColumn($row, $column) {
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

    function notification($type, $message) {
        import('classes.notification.NotificationManager');
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
    function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
        $pref = $row->getData();
        $columnId = $column->getId();
        $router = $request->getRouter();
        $operation = $pref['displayReviews'] ? 'disallowReviewsToBeDisplayed' : 'allowReviewsToBeDisplayed';

        $actionArgs = [
            "submissionId" => $this->getSubmissionId()
        ];

        $actionUrl = $router->url($request, null, null, $operation, null, $actionArgs);

        import('lib.pkp.classes.linkAction.request.AjaxAction');
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
