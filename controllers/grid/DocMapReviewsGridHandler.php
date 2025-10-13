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
use PKP\notification\NotificationManager;
use PKP\notification\PKPNotification;
use PKP\security\Role;
use APP\core\Application;
use PKP\core\PKPApplication;
use PKP\submission\PKPSubmission;
use APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridRow;
use APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridCellProvider;
use APP\plugins\generic\docMapReviews\classes\DisplayReviewsPreference;

class DocMapReviewsGridHandler extends GridHandler
{
    public static $plugin;

    /** @var boolean */
    public $_readOnly;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            array(Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_AUTHOR),
            array(
                'fetchGrid',
                'fetchRow',
                'allowReviewsToBeDisplayed',
                'disallowReviewsToBeDisplayed',
            )
        );
    }

    /**
     * Set the DocMapReviewsPlugin plugin.
     * @param $plugin DocMapReviewsPlugin
     */
    public static function setPlugin($plugin)
    {
        self::$plugin = $plugin;
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

        $gridData = array();

        if (!$this::$plugin) {
            return;
        }

        $submission = $this->getSubmission();
        $submissionId = $submission->getId();
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');

        $prefs = $displayReviewsPreferenceDAO->getBySubmissionId($submission->getId())->toArray();

        if (empty($prefs)) {
            $displayReviewsPreference = new DisplayReviewsPreference();
            $displayReviewsPreference->setSubmissionId($submissionId);
            $displayReviewsPreference->setDisplayReviews(true);
            $displayReviewsPreferenceDAO->insertObject($displayReviewsPreference);
            $pref = $displayReviewsPreference;
        } else {
            $pref = reset($prefs);
        }

        $gridData[0] = array(
            'label' =>  __("plugins.generic.docMapReviews.displayReviews"),
            'displayReviews' => $pref->getData('displayReviews'),
        );

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
        $notificationMgr = new NotificationManager();
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
            return new JSONMessage(false);
        }

        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $displayReviewsPreferenceDAO->allowDisplayReviews($submissionId);

        $this->sendNotification(
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedDisplayed')]
        );

        return DAO::getDataChangedEvent($submissionId);
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
            return new JSONMessage(false);
        }

        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $displayReviewsPreferenceDAO->disallowDisplayReviews($submissionId);

        $this->sendNotification(
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedNotDisplayed')],
        );
        return DAO::getDataChangedEvent($submissionId);
    }

}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\docMapReviews\controllers\grid\DocMapReviewsGridHandler', '\DocMapReviewsGridHandler');
}
