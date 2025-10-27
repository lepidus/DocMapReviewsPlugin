<?php

/**
 * @file plugins/generic/docMapReviews/controllers/grid/DocMapReviewsGridHandler.php
 *
 * @class DocMapReviewsGridHandler
 * @ingroup plugins_generic_docMapReviews
 *
 * @brief Handle DocMap Reviews grid requests.
 */

namespace APP\plugins\generic\docMapReviews\controllers\grid;

use PKP\controllers\grid\GridHandler;
use PKP\controllers\grid\GridColumn;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\notification\PKPNotificationManager;
use PKP\notification\PKPNotification;
use PKP\security\Role;
use PKP\plugins\PluginRegistry;
use APP\core\Application;
use PKP\core\PKPApplication;
use PKP\submission\PKPSubmission;
use APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridRow;
use APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridCellProvider;
use APP\plugins\generic\docMapReviews\classes\DisplayReviewsPreferenceDAO;

class DocMapReviewsGridHandler extends GridHandler
{
    public $plugin;

    /** @var boolean */
    public $_readOnly;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_AUTHOR],
            [
                'fetchGrid',
                'fetchRow',
                'allowReviewsToBeDisplayed',
                'disallowReviewsToBeDisplayed',
            ]
        );
        $this->plugin = PluginRegistry::getPlugin('generic', DOC_MAP_REVIEWS_PLUGIN_NAME);
    }

    /**
     * Get the submission associated with this grid.
     * @return Submission
     */
    public function getSubmission()
    {
        return $this->getAuthorizedContextObject(PKPApplication::ASSOC_TYPE_SUBMISSION);
    }

    /**
     * Get whether this grid should be 'read only'
     * @return boolean
     */
    public function getReadOnly()
    {
        return $this->_readOnly;
    }

    /**
     * Set the boolean for 'read only' status
     * @param boolean
     */
    public function setReadOnly($readOnly)
    {
        $this->_readOnly = $readOnly;
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc Gridhandler::initialize()
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $gridData = [];

        $submission = $this->getSubmission();
        $submissionId = $submission->getId();
        /** @var DisplayReviewsPreferenceDAO */
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');

        $pref = $displayReviewsPreferenceDAO->getOrCreate($submissionId);

        $gridData[0] = [
            'label' =>  __("plugins.generic.docMapReviews.displayReviews"),
            'displayReviews' => $pref->getData('displayReviews'),
        ];

        $this->setGridDataElements($gridData);

        if ($this->canAdminister($request->getUser())) {
            $this->setReadOnly(false);
        } else {
            $this->setReadOnly(true);
        }

        // Columns
        $cellProvider = new DocMapReviewsGridCellProvider();
        $cellProvider->setSubmissionId($submissionId);

        $this->addColumn(new GridColumn(
            'displayReviewsLabel',
            'plugins.generic.docMapReviews.preferences',
            null,
            'controllers/grid/gridCell.tpl',
            $cellProvider
        ));

        $this->addColumn(new GridColumn(
            'displayReviews',
            '',
            null,
            'controllers/grid/common/cell/selectStatusCell.tpl',
            $cellProvider
        ));
    }

    //
    // Overridden methods from GridHandler
    //
    /**
     * @copydoc Gridhandler::getRowInstance()
     */
    public function getRowInstance()
    {
        return new DocMapReviewsGridRow($this->getReadOnly());
    }

    /**
     * @copydoc GridHandler::getJSHandler()
     */
    public function getJSHandler()
    {
        return '$.pkp.plugins.generic.docMapReviews.DocMapReviewsGridHandler';
    }

    /**
     * @param $user User
     * @return boolean
     */
    public function canAdminister($user)
    {
        return true;
    }

    private function isSubmissionPublished($submission)
    {
        return $submission->getData('status') === PKPSubmission::STATUS_PUBLISHED;
    }

    public function sendNotification($type, $params)
    {
        $notificationMgr = new PKPNotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            $type,
            $params,
        );
    }

    /**
     * Assign service from review offer preferences.
     * @param $args array
     * @param $request PKPRequest
     */
    public function allowReviewsToBeDisplayed($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $submission = $this->getSubmission();
        $submissionId = $submission->getId();

        if ($this->isSubmissionPublished($submission)) {
            $latestPublication = $submission->getLatestPublication();
            if ($latestPublication->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
                return new JSONMessage(false);
            }
        }

        /** @var DisplayReviewsPreferenceDAO */
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $preference = $displayReviewsPreferenceDAO->getOrCreate($submissionId);
        $preference->setDisplayReviews(true);
        $displayReviewsPreferenceDAO->updateObject($preference);

        $this->sendNotification(
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedDisplayed')]
        );

        $json = new JSONMessage(true);
        $json->setEvent('dataChanged', [$submissionId]);
        return $json;
    }

    /**
     * Unassign service from review offer preferences.
     * @param $args array
     * @param $request PKPRequest
     */
    public function disallowReviewsToBeDisplayed($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }
        $submission = $this->getSubmission();
        $submissionId = $submission->getId();

        if ($this->isSubmissionPublished($submission)) {
            $latestPublication = $submission->getLatestPublication();
            if ($latestPublication->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
                return new JSONMessage(false);
            }
        }

        /** @var DisplayReviewsPreferenceDAO */
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $preference = $displayReviewsPreferenceDAO->getOrCreate($submissionId);
        $preference->setDisplayReviews(false);
        $displayReviewsPreferenceDAO->updateObject($preference);

        $this->sendNotification(
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedNotDisplayed')],
        );
        $json = new JSONMessage(true);
        $json->setEvent('dataChanged', [$submissionId]);
        return $json;
    }

}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridHandler', '\DocMapReviewsGridHandler');
}
