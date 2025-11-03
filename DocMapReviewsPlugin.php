<?php

/**
 * @file plugins/generic/docMapReviews/DocMapReviewsPlugin.php
 *
 * Copyright (c) --

 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class DocMapReviewsPlugin
 * @ingroup plugins_generic_docMapReviews
 * @brief Plugin class for the DocMap Reviews plugin.
 */

namespace APP\plugins\generic\docMapReviews;

use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\config\Config;
use PKP\core\PKPApplication;
use APP\core\Application;
use APP\facades\Repo;
use PKP\submission\PKPSubmission;
use PKP\db\DAORegistry;
use APP\plugins\generic\docMapReviews\classes\DisplayReviewsPreferenceDAO;
use APP\plugins\generic\docMapReviews\DocMapReviewsSchemaMigration;
use DateTime;
use Exception;

define('DOCMAPS_API_URL', 'https://sciety.org/docmaps/v1/articles/');
define('DOCMAPS_JSON_VERSION', '.docmap.json');

class DocMapReviewsPlugin extends GenericPlugin
{
    /** @var array Lazy loaded review service list */
    private $_reviewServiceList = null;

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            $displayReviewsPreferenceDAO = new DisplayReviewsPreferenceDAO();
            DAORegistry::registerDAO('DisplayReviewsPreferenceDAO', $displayReviewsPreferenceDAO);

            Hook::add('Template::Workflow::Publication', [$this, 'addToWorkflow']);
            Hook::add('TemplateManager::display', [$this, 'addGridhandlerJs']);
            Hook::add('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', [$this, 'submissionWizard']);
            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);
            Hook::add('Templates::Preprint::Details', [$this, 'callbackSharingDisplay']);
        }

        return $success;
    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the plugins list where editors can
     * enable and disable plugins.
     */
    public function getDisplayName()
    {
        return 'DocMap Reviews';
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the plugins list where editors can
     * enable and disable plugins.
     */
    public function getDescription()
    {
        return 'This plugin allows reviews via DocMaps to be displayed on the pre-review detail pages.';
    }

    private function getAuthorId($user)
    {
        $orcid = $user->getOrcid();
        return ($orcid != "") ? $orcid : "mailto:{$user->getEmail()}";
    }

    public function getDoiById($id)
    {
        $submission = Repo::submission()->get($id);
        if (!$submission) {
            return null;
        }
        $currentPublication = $submission->getCurrentPublication();
        if (!$currentPublication) {
            return null;
        }

        $doiObject = $currentPublication->getData('doiObject');
        return $doiObject ? $doiObject->getData('doi') : null;
    }

    private function getSubmissionType()
    {
        $applicationName = substr(Application::getName(), 0, 3);

        if ($applicationName == 'ops') {
            return 'preprint';
        }

        return 'article';
    }

    /**
     * Retrieves the list of review services from the plugin settings and caches it
     * @return array List of review services, where key is the home URL and value is the inbox URL
     */
    public function getReviewServiceList()
    {
        if (
            $this->_reviewServiceList === null
            && !is_array($this->_reviewServiceList = $this->getSetting($this->getCurrentContextId(), 'reviewServiceList'))
        ) {
            $this->_reviewServiceList = [];
        }
        return $this->_reviewServiceList;
    }

    public function getInstallMigration()
    {
        return new DocMapReviewsSchemaMigration();
    }

    /**
     * @see Plugin::getInstallSitePluginSettingsFile()
     */
    public function getInstallSitePluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    private function isSubmissionPublished($submission)
    {
        return $submission->getData('status') === PKPSubmission::STATUS_PUBLISHED;
    }

    public function getDisplayReviewsPreferences($submissionId)
    {
        /** @var DisplayReviewsPreferenceDAO $displayReviewsPreferenceDAO */
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $preference = $displayReviewsPreferenceDAO->getBySubmissionId($submissionId);

        return $preference ? $preference->getData('displayReviews') : false;
    }

    public function addToWorkflow($hookName, $params)
    {
        $smarty = & $params[1];
        $output = & $params[2];
        $submission = $smarty->getTemplateVars('submission');
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        $smarty->assign(
            'userIsManager',
            $user->hasRole(Application::getWorkflowTypeRoles()[PKPApplication::WORKFLOW_TYPE_EDITORIAL], $request->getContext()->getId())
        );

        $smarty->assign([
            'submissionType' => $this->getSubmissionType(),
            'reviewServiceList' => $this->getReviewServiceList(),
            'originHomeUrl' => $this->getSetting($this->getCurrentContextId(), 'originHomeUrl'),
            'originInboxUrl' => $this->getSetting($this->getCurrentContextId(), 'originInboxUrl'),
            'actorName' => $user->getFullName(),
            'authorId' => $this->getAuthorId($user),
            'isPublished' => $this->isSubmissionPublished($submission),
            'doi' => $this->getDoiById($submission->getId()),
            'submissionId' => $submission->getData('id'),
            'displayReviewsPreferences' => $this->getDisplayReviewsPreferences($submission->getData('id')),
        ]);

        $output .= sprintf(
            '<tab id="docMapReviews" label="%s">%s</tab>',
            __('plugins.generic.docMapReviews.displayName'),
            $smarty->fetch($this->getTemplateResource('docMapReviewPreferences.tpl'))
        );
    }

    /**
     * Show citations part on step 3 in submission wizard
     * @param string $hookname
     * @param array $args
     * @return void
     */
    public function submissionWizard($hookname, array $args)
    {
        $templateMgr = &$args[1];
        $request = $this->getRequest();
        $submissionId = $request->getUserVar('submissionId');

        $templateParams = [];
        $templateParams['submissionId'] = $submissionId;

        $templateParams['statusCodePublished'] = PKPSubmission::STATUS_PUBLISHED;

        $templateMgr->assign($templateParams);

        $templateMgr->display($this->getTemplateResource("submission/form/submissionWizard.tpl"));
    }

    /**
     * Permit requests to the grid handler
     * @param $hookName string The name of the hook being invoked
     * @param $args array The parameters to the invoked hook
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        if ($component == 'plugins.generic.docMapReviews.controllers.grid.DocMapReviewsGridHandler') {
            define('DOC_MAP_REVIEWS_PLUGIN_NAME', $this->getName());
            return true;
        }
        return false;
    }

    /**
     * Add custom gridhandlerJS for backend
     */
    public function addGridhandlerJs($hookName, $params)
    {
        $templateMgr = $params[0];
        $gridHandlerJs = $this->getJavaScriptURL() . DIRECTORY_SEPARATOR . 'DocMapReviewsGridHandler.js';
        $templateMgr->addJavaScript(
            'DocMapReviewsGridHandlerJs',
            $gridHandlerJs,
            array('contexts' => 'backend')
        );
        return false;
    }

    /**
     * Get the JavaScript URL for this plugin.
     */
    public function getJavaScriptURL()
    {
        return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
    }

    public function getReviewWebContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function validateDocMapPayload($payload)
    {
        if (is_string($payload) || $payload == ["message" => "Invalid DOI requested"] || $payload == ["message" => "No Docmaps available for requested DOI"]) {
            return false;
        }
        return true;
    }

    public function getDocMapReviewsPreference($submissionId)
    {
        /** @var DisplayReviewsPreferenceDAO $displayReviewsPreferenceDAO */
        $displayReviewsPreferenceDAO = DAORegistry::getDAO('DisplayReviewsPreferenceDAO');
        $preference = $displayReviewsPreferenceDAO->getBySubmissionId($submissionId);

        return $preference ? $preference->getData('displayReviews') : false;
    }

    public function fetchDocMapReviewsByGroup($doi)
    {
        $doi = strtolower($doi);
        $url = DOCMAPS_API_URL . $doi . DOCMAPS_JSON_VERSION;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        // close curl resource to free up system resources
        curl_close($ch);
        $data = json_decode($result, true);
        $reviewGroups = [];
        $groupId = 0;

        if ($this->validateDocMapPayload($data)) {
            foreach ($data as $group) {
                $reviewGroups[$groupId]['name'] = $group['publisher']['name'];
                $reviewGroups[$groupId]['logo'] = $group['publisher']['logo'];

                $actions = $group['steps'][$group['first-step']]['actions'];
                $i = 0;

                foreach ($actions as $action) {
                    $date = new DateTime($actions[$i]['outputs'][0]['published']);
                    $formattedDate = $date->format('d M Y');
                    $contentLink = '';

                    foreach ($actions[$i]['outputs'] as $output) {
                        foreach ($output['content'] as $content) {
                            if ($content['type'] == 'web-content') {
                                $contentLink = $content['url'];
                            }
                        }
                    }

                    $reviewGroups[$groupId]['reviews'][$i] = [
                        'id' => sprintf("%d%d", $groupId, $i),
                        'name' => $actions[$i]['participants'][0]['actor']['name'],
                        'published' => $formattedDate,
                        'outputType' => $actions[$i]['outputs'][0]['type'],
                        'link' => $actions[$i]['outputs'][0]['content'][0]['url'],
                        'webContent' => $this->getReviewWebContent($contentLink),
                    ];

                    $i++;
                }

                $groupId++;
            }
        }

        return $reviewGroups;
    }

    public function callbackSharingDisplay($hookName, $params)
    {
        $templateMgr = $params[1];
        $templateOutput = & $params[2];
        $preprint = $templateMgr->getTemplateVars('preprint');
        if (!$preprint) {
            return false;
        }
        $idPreprint = $preprint->getId();

        $shouldDisplayReviews = $this->getDocMapReviewsPreference($idPreprint);

        if ($shouldDisplayReviews) {
            $doi = $this->getDoiById($idPreprint);
            $reviewGroups = $this->fetchDocMapReviewsByGroup($doi);
            $templateMgr->assign([
                'doi' => $doi,
                'idPreprint' => $idPreprint,
                'reviewGroups' => $reviewGroups,
            ]);
            $templateOutput .= $templateMgr->fetch($this->getTemplateResource('docMapReviews.tpl'));
        }

        return false;
    }

}
