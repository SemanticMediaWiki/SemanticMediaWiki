<?php

namespace SMW\MediaWiki\Specials;

use PermissionsError;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;
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
 * @license GPL-2.0-or-later
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
			throw new PermissionsError( 'smw-admin', [ 'smw-admin-permission-missing' ] );
		}

		// https://phabricator.wikimedia.org/T109652#1562641
		$this->getRequest()->setVal(
			'wpEditToken',
			$this->getUser()->getEditToken()
		);

		$this->setHeaders();
		$output = $this->getOutput();

		$output->setPageTitle( $this->msg_text( 'smw-title' ) );
		$output->addHelpLink( $this->msg_text( 'smw-admin-helplink' ), true );

		$output->addModuleStyles( 'ext.smw.styles' );
		$output->addModuleStyles( 'ext.smw.special.styles' );
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

		$taskHandlerFactory->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$taskHandlerRegistry = $taskHandlerFactory->newTaskHandlerRegistry(
			$this->getUser(),
			$adminFeatures
		);

		if ( $query !== null ) {
			$this->getRequest()->setVal( 'action', $query );
		}

		$action = $this->getRequest()->getText( 'action' );

		foreach ( $taskHandlerRegistry->get( TaskHandler::ACTIONABLE ) as $taskHandler ) {
			if ( $taskHandler->isTaskFor( $action ) ) {
				return $taskHandler->handleRequest( $this->getRequest() );
			}
		}

		$output->addHTML(
			$this->buildHTML( $taskHandlerRegistry )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

	private function buildHTML( TaskHandlerRegistry $taskHandlerRegistry ): string {
		$maintenanceSection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_MAINTENANCE ) as $maintenanceTask ) {
			$maintenanceSection .= $maintenanceTask->getHtml();
		}

		$supplementarySection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_SUPPLEMENT ) as $supplementaryTask ) {
			$supplementarySection .= $supplementaryTask->getHtml();
		}

		$alertsSection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_ALERTS ) as $alertTask ) {
			$alertsSection .= $alertTask->getHtml();
		}

		$htmlTabs = new HtmlTabs();

		$supportTaskList = $taskHandlerRegistry->get( TaskHandler::SECTION_SUPPORT );
		$supportTask = end( $supportTaskList );

		$default = $alertsSection === '' ? ( $supportTask->isEnabledFeature( SMW_ADM_SHOW_OVERVIEW ) ? 'general' : 'maintenance' ) : 'alerts';

		// If we want to remain on a specific tab on a GET request, use the `tab`
		// parameter since we are unable to fetch any #href hash from a request
		$htmlTabs->setActiveTab(
			$this->getRequest()->getVal( 'tab', $default )
		);

		$htmlTabs->tab(
			'alerts',
			'<span class="smw-icon-alert smw-tab-icon skin-invert"></span>' . $this->msg_text( 'smw-admin-tab-alerts' ),
			[
				'hide'  => $alertsSection === '',
				'class' => 'smw-tab-warning'
			]
		);

		if ( $supportTask->isEnabledFeature( SMW_ADM_SHOW_OVERVIEW ) ) {
			$supportSection = $supportTask->getHtml();
			$htmlTabs->tab( 'general', $this->msg_text( 'smw-admin-tab-general' ) );
		}

		$htmlTabs->tab( 'maintenance', $this->msg_text( 'smw-admin-tab-maintenance' ) );
		$htmlTabs->tab( 'supplement', $this->msg_text( 'smw-admin-tab-supplement' ) );

		if ( $supportTask->isEnabledFeature( SMW_ADM_SHOW_OVERVIEW ) ) {
			$htmlTabs->content( 'general', $supportSection );
		}

		$htmlTabs->content( 'alerts', $alertsSection );
		$htmlTabs->content( 'maintenance', $maintenanceSection );
		$htmlTabs->content( 'supplement', $supplementarySection );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-admin' ]
		);

		return $html;
	}

	private function msg_text( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
