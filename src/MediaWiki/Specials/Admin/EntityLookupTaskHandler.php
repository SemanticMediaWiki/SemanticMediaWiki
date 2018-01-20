<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Store;
use SMW\SQLStore\SQLStore;
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
class EntityLookupTaskHandler extends TaskHandler {

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
		return $task === 'lookup';
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
					$this->outputFormatter->getSpecialPageLinkWith( $this->getMessageAsString( 'smw-admin-supplementary-idlookup-title' ), array( 'action' => 'lookup' ) )
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
		$this->outputFormatter->addParentLink( [ 'tab' => 'supplement' ] );

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

		$applicationFactory = ApplicationFactory::getInstance();

		$entityIdDisposerJob = $applicationFactory->newJobFactory()->newEntityIdDisposerJob(
			\Title::newFromText( __METHOD__ )
		);

		$entityIdDisposerJob->dispose( intval( $id ) );

		$manualEntryLogger = $applicationFactory->create( 'ManualEntryLogger' );
		$manualEntryLogger->registerLoggableEventType( 'admin' );
		$manualEntryLogger->log( 'admin', $this->user, 'Special:SMWAdmin', 'Forced removal of ID '. $id );
	}

	private function getForm( $webRequest, $id ) {

		list( $result, $error ) = $this->createInfoMessageById( $webRequest, $id );

		if ( $id < 1 ) {
			$id = null;
		}

		$html = $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'lookup' )
			->addParagraph( $error . $this->getMessageAsString( 'smw-admin-idlookup-docu' ) )
			->addInputField(
				$this->getMessageAsString( 'smw-admin-idlookup-input' ),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->getMessageAsString( 'allpagessubmit' ) )
			->addParagraph( $result )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) == 'yes' ) {
			$result = $this->getMessageAsString( array ('smw-admin-iddispose-done', $id ) );
			$id = null;
		}

		if ( !$this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			return $html;
		}

		$html .= $this->htmlFormRenderer
			->setName( 'iddispose' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'lookup' )
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

		if ( $webRequest->getText( 'action' ) !== 'lookup' || $id === '' ) {
			return [ '', '' ];
		}

		$connection = $this->store->getConnection( 'mw.db' );

		if ( ctype_digit( $id ) ) {
			$condition = 'smw_id=' . intval( $id );
		} else {
			$op = strpos( $id, '*' ) !== false ? ' LIKE ' : '=';
			$condition = "smw_sortkey $op " . $connection->addQuotes( str_replace( [ '_', '*' ], [ ' ', '%' ], $id ) );
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

		$connection = $this->store->getConnection( 'mw.db' );

		$references = array();
		$formattedRows = array();
		$output = '';
		$error = '';

		if ( $rows !== array() ) {
			foreach ( $rows as $row ) {
				$id = $row->smw_id;

				$references[$id] = $this->store->getPropertyTableIdReferenceFinder()->searchAllTablesToFindAtLeastOneReferenceById(
					$id
				);

				$formattedRows[$id] = (array)$row;

				$row = $connection->selectRow(
						SQLStore::FT_SEARCH_TABLE,
						[
							's_id',
							'p_id',
							'o_text'
						],
						[
							's_id' => $id
						],
						__METHOD__
				);

				if ( $row !== false ) {
					$references[$id][SQLStore::FT_SEARCH_TABLE] = (array)$row;
				}
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
			$msg = $id === '' ? 'smw-admin-iddispose-references-multiple' : 'smw-admin-iddispose-references';
			$output .= Html::element(
				'p',
				array(),
				$this->getMessageAsString( array( $msg, $id, count( $references ) ) )
			);
			$output .= '<pre>' . $this->outputFormatter->encodeAsJson( $references ) . '</pre>';
		} else {
			$error .= Html::element(
				'div',
				array(
					'class' => 'smw-callout smw-callout-warning'
				),
				$this->getMessageAsString( array( 'smw-admin-iddispose-no-references', $id ) )
			);

			$id = '';
		}

		return [ $output, $error ];
	}

}
