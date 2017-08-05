<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Store;
use SMW\Message;
use SMW\NamespaceManager;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class IdTaskHandler extends TaskHandler {

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
	 * @var User|null
	 */
	private $user;

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
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'idlookup';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function setUser( $user = null ) {
		$this->user = $user;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {
		return Html::rawElement(
			'li',
			array(),
			$this->getMessageAsString(
				array(
					'smw-admin-supplementary-idlookup-intro',
					$this->outputFormatter->getSpecialPageLinkWith( $this->getMessageAsString( 'smw-admin-supplementary-idlookup-title' ), array( 'action' => 'idlookup' ) )
				)
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle( $this->getMessageAsString( 'smw-admin-supplementary-idlookup-title' ) );
		$this->outputFormatter->addParentLink();

		// https://phabricator.wikimedia.org/T109652#1562641
		if ( !$this->user->matchEditToken( $webRequest->getVal( 'wpEditToken' ) ) ) {
			return $this->outputFormatter->addHtml( $this->getMessageAsString( 'sessionfailure' ) );
		}

		$id = $webRequest->getText( 'id' );

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && $id > 0 && $webRequest->getText( 'dispose' ) === 'yes' ) {
			$this->doDispose( $id );
		}

		$this->outputFormatter->addHtml( $this->getForm( $webRequest, $id ) );
	}

	/**
	 * @param integer $id
	 * @param User|null $use
	 */
	private function doDispose( $id ) {

		$entityIdDisposerJob = ApplicationFactory::getInstance()->newJobFactory()->newEntityIdDisposerJob(
			\Title::newFromText( __METHOD__ )
		);

		$entityIdDisposerJob->dispose( intval( $id ) );

		$manualEntryLogger = ApplicationFactory::getInstance()->create( 'ManualEntryLogger' );
		$manualEntryLogger->registerLoggableEventType( 'admin' );
		$manualEntryLogger->log( 'admin', $this->user, 'Special:SMWAdmin', 'Forced removal of ID '. $id );
	}

	private function getForm( $webRequest, $id ) {

		$message = $this->createInfoMessageById( $webRequest, $id );

		if ( $id < 1 ) {
			$id = null;
		}

		$html = $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHiddenField( 'id', $id )
			->addParagraph( $this->getMessageAsString( 'smw-admin-idlookup-docu' ) )
			->addInputField(
				$this->getMessageAsString( 'smw-admin-objectid' ),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->getMessageAsString( 'allpagessubmit' ) )
			->addParagraph( $message )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) == 'yes' ) {
			$message = $this->getMessageAsString( array ('smw-admin-iddispose-done', $id ) );
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
			->addHeader( 'h2', $this->getMessageAsString( 'smw-admin-iddispose-title' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-iddispose-docu', Message::PARSE ) )
			->addInputField(
				$this->getMessageAsString( 'smw-admin-objectid' ),
				'id',
				$id,
				null,
				20,
				'',
				true
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->getMessageAsString( 'allpagessubmit' ) )
			->addCheckbox(
				$this->getMessageAsString( 'smw_smwadmin_datarefreshstopconfirm', Message::ESCAPED ),
				'dispose',
				'yes'
			)
			->getForm();

		return $html . Html::element( 'p', array(), '' );
	}

	private function createInfoMessageById( $webRequest, &$id ) {

		if ( $webRequest->getText( 'action' ) !== 'idlookup' || $id === '' ) {
			return '';
		}

		$connection = $this->store->getConnection( 'mw.db' );

		if ( intval( $id ) ) {
			$condition = 'smw_id=' . intval( $id );
		} else {
			$condition = 'smw_sortkey=' . $connection->addQuotes( $id );
		}

		$rows = $connection->select(
				\SMWSql3SmwIds::TABLE_NAME,
				array(
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey'
				),
				$condition,
				__METHOD__
		);

		return $this->createMessageFromRows( $id, $rows );
	}

	private function createMessageFromRows( &$id, $rows ) {

		$references = array();
		$formattedRows = array();
		$output = '';

		if ( $rows !== array() ) {
			foreach ( $rows as $row ) {
				$id = $row->smw_id;

				$references[$id] = $this->store->getPropertyTableIdReferenceFinder()->searchAllTablesToFindAtLeastOneReferenceById(
					$id
				);

				$formattedRows[$id] = (array)$row;
			}
		}

		// ID is not unique
		if ( count( $formattedRows ) > 1 ) {
			$id = '';
		}

		if ( $formattedRows !== array() ) {
			$output = '<pre>' . $this->outputFormatter->encodeAsJson( $formattedRows ) . '</pre>';
		}

		if ( $references !== array() ) {
			$output .= Html::element(
				'p',
				array(),
				$this->getMessageAsString( array( 'smw-admin-iddispose-references', $id, count( $references ) ) )
			);
			$output .= '<pre>' . $this->outputFormatter->encodeAsJson( $references ) . '</pre>';
		}

		return $output;
	}

}
