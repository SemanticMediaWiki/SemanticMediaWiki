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
class IdHandlerSection {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Database $connection, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->connection = $connection;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @param WebRequest $webRequest
	 * @param User|null $user
	 */
	public function outputActionForm( WebRequest $webRequest, User $user = null ) {

		$this->outputFormatter->setPageTitle( Message::get( 'smw-smwadmin-idlookup-title', Message::TEXT, Message::USER_LANGUAGE ) );
		$this->outputFormatter->addParentLink();

		$id = (int)$webRequest->getText( 'id' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) === 'yes' ) {
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

		$message = $this->getMessage( $webRequest, $id );

		if ( $id < 1 ) {
			$id = null;
		}

		$html = $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHiddenField( 'id', $id )
			->addParagraph( Message::get( 'smw-sp-admin-idlookup-docu', Message::TEXT, Message::USER_LANGUAGE ) )
			->addInputField(
				Message::get( 'smw-sp-admin-objectid' ),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( Message::get( 'allpagessubmit', Message::TEXT, Message::USER_LANGUAGE ) )
			->addParagraph( $message )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) == 'yes' ) {
			$message = Message::get( array ('smw-sp-admin-iddispose-done', $id ), Message::TEXT, Message::USER_LANGUAGE );
			$id = null;
		}

		$html .= $this->htmlFormRenderer
			->setName( 'iddispose' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHiddenField( 'id', $id )
			->addHeader( 'h3', Message::get( 'smw-sp-admin-iddispose-title', Message::TEXT, Message::USER_LANGUAGE ) )
			->addParagraph( Message::get( 'smw-sp-admin-iddispose-docu', Message::PARSE, Message::USER_LANGUAGE ) )
			->addInputField(
				Message::get( 'smw-sp-admin-objectid', Message::TEXT, Message::USER_LANGUAGE ),
				'id',
				$id,
				null,
				20,
				'',
				true
			)
			->addNonBreakingSpace()
			->addSubmitButton( Message::get( 'allpagessubmit', Message::TEXT, Message::USER_LANGUAGE ) )
			->addCheckbox(
				Message::get( 'smw_smwadmin_datarefreshstopconfirm', Message::ESCAPED, Message::USER_LANGUAGE ),
				'dispose',
				'yes'
			)
			->getForm();

		return $html . Html::element( 'p', array(), '' );
	}

	private function getMessage( $webRequest, $id ) {

		if ( $id < 1 || $webRequest->getText( 'action' ) !== 'idlookup' ) {
			return '';
		}

		$row = $this->connection->selectRow(
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

		return '<pre>' . $this->outputFormatter->encodeAsJson( array( $id, $row ) ) . '</pre>';
	}

}
