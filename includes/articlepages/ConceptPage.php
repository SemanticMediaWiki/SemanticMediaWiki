<?php

namespace SMW;

use Html;
use SMW\MediaWiki\ByLanguageCollationMapper;
use SMW\Query\Language\ConceptDescription;
use SMWDataItem as DataItem;
use SMWPageLister;

/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * Indicate class aliases in a way PHPStorm and Eclipse understand.
 * This is purely an IDE helper file, and is not loaded by the extension.
 *
 * @since 1.9
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @author: Markus Krötzsch
 * @author: mwjames
 */

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * Concept: pages. Very similar to CategoryPage.
 * @ingroup SMW
 */
class ConceptPage extends \SMWOrderedListPage {

	/**
	 * Initialize parameters to use a higher limit. This operation is very
	 * similar to showing members of categories.
	 */
	protected function initParameters() {
		global $smwgConceptPagingLimit;
		$this->limit = $smwgConceptPagingLimit;
		return true;
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected function getHtml() {
		global $smwgConceptPagingLimit, $wgRequest;

		if ( $this->limit > 0 ) { // limit==0: configuration setting to disable this completely
			$description = new ConceptDescription( $this->getDataItem() );
			$query = SMWPageLister::getQuery( $description, $this->limit, $this->from, $this->until );

			$query->setLimit( $wgRequest->getVal( 'limit', $smwgConceptPagingLimit ) );
			$query->setOffset( $wgRequest->getVal( 'offset', '0' ) );

			$queryResult = ApplicationFactory::getInstance()->getStore()->getQueryResult( $query );

			$diWikiPages = $queryResult->getResults();
			if ( $this->until !== '' ) {
				$diWikiPages = array_reverse( $diWikiPages );
			}

			$errors = $queryResult->getErrors();
		} else {
			$diWikiPages = array();
			$errors = array();
		}

		$pageLister = new SMWPageLister( $diWikiPages, null, $this->limit, $this->from, $this->until );
		$this->mTitle->setFragment( '#SMWResults' ); // Make navigation point to the result list.

		$titleText = htmlspecialchars( $this->mTitle->getText() );

		return Html::element( 'br', array( 'id' => 'smwfootbr' ) ) .
			Html::element( 'a', array( 'name' => 'SMWResults' ), null ) .
			Html::rawElement( 'div', array( 'id' => 'mw-pages'),
				$this->getCacheInformation() .
				Html::rawElement( 'h2', array(), $this->getContext()->msg( 'smw_concept_header', $titleText )->text() ) .
				$this->getNavigationLinks( 'smw_conceptarticlecount', $diWikiPages, $smwgConceptPagingLimit ) .
				smwfEncodeMessages( $errors ) . ' ' .
				$this->getFormattedColumns( $diWikiPages )
			);
	}

	private function getCacheInformation() {

		$concept = ApplicationFactory::getInstance()->getStore()->getConceptCacheStatus( $this->getDataItem() );
		$cacheInformation = wfMessage( 'smw-concept-no-cache' )->text();

		if ( $concept instanceof DIConcept && $concept->getCacheStatus() === 'full' ) {
			$cacheInformation = wfMessage(
				'smw-concept-cache-count',
				$this->getContext()->getLanguage()->formatNum( $concept->getCacheCount() ),
				$this->getContext()->getLanguage()->timeanddate( $concept->getCacheDate() )
			)->parse();
		}

		return Html::rawElement(
			'h2',
			array(),
			$this->getContext()->msg( 'smw-concept-cache-header' )->text()
		) . $cacheInformation;
	}

	private function getFormattedColumns( array $diWikiPages ) {

		if ( $diWikiPages === array() ) {
			return '';
		}

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();
		$htmlColumnListRenderer = $mwCollaboratorFactory->newHtmlColumnListRenderer();

		foreach ( $diWikiPages as $value ) {
			$dv = DataValueFactory::getInstance()->newDataValueByItem( $value );
			$contentsByIndex[$this->getFirstLetterForCategory( $value )][] = $dv->getLongHTMLText( smwfGetLinker() );
		}

		$htmlColumnListRenderer->setColumnRTLDirectionalityState(
			$this->getContext()->getLanguage()->isRTL()
		);

		$htmlColumnListRenderer->setColumnClass( 'smw-column-responsive' );
		$htmlColumnListRenderer->setNumberOfColumns( 1 );
		$htmlColumnListRenderer->addContentsByIndex( $contentsByIndex );

		return $htmlColumnListRenderer->getHtml();
	}

	private function getFirstLetterForCategory( DataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() == DataItem::TYPE_WIKIPAGE ) {
			$sortKey = ApplicationFactory::getInstance()->getStore()->getWikiPageSortKey( $dataItem );

		}

		return ByLanguageCollationMapper::getInstance()->findFirstLetterForCategory( $sortKey );
	}

}
