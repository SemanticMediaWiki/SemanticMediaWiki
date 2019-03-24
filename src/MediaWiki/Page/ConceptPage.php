<?php

namespace SMW\MediaWiki\Page;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\MediaWiki\Collator;
use SMW\Message;
use SMWDataItem as DataItem;
use SMW\Utils\HtmlTabs;
use SMW\Utils\Pager;
use SMW\MediaWiki\Page\ListBuilder;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptPage extends Page {

	/**
	 * @var DIProperty
	 */
	private $property;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DataValue
	 */
	private $propertyValue;

	/**
	 * @see Page::initParameters()
	 *
	 * @note We use a smaller limit here; property pages might become large.
	 */
	protected function initParameters() {
		$this->limit = $this->getOption( 'pagingLimit' );
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected function getHtml() {

		$context = $this->getContext();
		$context->getOutput()->addModuleStyles( 'ext.smw.page.styles' );

		$request = $context->getRequest();
		$store = ApplicationFactory::getInstance()->getStore();

		// limit==0: configuration setting to disable this completely
		if ( $this->limit > 0 ) {
			$descriptionFactory = ApplicationFactory::getInstance()->getQueryFactory()->newDescriptionFactory();

			$description = $descriptionFactory->newConceptDescription( $this->getDataItem() );
			$query = \SMWPageLister::getQuery( $description, $this->limit, $this->from, $this->until );

			$query->setLimit( $request->getVal( 'limit', $this->getOption( 'pagingLimit' ) ) );
			$query->setOffset( $request->getVal( 'offset', '0' ) );
			$query->setContextPage( $this->getDataItem() );
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
		$this->mTitle->setFragment( '#smw-result' );
		$isRTL = $context->getLanguage()->isRTL();

		$titleText = htmlspecialchars( $this->mTitle->getText() );
		$resultCount = count( $diWikiPages );

		$limit = $request->getVal( 'limit', $this->getOption( 'pagingLimit' ) );
		$offset = $request->getVal( 'offset', '0' );

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
				Pager::pagination( $this->mTitle, $limit, $offset, $resultCount, $query + [ '_target' => '#smw-result' ] )
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

		if ( $this->mTitle->exists() ) {

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

		$htmlTabs->tab( 'smw-concept-errors', $this->msg( 'smw-concept-tab-errors' ),  [ 'hide' => $html === '' ] );
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

	private function getCachedCount( $store ) {

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

	private function msg( $params, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $params, $type, $lang );
	}

}
