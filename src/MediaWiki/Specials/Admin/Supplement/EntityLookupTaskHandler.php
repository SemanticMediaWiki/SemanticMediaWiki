<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
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

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-idlookup-title' ),
			[
				'action' => 'lookup'
			]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-idlookup-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle( $this->msg( 'smw-admin-supplementary-idlookup-title' ) );
		$this->outputFormatter->addParentLink( [ 'tab' => 'supplement' ] );

		// https://phabricator.wikimedia.org/T109652#1562641
		if ( !$this->user->matchEditToken( $webRequest->getVal( 'wpEditToken' ) ) ) {
			return $this->outputFormatter->addHtml( $this->msg( 'sessionfailure' ) );
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
			->addParagraph( $error )
			->addHeader( 'h2', $this->msg( 'smw-admin-idlookup-title' ) )
			->addParagraph( $this->msg( 'smw-admin-idlookup-docu' ) )
			->addInputField(
				$this->msg( 'smw-admin-objectid' ),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->msg( 'smw-ask-search' ) )
			->addParagraph( $result )
			->getForm();

		$html .= Html::element( 'p', [], '' );

		if ( $id > 0 && $webRequest->getText( 'dispose' ) == 'yes' ) {
			$result = $this->msg(  ['smw-admin-iddispose-done', $id ] );
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
			->addHeader( 'h2', $this->msg( 'smw-admin-iddispose-title' ) )
			->addParagraph( $this->msg( 'smw-admin-iddispose-docu', Message::PARSE ), [ 'class' => 'plainlinks' ] )
			->addInputField(
				$this->msg( 'smw-admin-objectid' ),
				'id',
				$id,
				null,
				20,
				[ 'disabled' => true ]
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->msg( 'allpagessubmit' ) )
			->addCheckbox(
				$this->msg( 'smw_smwadmin_datarefreshstopconfirm', Message::ESCAPED ),
				'dispose',
				'yes'
			)
			->getForm();

		return $html . Html::element( 'p', [], '' );
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
				[
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey'
				],
				$condition,
				__METHOD__
		);

		return $this->createMessageFromRows( $id, $rows );
	}

	private function createMessageFromRows( &$id, $rows ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$references = [];
		$formattedRows = [];
		$output = '';
		$error = '';

		if ( $rows !== [] ) {
			foreach ( $rows as $row ) {
				$id = $row->smw_id;

				$references[$id] = $this->store->getPropertyTableIdReferenceFinder()->searchAllTablesToFindAtLeastOneReferenceById(
					$id
				);

				$formattedRows[$id] = (array)$row;
				$this->addFulltextInfo( $id, $references );
			}
		}

		// ID is not unique
		if ( count( $formattedRows ) > 1 ) {
			$id = '';
		}

		if ( $formattedRows !== [] ) {
			$output = '<pre>' . $this->outputFormatter->encodeAsJson( $formattedRows ) . '</pre>';
		}
		if ( $references !== [] ) {

			$msg = $id === '' ? 'smw-admin-iddispose-references-multiple' : 'smw-admin-iddispose-references';
			$count = isset( $references[$id] ) ? count( $references[$id] ) + 1 : 0;
			$output .= Html::rawElement(
				'p',
				[],
				$this->msg( [ $msg, $id, $count ], Message::PARSE )
			);
			$output .= '<pre>' . $this->outputFormatter->encodeAsJson( $references ) . '</pre>';
		} else {
			$error .= Html::element(
				'div',
				[
					'class' => 'smw-callout smw-callout-warning'
				],
				$this->msg( [ 'smw-admin-iddispose-no-references', $id ] )
			);

			$id = '';
		}

		return [ $output, $error ];
	}

	private function addFulltextInfo( $id, &$references ) {
		$connection = $this->store->getConnection( 'mw.db' );

		if ( !$connection->tableExists( SQLStore::FT_SEARCH_TABLE ) ) {
			return;
		}

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
