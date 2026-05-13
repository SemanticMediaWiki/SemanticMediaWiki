<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\MessageBuilder;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\RequestOptions;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Lookup\KeysetPaginationTrait;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\HtmlTabs;
use SMW\Utils\Pager;

/**
 * Special page that lists available concepts
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class SpecialConcepts extends SpecialPage {

	use KeysetPaginationTrait;

	private ?Store $store = null;

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'Concepts' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ): void {
		$this->setHeaders();
		$out = $this->getOutput();
		$out->addModuleStyles( [
			'ext.smw.styles',
			'ext.smw.page.styles'
		] );

		$request = $this->getRequest();
		$limit = (int)$request->getVal( 'limit', 50 );
		$offset = (int)$request->getVal( 'offset', 0 );
		$after = $request->getInt( 'after', 0 );
		$before = $request->getInt( 'before', 0 );

		$this->store = ApplicationFactory::getInstance()->getStore();

		// Cursor mode unless a legacy `?offset=N` URL is in play. Bots and
		// fresh visitors fall into cursor mode; deep-indexed legacy URLs
		// continue to render via the offset path.
		$cursorMode = $offset === 0;

		if ( $cursorMode ) {
			$options = new RequestOptions();
			$options->limit = $limit;
			$options->setOption( RequestOptions::CURSOR_MODE, true );
			if ( $after > 0 ) {
				$options->setCursorAfter( $after );
			} elseif ( $before > 0 ) {
				$options->setCursorBefore( $before );
			}
			$diWikiPages = $this->doCursorFetch( $options );
			$html = $this->getHtml( $diWikiPages, $limit, $offset, $options );
		} else {
			$diWikiPages = $this->fetchFromTable( $limit, $offset );
			$html = $this->getHtml( $diWikiPages, $limit, $offset );
		}

		$this->addHelpLink( $this->msg( 'smw-helplink-concepts' )->escaped(), true );

		$out->setPageTitle( $this->msg( 'concepts' )->text() );
		$out->addHTML( $html );
	}

	/**
	 * Cursor-aware fetch used by `execute()` when on the cursor URL path.
	 * Applies `KeysetPaginationTrait::applyCursorPagination()` to the same
	 * underlying query that `fetchFromTable()` emits, plus a `LIMIT + 1`
	 * lookahead. Populates cursor metadata (`firstCursor`, `lastCursor`,
	 * `cursorHasMore`) on the caller's `RequestOptions` for the pager
	 * renderer to read.
	 *
	 * @since 7.0.0
	 */
	private function doCursorFetch( RequestOptions $options ): array {
		$connection = $this->store->getConnection( 'mw.db' );

		$qb = $connection->newSelectQueryBuilder()
			->select( [ 'smw_id', 'smw_title' ] )
			->tables( [
				SQLStore::ID_TABLE,
				SQLStore::CONCEPT_TABLE
			] )
			->joinConds( [
				SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=s_id' ] ]
			] )
			->where( [
				'smw_namespace' => SMW_NS_CONCEPT,
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_proptable_hash IS NOT NULL',
				'concept_features > 0'
			] )
			->caller( __METHOD__ );

		if ( $options->limit > 0 ) {
			$qb->limit( $options->limit + 1 );
		}
		$this->applyCursorPagination( $qb, $connection, $options );

		$rows = [];
		foreach ( $qb->fetchResultSet() as $row ) {
			$rows[] = $row;
		}

		if ( $options->limit > 0 && count( $rows ) > $options->limit ) {
			array_pop( $rows );
			$options->setCursorHasMore( true );
		}

		if ( $options->getCursorBefore() !== null ) {
			$rows = array_reverse( $rows );
		}

		if ( $rows !== [] ) {
			$options->setFirstCursor( (int)$rows[0]->smw_id );
			$options->setLastCursor( (int)$rows[count( $rows ) - 1]->smw_id );
		}

		$results = [];
		foreach ( $rows as $row ) {
			$results[] = new WikiPage( $row->smw_title, SMW_NS_CONCEPT );
		}

		return $results;
	}

	/**
	 * @since 1.9
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return WikiPage[]
	 */
	public function fetchFromTable( $limit, $offset ): array {
		$connection = $this->store->getConnection( 'mw.db' );
		$results = [];

		$fields = [
			'smw_id',
			'smw_title'
		];

		$conditions = [
			'smw_namespace' => SMW_NS_CONCEPT,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash IS NOT NULL',
			'concept_features > 0'
		];

		$options = [
			'LIMIT' => $limit + 1,
			'OFFSET' => $offset,
		];

		$res = $connection->newSelectQueryBuilder()
			->select( $fields )
			->tables( [
				SQLStore::ID_TABLE,
				SQLStore::CONCEPT_TABLE
			] )
			->joinConds( [
				SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=s_id' ] ]
			] )
			->where( $conditions )
			->options( $options )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $res as $row ) {
			$results[] = new WikiPage( $row->smw_title, SMW_NS_CONCEPT );
		}

		return $results;
	}

	/**
	 * @since 1.9
	 *
	 * @param WikiPage[] $dataItems
	 * @param int $limit
	 * @param int $offset
	 * @param RequestOptions|null $cursorOptions When non-null, the pager is
	 *   rendered in cursor mode using `MessageBuilder::cursorPrevNextToText`
	 *   and the cursor metadata on `$cursorOptions`. When null, the legacy
	 *   offset pager (`Pager::pagination`) is rendered.
	 *
	 * @return string
	 */
	public function getHtml( array $dataItems, $limit, $offset, ?RequestOptions $cursorOptions = null ): string {
		if ( $this->store === null ) {
			$this->store = ApplicationFactory::getInstance()->getStore();
		}

		$count = count( $dataItems );
		$resultNumber = min( $limit, $count );

		if ( $resultNumber == 0 ) {
			$key = 'smw-special-concept-empty';
		} else {
			$key = 'smw-special-concept-count';
		}

		$listBuilder = new ListBuilder(
			$this->store,
			Collator::singleton()
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'concept' );

		$htmlTabs->isRTL(
			$this->getLanguage()->isRTL()
		);

		if ( $cursorOptions !== null ) {
			$msgBuilder = new MessageBuilder( $this->getLanguage() );
			$isFirstPage = !$cursorOptions->hasCursor();
			$paginationHtml = $msgBuilder->cursorPrevNextToText(
				$this->getPageTitle(),
				$limit,
				$isFirstPage ? null : $cursorOptions->getFirstCursor(),
				$cursorOptions->getLastCursor(),
				[],
				!$cursorOptions->getCursorHasMore(),
				$cursorOptions->getCursorBefore() !== null
			);
		} else {
			$paginationHtml = Pager::pagination( $this->getPageTitle(), $limit, $offset, $count );
		}

		$html = Html::rawElement(
				'div',
				[ 'id' => 'mw-pages' ],
			Html::rawElement(
				'div',
				[ 'class' => 'smw-page-navigation' ],
				$paginationHtml
			) . Html::element(
				'div',
				[ 'class' => $key, 'style' => 'margin-top:10px;margin-bottom:10px;' ],
				$this->msg( $key, $resultNumber )->parse()
			) . $listBuilder->getColumnList( $dataItems )
		);

		$htmlTabs->tab( 'smw-concept-list', $this->msg( 'smw-concept-tab-list' ) );
		$htmlTabs->content( 'smw-concept-list', $html );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-concept clearfix' ]
		);

		return Html::rawElement(
			'p',
			[ 'class' => 'smw-special-concept-docu plainlinks' ],
			$this->msg( 'smw-special-concept-docu' )->parse()
		) . $html;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/properties-concepts-types';
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( SpecialConcepts::class, 'SMW\SpecialConcepts' );
