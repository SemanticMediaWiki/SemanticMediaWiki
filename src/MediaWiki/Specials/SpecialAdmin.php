<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\Store;
use SpecialPage;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Exception\ExtendedPermissionsError;
use SMW\Message;
use SMW\Utils\HtmlVTabs;
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
			throw new ExtendedPermissionsError( 'smw-admin', array( 'smw-admin-permission-missing' ) );
		}

		// https://phabricator.wikimedia.org/T109652#1562641
		$this->getRequest()->setVal(
			'wpEditToken',
			$this->getUser()->getEditToken()
		);

		HtmlVTabs::init();

		$this->setHeaders();
		$output = $this->getOutput();
		$output->setPageTitle( $this->getMessageAsString( 'smwadmin' ) );

		$output->addModuleStyles( array(
			'ext.smw.special.style'
		) );

		$output->addModuleStyles( HtmlVTabs::getModuleStyles() );

		$output->addModules( array(
			'ext.smw.admin'
		) );

		$output->addModules( HtmlVTabs::getModules() );

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

		$taskHandlerFactory = new TaskHandlerFactory(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		// DeprecationNoticeTaskHandler
		$deprecationNoticeTaskHandler = $taskHandlerFactory->newDeprecationNoticeTaskHandler();

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

		$actionTaskList = array(
			$dataRefreshJobTaskHandler,
			$disposeJobTaskHandler,
			$propertyStatsRebuildJobTaskHandler,
			$fulltextSearchTableRebuildJobTaskHandler,
			$tableSchemaTaskHandler,
			$configurationListTaskHandler,
			$operationalStatisticsListTaskHandler,
			$idTaskHandler
		);

		foreach ( $actionTaskList as $actionTask ) {
			if ( $actionTask->isTaskFor( $action ) ) {
				return $actionTask->handleRequest( $this->getRequest() );
			}
		}

		$supplementaryTaskList = array(
			$configurationListTaskHandler,
			$operationalStatisticsListTaskHandler,
			$idTaskHandler
		);

		$dataRepairTaskList = array(
			$dataRefreshJobTaskHandler,
			$disposeJobTaskHandler,
			$propertyStatsRebuildJobTaskHandler,
			$fulltextSearchTableRebuildJobTaskHandler
		);

		// General intro
		$html = $this->getHtml(
			$deprecationNoticeTaskHandler,
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

	private function getHtml( $deprecationNoticeTaskHandler, $tableSchemaTaskHandler, $dataRepairTaskList, $supplementaryTaskList, $supportListTaskHandler ) {

		$dataRebuildSection = $tableSchemaTaskHandler->getHtml();
		$dataRebuildSection .= Html::rawElement(
			'hr',
			array( 'style' => 'margin-top:20px; background-color: #ddd;' ),
			''
		)  . Html::rawElement(
			'h3',
			array(),
			$this->getMessageAsString( array( 'smw-smwadmin-refresh-title' ) )
		) . Html::rawElement(
			'p',
			array(),
			$this->getMessageAsString( array( 'smw-admin-job-scheduler-note' ) )
		);

		$list = '';

		foreach ( $dataRepairTaskList as $dataRepairTask ) {
			$list .= $dataRepairTask->getHtml();
		}

		$dataRebuildSection .= Html::rawElement( 'div', array( 'class' => 'smw-admin-data-repair-section' ),
			$list
		);

		$supplementarySection = Html::rawElement(
			'h3',
			array(),
			$this->getMessageAsString( array( 'smw-admin-supplementary-section-title' ) )
		)  . Html::rawElement(
			'p',
			array(),
			$this->getMessageAsString( array( 'smw-admin-supplementary-section-intro' ) )
		);

		$list = '';

		foreach ( $supplementaryTaskList as $supplementaryTask ) {
			$list .= $supplementaryTask->getHtml();
		}

		$supplementarySection .= Html::rawElement( 'div', array( 'class' => 'smw-admin-supplementary-section' ),
			Html::rawElement( 'ul', array(),
				$list
			)
		);

		$deprecationNotices = $deprecationNoticeTaskHandler->getHtml();
		$isHidden = $deprecationNotices === '' ? HtmlVTabs::IS_HIDDEN : false;

		$tab = 'general';

		// If we want to remain on a specifc tab on a GET request, use the `tab`
		// parameter since we are unable to fetch any #href hash from a request
		if ( $this->getRequest()->getVal( 'tab' ) ) {
			$tab = $this->getRequest()->getVal( 'tab' );
		}

		$findActiveLink = [ HtmlVTabs::FIND_ACTIVE_LINK => $tab ];

		// Navigation tabs
		$html = HtmlVTabs::nav(
			HtmlVTabs::navLink(
				'general',
				$this->getMessageAsString( 'smw-admin-tab-general' ),
				$findActiveLink
			) . HtmlVTabs::navLink(
				'notices',
				$this->getMessageAsString( 'smw-admin-tab-notices' ),
				$isHidden,
				[ 'class' => 'smw-vtab-warning' ]
			) . HtmlVTabs::navLink(
				'rebuild',
				$this->getMessageAsString( 'smw-admin-tab-rebuild' ),
				$findActiveLink
			) . HtmlVTabs::navLink(
				'supplement',
				$this->getMessageAsString( 'smw-admin-tab-supplement' ),
				$findActiveLink
			) . HtmlVTabs::navLink(
				'registry',
				$this->getMessageAsString( 'smw-admin-tab-registry' ),
				$findActiveLink
			)
		);

		// Content
		$html .= HtmlVTabs::content(
			'general',
			Html::rawElement(
				'p',
				array(),
				$this->getMessageAsString( 'smw-admin-docu' )
			) . $supportListTaskHandler->createSupportForm()
		);

		$html .= HtmlVTabs::content(
			'notices',
			$deprecationNotices
		);

		$html .= HtmlVTabs::content(
			'rebuild',
			$dataRebuildSection
		);

		$html .= HtmlVTabs::content(
			'supplement',
			$supplementarySection
		);

		$html .= HtmlVTabs::content(
			'registry',
			$supportListTaskHandler->createRegistryForm()
		);

		return $html;
	}

	private function getMessageAsString( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
