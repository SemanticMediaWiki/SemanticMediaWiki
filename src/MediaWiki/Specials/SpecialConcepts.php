<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\Navigation\PagerNavigationBuilder;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\RequestOptions;
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

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store
	) {
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

		$cursorMode = self::shouldUseCursorMode( $request->getVal( 'offset', null ) );

		if ( $cursorMode ) {
			$options = new RequestOptions();
			$options->limit = $limit;
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

		$this->addHelpLink( $this->msg( 'smw-helplink-concepts' )->text(), true );

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
		$qb = $this->newConceptQueryBuilder( $connection, __METHOD__ );

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

		$res = $this->newConceptQueryBuilder( $connection, __METHOD__ )
			->options( [
				'LIMIT' => $limit + 1,
				'OFFSET' => $offset,
			] )
			->fetchResultSet();

		$results = [];
		foreach ( $res as $row ) {
			$results[] = new WikiPage( $row->smw_title, SMW_NS_CONCEPT );
		}

		return $results;
	}

	/**
	 * Shared base query for both `doCursorFetch()` and `fetchFromTable()`:
	 * the JOIN onto `smw_object_ids` and the production filter. Each caller
	 * appends its own LIMIT/OFFSET/ORDER BY (legacy via the options array,
	 * cursor via the trait).
	 */
	private function newConceptQueryBuilder( $connection, string $caller ) {
		return $connection->newSelectQueryBuilder()
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
			->caller( $caller );
	}

	/**
	 * @since 1.9
	 *
	 * @param WikiPage[] $dataItems
	 * @param int $limit
	 * @param int $offset
	 * @param RequestOptions|null $cursorOptions When non-null, the pager is
	 *   rendered in cursor mode using `PagerNavigationBuilder` and the cursor
	 *   metadata on `$cursorOptions`. When null, the legacy
	 *   offset pager (`Pager::pagination`) is rendered.
	 *
	 * @return string
	 */
	public function getHtml( array $dataItems, $limit, $offset, ?RequestOptions $cursorOptions = null ): string {
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
			$isFirstPage = !$cursorOptions->hasCursor();
			$navBuilder = new PagerNavigationBuilder( RequestContext::getMain() );
			$navBuilder
				->setPage( $this->getPageTitle() )
				->setLinkQuery( [ 'limit' => $limit ] )
				->setLimitLinkQueryParam( 'limit' )
				->setCurrentLimit( $limit )
				->setPrevTooltipMsg( 'prevn-title' )
				->setNextTooltipMsg( 'nextn-title' )
				->setLimitTooltipMsg( 'shown-title' );

			$isBackward = $cursorOptions->getCursorBefore() !== null;
			$isAtEnd = !$cursorOptions->getCursorHasMore();
			$showPrev = $isBackward ? !$isAtEnd : true;
			$showNext = $isBackward ? true : !$isAtEnd;

			if ( $showPrev && !$isFirstPage && $cursorOptions->getFirstCursor() !== null ) {
				$navBuilder->setPrevLinkQuery( [ 'before' => (string)$cursorOptions->getFirstCursor() ] );
			}

			if ( $showNext && $cursorOptions->getLastCursor() !== null ) {
				$navBuilder->setNextLinkQuery( [ 'after' => (string)$cursorOptions->getLastCursor() ] );
			}

			$paginationHtml = $navBuilder->getHtml();
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
			) . Html::rawElement(
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
	 * Decides whether the request should be served via the cursor path or
	 * the legacy offset path. Cursor mode is the default; the legacy path
	 * is taken whenever the `offset` URL param is present at all, even if
	 * its value is empty, garbage, or negative. The reasoning: an `offset=`
	 * param signals offset semantics on the client side, so we honour the
	 * intent rather than silently switching modes when the value coerces
	 * to 0.
	 *
	 * Extracted as a static helper so the predicate can be unit-tested
	 * without spinning up the full request context.
	 *
	 * @since 7.0.0
	 *
	 * @param string|null $offsetParamValue The raw value of `?offset=` from
	 *   the request, or null if the param is absent.
	 */
	public static function shouldUseCursorMode( ?string $offsetParamValue ): bool {
		return $offsetParamValue === null;
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
