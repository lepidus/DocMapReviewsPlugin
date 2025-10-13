<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.docMapReviews.controllers.grid.DocMapReviewsGridRow');
import('plugins.generic.docMapReviews.controllers.grid.DocMapReviewsGridCellProvider');

class DocMapReviewsGridHandler extends GridHandler {
    static $plugin;

    /** @var boolean */
    var $_readOnly;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->addRoleAssignment(
            array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
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
    static function setPlugin($plugin) {
        self::$plugin = $plugin;
    }

    /**
     * Get the submission associated with this grid.
     * @return Submission
     */
    function getSubmission() {
        return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
    }

    /**
     * Get whether this grid should be 'read only'
     * @return boolean
     */
    function getReadOnly() {
        return $this->_readOnly;
    }

    /**
     * Set the boolean for 'read only' status
     * @param boolean
     */
    function setReadOnly($readOnly) {
        $this->_readOnly = $readOnly;
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    function authorize($request, &$args, $roleAssignments) {
        import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc Gridhandler::initialize()
     */
    function initialize($request, $args = null) {
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
    function getRowInstance() {
        return new DocMapReviewsGridRow($this->getReadOnly());
    }

    /**
     * @copydoc GridHandler::getJSHandler()
     */
    public function getJSHandler() {
        return '$.pkp.plugins.generic.docMapReviews.DocMapReviewsGridHandler';
    }

    /**
     * @param $user User
     * @return boolean
     */
    function canAdminister($user) {
        return true;
    }

    private function isSubmissionPublished($submission) {
        return $submission->getData('status') === STATUS_PUBLISHED;
    }

    function sendNotification($type, $params) {
        import('classes.notification.NotificationManager');
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
    function allowReviewsToBeDisplayed($args, $request) {
        if (!$request->checkCSRF()) return new JSONMessage(false);

        $submission = $this->getSubmission();
        $submissionId = $submission->getId();

        if ($this->isSubmissionPublished($submission)) return new JSONMessage(false);

        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $displayReviewsPreferenceDAO->allowDisplayReviews($submissionId);

        $this->sendNotification(
            NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedDisplayed')]
        );

        return DAO::getDataChangedEvent($submissionId);
    }

    /**
     * Unassign service from review offer preferences.
     * @param $args array
     * @param $request PKPRequest
     */
    function disallowReviewsToBeDisplayed($args, $request) {
        if (!$request->checkCSRF()) return new JSONMessage(false);
        $submission = $this->getSubmission();
        $submissionId = $submission->getId();

        if ($this->isSubmissionPublished($submission)) return new JSONMessage(false);

        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $displayReviewsPreferenceDAO->disallowDisplayReviews($submissionId);

        $this->sendNotification(
            NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.docMapReviews.displayReviewPreferencesUpdatedNotDisplayed')],
        );
        return DAO::getDataChangedEvent($submissionId);
    }

}
