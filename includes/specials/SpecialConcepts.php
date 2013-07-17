<?php

namespace SMW;

use SMWThingDescription;
use SMWSomeProperty;
use SMWPageLister;

use Html;

/**
 * Special page that lists available concepts
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
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Special page that lists available concepts
 *
 * @ingroup SpecialPage
 */
class SpecialConcepts extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'Concepts' );
	}

	/**
	 * Returns concept pages
	 *
	 * @since 1.9
	 *
	 * @param integer $limit
	 * @param integer $from
	 * @param integer $until
	 *
	 * @return DIWikiPage[]
	 */
	public function getResults( $limit, $from, $until ) {
		$description = new SMWSomeProperty( new DIProperty( '_CONC' ), new SMWThingDescription() );
		$query = SMWPageLister::getQuery( $description, $limit, $from, $until );
		return $this->getStore()->getQueryResult( $query )->getResults();
	}

	/**
	 * Returns html
	 *
	 * @since 1.9
	 *
	 * @param DIWikiPage[] $diWikiPages
	 * @param integer $limit
	 * @param integer $from
	 * @param integer $until
	 *
	 * @return string
	 */
	public function getHtml( $diWikiPages, $limit, $from, $until ) {
		$resultNumber = min( $limit, count( $diWikiPages ) );
		$pageLister   = new SMWPageLister( $diWikiPages, null, $limit, $from, $until );
		$key = $resultNumber == 0 ? 'smw-sp-concept-empty' : 'smw-sp-concept-count';

		return Html::rawElement(
			'span',
			array( 'class' => 'smw-sp-concept-docu' ),
			$this->msg( 'smw-sp-concept-docu' )->parse()
			) .
			Html::rawElement(
				'div',
				array( 'id' => 'mw-pages'),
				Html::element(
					'h2',
					array(),
					$this->msg( 'smw-sp-concept-header' )->text()
				) .
				Html::element(
					'span',
					array( 'class' => $key ),
					$this->msg( $key, $resultNumber )->parse()
				) .	' ' .
				$pageLister->getNavigationLinks( $this->getTitle() ) .
				$pageLister->formatList()
			);
	}

	/**
	 * Executes and outputs results for available concepts
	 *
	 * @since 1.9
	 *
	 * @param array $param
	 */
	public function execute( $param ) {
		Profiler::In( __METHOD__ );

		$this->getOutput()->setPageTitle( $this->msg( 'concepts' )->text() );

		$from  = $this->getRequest()->getVal( 'from' , '' );
		$until = $this->getRequest()->getVal( 'until', '' );
		$limit = $this->getRequest()->getVal( 'limit', 50 );

		$diWikiPages = $this->getResults( $limit, $from, $until );
		$diWikiPages = $until !== '' ? array_reverse( $diWikiPages ) : $diWikiPages;

		$this->getOutput()->addHTML( $this->getHtml( $diWikiPages, $limit, $from , $until ) );

		Profiler::Out( __METHOD__ );
	}
}
