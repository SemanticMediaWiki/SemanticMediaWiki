<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\Store;
use SpecialPage;
use SMW\MediaWiki\Specials\Admin\Configuration;
use SMW\MediaWiki\Specials\Admin\TableSchemaUpdaterSection;
use SMW\MediaWiki\Specials\Admin\IdActionHandler;
use SMW\MediaWiki\Specials\Admin\SupportSection;
use SMW\MediaWiki\Specials\Admin\DataRepairSection;
use SMW\MediaWiki\Specials\Admin\LinkSection;
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
			throw new ExtendedPermissionsError( 'smw-admin', array( 'smw-smwadmin-permission-missing' ) );
		}

		$output = $this->getOutput();
		$output->setPageTitle( Message::get('smwadmin', Message::TEXT, Message::USER_LANGUAGE ) );

		$applicationFactory = ApplicationFactory::getInstance();
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$store = $applicationFactory->getStore();
		$connection = $store->getConnection( 'mw.db' );
		$outputFormatter = new OutputFormatter( $output );

		$dataRepairSection = new DataRepairSection(
			$connection,
			$htmlFormRenderer,
			$outputFormatter
		);

		$dataRepairSection->enabledRefreshStore(
			$applicationFactory->getSettings()->get( 'smwgAdminRefreshStore' )
		);

		$dataRepairSection->enabledIdDisposal(
			$applicationFactory->getSettings()->get( 'smwgAdminIdDisposal' )
		);

		$linkSection = new LinkSection(
			$htmlFormRenderer,
			$outputFormatter
		);

		$tableSchemaUpdaterSection = new TableSchemaUpdaterSection(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		$tableSchemaUpdaterSection->enabledSetupStore(
			$applicationFactory->getSettings()->get( 'smwgAdminSetupStore' )
		);

		$idActionHandler = new IdActionHandler(
			$store,
			$htmlFormRenderer,
			$outputFormatter
		);

		$idActionHandler->enabledIdDisposal(
			$applicationFactory->getSettings()->get( 'smwgAdminIdDisposal' )
		);

		$supportSection = new SupportSection( $htmlFormRenderer );

		$action = $query !== null ? $query : $this->getRequest()->getText( 'action' );

		// Actions
		switch ( $action ) {
			case 'settings':
				return $linkSection->outputConfigurationList();
			case 'stats':
				return $linkSection->outputStatistics();
			case 'updatetables':
				return $tableSchemaUpdaterSection->doUpdate( $this->getRequest() );
			case 'idlookup':
				return $idActionHandler->performActionWith( $this->getRequest(), $this->getUser() );
			case 'refreshstore':
				return $dataRepairSection->doRefresh( $this->getRequest() );
			case 'dispose':
				return $dataRepairSection->doDispose( $this->getRequest() );
		}

		// General intro
		$html = Message::get( 'smw_smwadmin_docu', Message::TEXT, Message::USER_LANGUAGE );
		$html .= $tableSchemaUpdaterSection->getForm();
		$html .= $dataRepairSection->getForm();
		$html .= $linkSection->getForm();
		$html .= $supportSection->getForm();

		$output->addHTML( $html );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

}
