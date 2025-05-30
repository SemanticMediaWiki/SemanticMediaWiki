<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\Localizer\Message;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use WebRequest;

/**
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class EntityLookupTaskHandler extends TaskHandler implements ActionableTask {

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
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getTask(): string {
		return 'lookup';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( string $action ): bool {
		return $action === $this->getTask();
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
			$this->msg( 'smw-admin-supplementary-idlookup-short-title' ),
			[
				'action' => $this->getTask()
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
		$this->outputFormatter->setPageTitle(
			$this->msg( [ 'smw-admin-main-title', $this->msg( 'smw-admin-supplementary-idlookup-title' ) ] )
		);

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
	 * @param int $id
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
		$manualEntryLogger->log( 'admin', $this->user, 'Special:SMWAdmin', 'Forced removal of ID ' . $id );
	}

	private function getForm( $webRequest, $id ) {
		[ $result, $error ] = $this->createInfoMessageById( $webRequest, $id );

		if ( $id < 1 ) {
			$id = null;
		}

		$html = $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'lookup' )
			->addParagraph( $error )
			->addHeader( 'h2', $this->msg( 'smw-admin-idlookup-title' ), [ 'class' => 'smw-title' ] )
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
			$result = $this->msg( [ 'smw-admin-iddispose-done', $id ] );
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
			->addHeader( 'h2', $this->msg( 'smw-admin-iddispose-title' ), [ 'class' => 'smw-title' ] )
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

		if ( ctype_digit( (string)$id ) ) {
			$condition = 'smw_id=' . intval( $id );
		} else {
			$op = strpos( $id, '*' ) !== false ? ' LIKE ' : '=';
			$condition = "smw_sortkey $op " . $connection->addQuotes( str_replace( [ '_', '*' ], [ ' ', '%' ], $id ) );
		}

		$rows = $connection->select(
				SQLStore::ID_TABLE,
				[
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey',
					'smw_proptable_hash',
					'smw_rev',
					'smw_touched'
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

				$row->smw_proptable_hash = $row->smw_proptable_hash === null ? null : '...';

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
			$error .= Html::warningBox(
				$this->msg( [ 'smw-admin-iddispose-no-references', $id ] )
			);

			$id = '';
		}

		return [ $output, $error ];
	}

	private function addFulltextInfo( $id, &$references ) {
		$connection = $this->store->getConnection( 'mw.db' );

		if ( !$connection->tableExists( SQLStore::FT_SEARCH_TABLE, __METHOD__ ) ) {
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
