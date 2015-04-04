<?php

namespace SMW;

use SMW\Query\Language\ConceptDescription;

use Html;
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

		if ( $this->limit > 0 ) { // limit==0: configuration setting to disable this completely
			$store = StoreFactory::getStore();
			$concept = $store->getConceptCacheStatus( $this->getDataItem() );
			$description = new ConceptDescription( $this->getDataItem() );
			$query = SMWPageLister::getQuery( $description, $this->limit, $this->from, $this->until );
			$queryResult = $store->getQueryResult( $query );

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
		$navigation = $pageLister->getNavigationLinks( $this->mTitle );

		$titleText = htmlspecialchars( $this->mTitle->getText() );
		$resultNumber = min( $this->limit, count( $diWikiPages ) );

		// Concept cache information
		if ( $concept instanceof DIConcept && $concept->getCacheStatus() === 'full' ){
			$cacheInformation = Html::element(
				'span',
				array( 'class' => 'smw-concept-cache-information' ),
				' ' . $this->getContext()->msg(
						'smw-concept-cache-text',
						$this->getContext()->getLanguage()->formatNum( $concept->getCacheCount() ),
						$this->getContext()->getLanguage()->date( $concept->getCacheDate() ),
						$this->getContext()->getLanguage()->time( $concept->getCacheDate() )
					)->text()
				);
		} else {
			$cacheInformation = '';
		}


		return Html::element( 'br', array( 'id' => 'smwfootbr' ) ) .
			Html::element( 'a', array( 'name' => 'SMWResults' ), null ) .
			Html::rawElement( 'div', array( 'id' => 'mw-pages'),
				Html::rawElement( 'h2', array(), $this->getContext()->msg( 'smw_concept_header', $titleText )->text() ) .
				Html::element( 'span', array(), $this->getContext()->msg( 'smw_conceptarticlecount', $resultNumber )->parse() ) .
				smwfEncodeMessages( $errors ) . ' '. $navigation .
				$cacheInformation .
				$pageLister->formatList()
			);
	}
}
