<?php

namespace SMW\Page;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\MediaWiki\Collator;
use SMW\Message;
use SMWDataItem as DataItem;
use SMW\Utils\HtmlTabs;

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
		global $wgRequest;

		$context = $this->getContext();

		$context->getOutput()->addModuleStyles( 'ext.smw.page.styles' );

		if ( $this->limit > 0 ) { // limit==0: configuration setting to disable this completely
			$descriptionFactory = ApplicationFactory::getInstance()->getQueryFactory()->newDescriptionFactory();

			$description = $descriptionFactory->newConceptDescription( $this->getDataItem() );
			$query = \SMWPageLister::getQuery( $description, $this->limit, $this->from, $this->until );

			$query->setLimit( $wgRequest->getVal( 'limit', $this->getOption( 'pagingLimit' ) ) );
			$query->setOffset( $wgRequest->getVal( 'offset', '0' ) );
			$query->setContextPage( $this->getDataItem() );
			$query->setOption( $query::NO_DEPENDENCY_TRACE, true );

			$queryResult = ApplicationFactory::getInstance()->getStore()->getQueryResult( $query );

			$diWikiPages = $queryResult->getResults();
			if ( $this->until !== '' ) {
				$diWikiPages = array_reverse( $diWikiPages );
			}

			$errors = $queryResult->getErrors();
		} else {
			$diWikiPages = [];
			$errors = [];
		}

		$pageLister = new \SMWPageLister( $diWikiPages, null, $this->limit, $this->from, $this->until );
		$this->mTitle->setFragment( '#SMWResults' ); // Make navigation point to the result list.

		$titleText = htmlspecialchars( $this->mTitle->getText() );
		$resultCount = count( $diWikiPages );

		$request = $this->getContext()->getRequest();

		$limit = $request->getVal( 'limit', $this->getOption( 'pagingLimit' ) );
		$offset = $request->getVal( 'offset', '0' );

		$query = [
			'from' => $request->getVal( 'from', '' ),
			'until' => $request->getVal( 'until', '' ),
			'value' => $request->getVal( 'value', '' )
		];

		$countMessage = Html::rawElement(
			'div',
			[ 'style' => 'margin-top:10px;' ],
			wfMessage( 'smw_conceptarticlecount', ( $resultCount < $limit ? $resultCount : $limit ) )->parse()
		);

		$navigationLinks =  '<div class="smw-page-navigation"><div class="clearfix">' . ListPager::pagination( $this->mTitle, $limit, $offset, $resultCount, $query ) . '</div>' . $countMessage . '</div>';

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'concept' );

		if ( $this->mTitle->exists() ) {
			$html = $navigationLinks . $this->getFormattedColumns( $diWikiPages );
		} else {
			$html = '';
		}

		$htmlTabs->tab( 'smw-concept-list', $this->msg( 'smw-concept-tab-list' ), [ 'hide' => $html === '' ] );
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
			[ 'id' => 'smwfootbr' ]
		) . Html::element(
			'a',
			[ 'name' => 'SMWResults' ],
			null
		) . Html::rawElement(
			'div',
			[ 'id' => 'mw-pages'],
			$html
		);
	}

	/**
	 * @see Page::getTopIndicators
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	protected function getTopIndicators() {

		if ( ( $cacheInformation = $this->getCacheInformation()  ) === [] ) {
			return '';
		}

		$cacheCount = $cacheInformation['count'];
		$date = $this->getContext()->getLanguage()->timeanddate( $cacheInformation['date'] );

		$countMsg = Message::get( [ 'smw-concept-indicator-cache-update', $date ] );
		$indicatorClass = ( $cacheCount < 25000 ? ( $cacheCount > 5000 ? ' moderate' : '' ) : ' high' );

		$usageCountHtml = Html::rawElement(
			'div',
			[
				'title' => $countMsg,
				'class' => 'smw-page-indicator usage-count' . $indicatorClass
			],
			$cacheCount
		);

		return [
			'smw-concept-count' => $usageCountHtml
		];
	}

	private function getCacheInformation() {

		$concept = ApplicationFactory::getInstance()->getStore()->getConceptCacheStatus( $this->getDataItem() );
		$cacheInformation = wfMessage( 'smw-concept-no-cache' )->text();

		if ( !$concept instanceof DIConcept || $concept->getCacheStatus() !== 'full' ) {
			return [];
		}

		return [
			'count' => $concept->getCacheCount(),
			'date'  => $concept->getCacheDate()
		];
	}

	private function getFormattedColumns( array $diWikiPages ) {

		if ( $diWikiPages === [] ) {
			return '';
		}

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();
		$htmlColumnListRenderer = $mwCollaboratorFactory->newHtmlColumnListRenderer();

		foreach ( $diWikiPages as $value ) {
			$dv = DataValueFactory::getInstance()->newDataValueByItem( $value );
			$contentsByIndex[$this->getSortedFirstLetterFrom( $value )][] = $dv->getLongHTMLText( smwfGetLinker() );
		}

		$htmlColumnListRenderer->setColumnRTLDirectionalityState(
			$this->getContext()->getLanguage()->isRTL()
		);

		$htmlColumnListRenderer->setColumnClass( 'smw-column-responsive' );
		$htmlColumnListRenderer->setNumberOfColumns( 1 );
		$htmlColumnListRenderer->addContentsByIndex( $contentsByIndex );

		return $htmlColumnListRenderer->getHtml();
	}

	private function getSortedFirstLetterFrom( DataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() == DataItem::TYPE_WIKIPAGE ) {
			$sortKey = ApplicationFactory::getInstance()->getStore()->getWikiPageSortKey( $dataItem );

		}

		return Collator::singleton()->getFirstLetter( $sortKey );
	}

	private function msg( $params, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $params, $type, $lang );
	}

}
