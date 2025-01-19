<?php

namespace SMW\MediaWiki\Page;

use Html;
use SMW\DIConcept;
use SMW\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\Utils\HtmlTabs;
use SMW\Utils\Pager;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptPage extends Page {

	/**
	 * @see Page::initParameters()
	 *
	 * @note We use a smaller limit here; property pages might become large.
	 */
	protected function initParameters(): void {
		$this->limit = $this->getOption( 'pagingLimit' );
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 */
	protected function getHtml(): string {
		$context = $this->getContext();
		$context->getOutput()->addModuleStyles( [
			'ext.smw.styles',
			'ext.smw.page.styles'
		] );

		$request = $context->getRequest();
		$store = ApplicationFactory::getInstance()->getStore();

		$limit = (int)$request->getVal( 'limit', $this->getOption( 'pagingLimit' ) );
		$offset = (int)$request->getVal( 'offset', '0' );

		// limit==0: configuration setting to disable this completely
		if ( $this->limit > 0 ) {
			$dataItem = $this->getDataItem();
			$descriptionFactory = ApplicationFactory::getInstance()->getQueryFactory()->newDescriptionFactory();

			$description = $descriptionFactory->newConceptDescription( $dataItem );
			$query = \SMWPageLister::getQuery( $description, $this->limit, $this->from, $this->until );

			$query->setLimit( $limit );
			$query->setOffset( $offset );
			$query->setContextPage( $dataItem );
			$query->setOption( $query::NO_DEPENDENCY_TRACE, true );
			$query->setOption( $query::NO_CACHE, true );

			$queryResult = $store->getQueryResult( $query );

			$diWikiPages = $queryResult->getResults();

			if ( $this->until !== '' ) {
				$diWikiPages = array_reverse( $diWikiPages );
			}

			$errors = $queryResult->getErrors();
		} else {
			$diWikiPages = [];
			$errors = [];
		}

		// Make navigation point to the result list.
		$title = $this->getTitle();
		$title->setFragment( '#smw-result' );
		$isRTL = $context->getLanguage()->isRTL();

		$resultCount = count( $diWikiPages );

		$query = [
			'from' => $request->getVal( 'from', '' ),
			'until' => $request->getVal( 'until', '' ),
			'value' => $request->getVal( 'value', '' )
		];

		$navigationLinks = Html::rawElement(
			'div',
			[
				'class' => 'smw-page-navigation'
			],
			Html::rawElement(
				'div',
				[
					'class' => 'clearfix'
				],
				Pager::pagination( $title, $limit, $offset, $resultCount,
					$query + [ '_target'	=> '#smw-result' ] )
			) . Html::rawElement(
				'div',
				[
					'style' => 'margin-top:10px;margin-bottom:10px;'
				],
				wfMessage( 'smw_conceptarticlecount', ( $resultCount < $limit ? $resultCount : $limit ) )->parse()
			)
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'concept' );

		$htmlTabs->isRTL(
			$isRTL
		);

		if ( $title->exists() ) {

			$listBuilder = new ListBuilder(
				$store
			);

			$listBuilder->isRTL(
				$isRTL
			);

			$html = $navigationLinks . $listBuilder->getColumnList( $diWikiPages );
		} else {
			$html = '';
		}

		$htmlTabs->tab(
			'smw-concept-list',
			$this->msg( 'smw-concept-tab-list' ) . $this->getCachedCount( $store ),
			[
				'hide' => $html === ''
			]
		);

		$htmlTabs->content( 'smw-concept-list', $html );

		// Improperty values
		$html = smwfEncodeMessages( $errors );

		$htmlTabs->tab( 'smw-concept-errors', $this->msg( 'smw-concept-tab-errors' ), [ 'hide' => $html === '' ] );
		$htmlTabs->content( 'smw-concept-errors', $html );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-concept clearfix' ]
		);

		return Html::element(
			'div',
			[
				'id' => 'smwfootbr'
			]
		) . Html::element(
			'a',
			[
				'name' => 'smw-result'
			],
			null
		) . Html::rawElement(
			'div',
			[
				'id' => 'mw-pages'
			],
			$html
		);
	}

	private function getCachedCount( Store $store ): string {
		$concept = $store->getConceptCacheStatus(
			$this->getDataItem()
		);

		if ( !$concept instanceof DIConcept || $concept->getCacheStatus() !== 'full' ) {
			return '';
		}

		$cacheCount = $concept->getCacheCount();
		$date = $this->getContext()->getLanguage()->timeanddate( $concept->getCacheDate() );

		$countMsg = Message::get( [ 'smw-concept-indicator-cache-update', $date ] );
		$indicatorClass = ( $cacheCount < 25000 ? ( $cacheCount > 5000 ? ' moderate' : '' ) : ' high' );

		return Html::rawElement(
			'div',
			[
				'title' => $countMsg,
				'class' => 'usage-count' . $indicatorClass
			],
			$cacheCount
		);
	}

	private function msg( $params, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ): string {
		return Message::get( $params, $type, $lang );
	}

}
