<?php

namespace SMW;

use Html;
use SMWPageLister;
use SMWConceptDescription;

/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * Indicate class aliases in a way PHPStorm and Eclipse understand.
 * This is purely an IDE helper file, and is not loaded by the extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
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
		wfProfileIn( __METHOD__ . ' (SMW)' );

		if ( $this->limit > 0 ) { // limit==0: configuration setting to disable this completely
			$store = smwfGetStore();
			$concept = $store->getConceptCacheStatus( $this->getDataItem() );
			$description = new SMWConceptDescription( $this->getDataItem() );
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
						$this->getContext()->getLanguage()->date( $concept->getCacheDate() )
					)->text()
				);
		} else {
			$cacheInformation = '';
		}

		wfProfileOut( __METHOD__ . ' (SMW)' );

		return  Html::element( 'br', array( 'id' => 'smwfootbr' ) ) .
			Html::element( 'a', array( 'name' => 'SMWResults' ) , null ) .
			Html::rawElement( 'div', array( 'id' => 'mw-pages'),
				Html::rawElement( 'h2', array(),  $this->getContext()->msg( 'smw_concept_header', $titleText )->text() ) .
				Html::element( 'span', array(), $this->getContext()->msg( 'smw_conceptarticlecount', $resultNumber )->parse() ) .
				smwfEncodeMessages( $errors ) . ' '. $navigation .
				$cacheInformation .
				$pageLister->formatList()
			);
	}
}