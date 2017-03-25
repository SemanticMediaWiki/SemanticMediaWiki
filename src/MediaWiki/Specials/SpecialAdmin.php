<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\Store;
use SpecialPage;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Exception\ExtendedPermissionsError;
use SMW\Message;
use Html;

/**
 * This special page for MediaWiki provides an administrative interface
 * that allows to execute certain functions related to the maintainance
 * of the semantic database. It is restricted to users with siteadmin status.
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

		$this->setHeaders();
		$output = $this->getOutput();
		$output->setPageTitle( $this->getMessageAsString( 'smwadmin' ) );

		$output->addModules( [
			'ext.smw.admin'
		] );

		$action = $query !== null ? $query : $this->getRequest()->getText( 'action' );

		$applicationFactory = ApplicationFactory::getInstance();
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );
		$outputFormatter = new OutputFormatter( $this->getOutput() );

		$adminFeatures = $applicationFactory->getSettings()->get( 'smwgAdminFeatures' );

		// Disable the feature in case the function is not supported
		if ( $applicationFactory->getSettings()->get( 'smwgEnabledFulltextSearch' ) === false ) {
			$adminFeatures = $adminFeatures & ~SMW_ADM_FULLT;
		}

		// Ensure BC for a deprecated setting
		if ( $applicationFactory->getSettings()->get( 'smwgAdminRefreshStore' ) === false ) {
			$adminFeatures = $adminFeatures & ~SMW_ADM_REFRESH;
		}

		$taskHandlerFactory = new TaskHandlerFactory(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		// DataRefreshJobTaskHandler
		$dataRefreshJobTaskHandler = $taskHandlerFactory->newDataRefreshJobTaskHandler();

		$dataRefreshJobTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		// DisposeJobTaskHandler
		$disposeJobTaskHandler = $taskHandlerFactory->newDisposeJobTaskHandler();

		$disposeJobTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		// PropertyStatsRebuildJobTaskHandler
		$propertyStatsRebuildJobTaskHandler = $taskHandlerFactory->newPropertyStatsRebuildJobTaskHandler();

		$propertyStatsRebuildJobTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		// FulltextSearchTableRebuildJobTaskHandler
		$fulltextSearchTableRebuildJobTaskHandler = $taskHandlerFactory->newFulltextSearchTableRebuildJobTaskHandler();

		$fulltextSearchTableRebuildJobTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		// ConfigurationListTaskHandler
		$configurationListTaskHandler = $taskHandlerFactory->newConfigurationListTaskHandler();

		// OperationalStatisticsListTaskHandler
		$operationalStatisticsListTaskHandler = $taskHandlerFactory->newOperationalStatisticsListTaskHandler();

		// TableSchemaTaskHandler
		$tableSchemaTaskHandler = $taskHandlerFactory->newTableSchemaTaskHandler();

		$tableSchemaTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		// IdTaskHandler
		$idTaskHandler = $taskHandlerFactory->newIdTaskHandler();

		$idTaskHandler->setEnabledFeatures(
			$adminFeatures
		);

		$idTaskHandler->setUser(
			$this->getUser()
		);

		// SupportListTaskHandler
		$supportListTaskHandler = $taskHandlerFactory->newSupportListTaskHandler();

		$actionTaskList = [
			$dataRefreshJobTaskHandler,
			$disposeJobTaskHandler,
			$propertyStatsRebuildJobTaskHandler,
			$fulltextSearchTableRebuildJobTaskHandler,
			$tableSchemaTaskHandler,
			$configurationListTaskHandler,
			$operationalStatisticsListTaskHandler,
			$idTaskHandler
		];

		foreach ( $actionTaskList as $actionTask ) {
			if ( $actionTask->isTaskFor( $action ) ) {
				return $actionTask->handleRequest( $this->getRequest() );
			}
		}

		$supplementaryTaskList = [
			$configurationListTaskHandler,
			$operationalStatisticsListTaskHandler,
			$idTaskHandler
		];

		$dataRepairTaskList = [
			$dataRefreshJobTaskHandler,
			$disposeJobTaskHandler,
			$propertyStatsRebuildJobTaskHandler,
			$fulltextSearchTableRebuildJobTaskHandler
		];

		// General intro
		$html = $this->getHtml(
			$tableSchemaTaskHandler,
			$dataRepairTaskList,
			$supplementaryTaskList,
			$supportListTaskHandler
		);

		$output->addHTML( $html );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

	private function getHtml( $tableSchemaTaskHandler, $dataRepairTaskList, $supplementaryTaskList, $supportListTaskHandler ) {

		$html = $this->getMessageAsString( 'smw-admin-docu' );
		$html .= $tableSchemaTaskHandler->getHtml();

		$html .= Html::rawElement( 'h2', [], $this->getMessageAsString( [ 'smw-smwadmin-refresh-title' ] ) );
		$html .= Html::rawElement( 'p', [], $this->getMessageAsString( [ 'smw-admin-job-scheduler-note' ] ) );

		foreach ( $dataRepairTaskList as $dataRepairTask ) {
			$html .= $dataRepairTask->getHtml();
		}

		$html .= Html::rawElement( 'h2', [], $this->getMessageAsString( [ 'smw-admin-supplementary-section-title' ] ) );
		$html .= Html::rawElement( 'p', [], $this->getMessageAsString( [ 'smw-admin-supplementary-section-intro' ] ) );

		$list = '';

		foreach ( $supplementaryTaskList as $supplementaryTask ) {
			$list .= $supplementaryTask->getHtml();
		}

		$html .= Html::rawElement( 'div', [ 'class' => 'smw-admin-supplementary-linksection' ],
			Html::rawElement( 'ul', [],
				$list
			)
		);

		$html .= $supportListTaskHandler->getHtml();

		return $html;
	}

	private function getMessageAsString( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
