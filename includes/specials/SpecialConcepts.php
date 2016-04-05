<?php

namespace SMW;

use Html;
use SMW\Query\Language\NamespaceDescription;
use SMWPageLister;

/**
 * Special page that lists available concepts
 *
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
		$description = new NamespaceDescription( SMW_NS_CONCEPT );
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

		// Deprecated: Use of SpecialPage::getTitle was deprecated in MediaWiki 1.23
		$title = method_exists( $this, 'getPageTitle') ? $this->getPageTitle() : $this->getTitle();

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
				$pageLister->getNavigationLinks( $title ) .
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

		$this->getOutput()->setPageTitle( $this->msg( 'concepts' )->text() );

		$from  = $this->getRequest()->getVal( 'from', '' );
		$until = $this->getRequest()->getVal( 'until', '' );
		$limit = $this->getRequest()->getVal( 'limit', 50 );

		$diWikiPages = $this->getResults( $limit, $from, $until );
		$diWikiPages = $until !== '' ? array_reverse( $diWikiPages ) : $diWikiPages;

		$this->getOutput()->addHTML( $this->getHtml( $diWikiPages, $limit, $from, $until ) );
	}

	protected function getGroupName() {
		return 'pages';
	}
}
