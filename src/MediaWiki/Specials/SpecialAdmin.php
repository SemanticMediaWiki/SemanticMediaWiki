<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\Store;
use SpecialPage;
use SMW\MediaWiki\Specials\Admin\Configuration;
use SMW\MediaWiki\Specials\Admin\TableSchemaActionHandler;
use SMW\MediaWiki\Specials\Admin\IdActionHandler;
use SMW\MediaWiki\Specials\Admin\SupportWidget;
use SMW\MediaWiki\Specials\Admin\DataRepairActionHandler;
use SMW\MediaWiki\Specials\Admin\SupplementaryLinksActionHandler;
use SMW\MediaWiki\Specials\Admin\SupplementaryLinksWidget;
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
			throw new ExtendedPermissionsError( 'smw-admin', array( 'smw-admin-permission-missing' ) );
		}

		$output = $this->getOutput();
		$output->setPageTitle( Message::get( 'smwadmin', Message::TEXT, Message::USER_LANGUAGE ) );

		$output->addModuleStyles( array(
			'mediawiki.ui',
			'mediawiki.ui.button',
			'mediawiki.ui.input'
		) );

		$output->addModules( array(
			'ext.smw.admin'
		) );

		$applicationFactory = ApplicationFactory::getInstance();

		list(
			$dataRepairActionHandler,
			$supplementaryLinksWidget,
			$supplementaryLinksActionHandler,
			$tableSchemaActionHandler,
			$idActionHandler,
			$supportWidget
		) = $this->getHandlers(
			$applicationFactory->getStore( '\SMW\SQLStore\SQLStore' ),
			$applicationFactory->getSettings(),
			$applicationFactory->newMwCollaboratorFactory()
		);

		$action = $query !== null ? $query : $this->getRequest()->getText( 'action' );

		// Actions
		switch ( $action ) {
			case 'settings':
				return $supplementaryLinksActionHandler->doOutputConfigurationList();
			case 'stats':
				return $supplementaryLinksActionHandler->doOutputStatistics();
			case 'updatetables':
				return $tableSchemaActionHandler->doUpdate( $this->getRequest() );
			case 'idlookup':
				return $idActionHandler->performActionWith( $this->getRequest(), $this->getUser() );
			case 'refreshstore':
				return $dataRepairActionHandler->doRefresh( $this->getRequest() );
			case 'dispose':
				return $dataRepairActionHandler->doDispose();
			case 'pstatsrebuild':
				return $dataRepairActionHandler->doPropertyStatsRebuild();
			case 'fulltrebuild':
				return $dataRepairActionHandler->doFulltextSearchTableRebuild();
		}

		// General intro
		$html = Message::get( 'smw-admin-docu', Message::TEXT, Message::USER_LANGUAGE );
		$html .= $tableSchemaActionHandler->getForm();
		$html .= $dataRepairActionHandler->getForm();
		$html .= $supplementaryLinksWidget->getForm();
		$html .= $supportWidget->getForm();

		$output->addHTML( $html );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

	private function getHandlers( $store, $settings, $mwCollaboratorFactory ) {

		$htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$connection = $store->getConnection( 'mw.db' );
		$outputFormatter = new OutputFormatter( $this->getOutput() );

		$adminFeatures = $settings->get( 'smwgAdminFeatures' );

		// Disable the feature in case the function is not supported
		if ( $settings->get( 'smwgEnabledFulltextSearch' ) === false ) {
			$adminFeatures = $adminFeatures & ~SMW_ADM_FULLT;
		}

		// Ensure BC for a deprecated setting
		if ( $settings->get( 'smwgAdminRefreshStore' ) === false ) {
			$adminFeatures = $adminFeatures & ~SMW_ADM_REFRESH;
		}

		$dataRepairActionHandler = new DataRepairActionHandler(
			$connection,
			$htmlFormRenderer,
			$outputFormatter
		);

		$dataRepairActionHandler->setEnabledFeatures(
			$adminFeatures
		);

		$supplementaryLinksWidget = new SupplementaryLinksWidget(
			$outputFormatter
		);

		$supplementaryLinksActionHandler = new SupplementaryLinksActionHandler(
			$outputFormatter
		);

		$tableSchemaActionHandler = new TableSchemaActionHandler(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		$tableSchemaActionHandler->setEnabledFeatures(
			$adminFeatures
		);

		$idActionHandler = new IdActionHandler(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		$idActionHandler->setEnabledFeatures(
			$adminFeatures
		);

		$supportWidget = new SupportWidget( $htmlFormRenderer );

		return array(
			$dataRepairActionHandler,
			$supplementaryLinksWidget,
			$supplementaryLinksActionHandler,
			$tableSchemaActionHandler,
			$idActionHandler,
			$supportWidget
		);
	}

}
