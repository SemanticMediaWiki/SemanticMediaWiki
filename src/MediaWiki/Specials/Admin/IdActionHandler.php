<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\Database;
use SMW\Message;
use SMW\Store;
use Html;
use WebRequest;
use User;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class IdActionHandler {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var integer
	 */
	private $enabledFeatures = 0;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Store $store, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->store = $store;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return ( $this->enabledFeatures & $feature ) != 0;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $enabledFeatures
	 */
	public function setEnabledFeatures( $enabledFeatures ) {
		$this->enabledFeatures = $enabledFeatures;
	}

	/**
	 * @since 2.5
	 *
	 * @param WebRequest $webRequest
	 * @param User|null $user
	 */
	public function performActionWith( WebRequest $webRequest, User $user = null ) {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw-admin-supplementary-idlookup-title' ) );
		$this->outputFormatter->addParentLink();

		$id = (int)$webRequest->getText( 'id' );

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && $id > 0 && $webRequest->getText( 'dispose' ) === 'yes' ) {
			$this->doDispose( $id, $user );
		}

		$this->outputFormatter->addHtml( $this->getForm( $webRequest, $id ) );
	}

	/**
	 * @param integer $id
	 * @param User|null $use
	 */
	private function doDispose( $id, $user = null ) {

		$entityIdDisposerJob = ApplicationFactory::getInstance()->newJobFactory()->newEntityIdDisposerJob(
			\Title::newFromText( __METHOD__ )
		);

		$entityIdDisposerJob->executeWith( $id );

		$manualEntryLogger = ApplicationFactory::getInstance()->create( 'ManualEntryLogger' );
		$manualEntryLogger->registerLoggableEventType( 'admin' );
		$manualEntryLogger->log( 'admin', $user, 'Special:SMWAdmin', 'Forced removal of ID '. $id );
	}

	private function getForm( $webRequest, $id ) {

		$message = $this->getIdInfoAsJson( $webRequest, $id );

		if ( $id < 1 ) {
			$id = null;
		}

		$html = $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHiddenField( 'id', $id )
			->addParagraph( $this->getMessage( 'smw-admin-idlookup-docu' ) )
			->addInputField(
				$this->getMessage( 'smw-admin-objectid' ),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->getMessage( 'allpagessubmit' ) )
			->addParagraph( $message )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) == 'yes' ) {
			$message = $this->getMessage( array ('smw-admin-iddispose-done', $id ) );
			$id = null;
		}

		if ( !$this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			return $html;
		}

		$html .= $this->htmlFormRenderer
			->setName( 'iddispose' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHiddenField( 'id', $id )
			->addHeader( 'h2', $this->getMessage( 'smw-admin-iddispose-title' ) )
			->addParagraph( $this->getMessage( 'smw-admin-iddispose-docu', Message::PARSE ) )
			->addInputField(
				$this->getMessage( 'smw-admin-objectid' ),
				'id',
				$id,
				null,
				20,
				'',
				true
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->getMessage( 'allpagessubmit' ) )
			->addCheckbox(
				$this->getMessage( 'smw_smwadmin_datarefreshstopconfirm', Message::ESCAPED ),
				'dispose',
				'yes'
			)
			->getForm();

		return $html . Html::element( 'p', array(), '' );
	}

	private function getIdInfoAsJson( $webRequest, $id ) {

		if ( $id < 1 || $webRequest->getText( 'action' ) !== 'idlookup' ) {
			return '';
		}

		$references = false;
		$row = $this->store->getConnection( 'mw.db' )->selectRow(
				\SMWSql3SmwIds::TABLE_NAME,
				array(
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey'
				),
				'smw_id=' . $id,
				__METHOD__
		);

		if ( $row !== false ) {
			$references = $this->store->getPropertyTableIdReferenceFinder()->searchAllTablesToFindAtLeastOneReferenceById(
				$id
			);
		}

		$output = '<pre>' . $this->outputFormatter->encodeAsJson( array( $id, $row ) ) . '</pre>';

		if ( $references ) {
			$output .= Html::element( 'p', array(), $this->getMessage( array( 'smw-admin-iddispose-references', $id, count( $references ) ) ) );
			$output .= '<pre>' . $this->outputFormatter->encodeAsJson( $references ) . '</pre>';
		}

		return $output;
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
