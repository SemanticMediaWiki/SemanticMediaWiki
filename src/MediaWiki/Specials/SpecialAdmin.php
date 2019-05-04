<?php

namespace SMW\MediaWiki\Specials;

use Html;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Exception\ExtendedPermissionsError;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\Message;
use SMW\Utils\HtmlTabs;
use SpecialPage;

/**
 * This special page for MediaWiki provides an administrative interface
 * that allows to execute certain functions related to the maintenance
 * of the semantic database.
 *
 * Access to the special page and its function is limited to users with the
 * `smw-admin` right.
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class SpecialAdmin extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'SMWAdmin', 'smw-admin' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			// $this->mRestriction is private MW 1.23-
			throw new ExtendedPermissionsError( 'smw-admin', [ 'smw-admin-permission-missing' ] );
		}

		// https://phabricator.wikimedia.org/T109652#1562641
		$this->getRequest()->setVal(
			'wpEditToken',
			$this->getUser()->getEditToken()
		);

		$this->setHeaders();
		$output = $this->getOutput();

		$output->setPageTitle( $this->msg_text( 'smw-title' ) );
		$output->addHelpLink(  $this->msg_text( 'smw-admin-helplink' ), true );

		$output->addModuleStyles( 'ext.smw.special.style' );
		$output->addModules( 'ext.smw.admin' );

		$applicationFactory = ApplicationFactory::getInstance();
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		// Some functions require methods only provided by the SQLStore (or any
		// inherit class thereof)
		if ( !is_a( ( $store = $applicationFactory->getStore() ), '\SMW\SQLStore\SQLStore' ) ) {
			$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );
		}

		$outputFormatter = new OutputFormatter(
			$this->getOutput()
		);

		$adminFeatures = $applicationFactory->getSettings()->get( 'smwgAdminFeatures' );

		// Disable the feature in case the function is not supported
		if ( $applicationFactory->getSettings()->get( 'smwgEnabledFulltextSearch' ) === false ) {
			$adminFeatures = $adminFeatures & ~SMW_ADM_FULLT;
		}

		$taskHandlerFactory = new TaskHandlerFactory(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		$taskHandlerList = $taskHandlerFactory->getTaskHandlerList(
			$this->getUser(),
			$adminFeatures
		);

		if ( $query !== null ) {
			$this->getRequest()->setVal( 'action', $query );
		}

		$action = $this->getRequest()->getText( 'action' );

		foreach ( $taskHandlerList['actions'] as $actionTask ) {
			if ( $actionTask->isTaskFor( $action ) ) {
				return $actionTask->handleRequest( $this->getRequest() );
			}
		}

		$output->addHTML(
			$this->buildHTML( $taskHandlerList )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

	private function buildHTML( $taskHandlerList ) {

		$maintenanceSection = '';

		foreach ( $taskHandlerList[TaskHandler::SECTION_MAINTENANCE] as $maintenanceTask ) {
			$maintenanceSection .= $maintenanceTask->getHtml();
		}

		$supplementarySection = '';

		foreach ( $taskHandlerList[TaskHandler::SECTION_SUPPLEMENT] as $supplementaryTask ) {
			$supplementarySection .= $supplementaryTask->getHtml();
		}

		$deprecationNoticeTaskList = $taskHandlerList[TaskHandler::SECTION_DEPRECATION];
		$deprecationNoticeTaskHandler = end( $deprecationNoticeTaskList );

		$deprecationNotices = $deprecationNoticeTaskHandler->getHtml();
		$htmlTabs = new HtmlTabs();

		$default = $deprecationNotices === '' ? 'general' : 'notices';

		// If we want to remain on a specific tab on a GET request, use the `tab`
		// parameter since we are unable to fetch any #href hash from a request
		$htmlTabs->setActiveTab(
			$this->getRequest()->getVal( 'tab', $default )
		);

		$htmlTabs->tab( 'general', $this->msg_text( 'smw-admin-tab-general' ) );

		$htmlTabs->tab(
			'notices',
			'⚠ ' . $this->msg_text( 'smw-admin-tab-notices' ),
			[
				'hide'  => $deprecationNotices === '' ? true : false,
				'class' => 'smw-tab-warning'
			]
		);

		$htmlTabs->tab( 'maintenance', $this->msg_text( 'smw-admin-tab-maintenance' ) );
		$htmlTabs->tab( 'supplement', $this->msg_text( 'smw-admin-tab-supplement' ) );

		$supportTaskList = $taskHandlerList[TaskHandler::SECTION_SUPPORT];
		$supportSection = end( $supportTaskList )->getHtml();

		$htmlTabs->content( 'general', $supportSection );
		$htmlTabs->content( 'notices', $deprecationNotices );
		$htmlTabs->content( 'maintenance', $maintenanceSection );
		$htmlTabs->content( 'supplement', $supplementarySection );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-admin' ]
		);

		return $html;
	}

	private function msg_text( $key, $type = Message::TEXT) {
		return Message::get( $key, $type , Message::USER_LANGUAGE );
	}

}
