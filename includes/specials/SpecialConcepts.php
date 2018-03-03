<?php

namespace SMW;

use Html;
use SMW\SQLStore\SQLStore;
use SMW\Page\ListPager;
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
	public function getResults( $limit, $offset, $from, $until ) {

		$connection = $this->getStore()->getConnection( 'mw.db' );
		$results = [];

		$fields = [
			'smw_id',
			'smw_title'
		];

		$conditions = [
			'smw_namespace' => SMW_NS_CONCEPT,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$options = [
			'LIMIT' => $limit + 1,
			'OFFSET' => $offset,
		];

		$res = $connection->select(
			$connection->tableName( SQLStore::ID_TABLE ),
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		foreach ( $res as $row ) {
			$results[] = new DIWikiPage( $row->smw_title, SMW_NS_CONCEPT );
		}

		return $results;
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
	public function getHtml( $diWikiPages, $limit, $offset, $from, $until ) {
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
				"<br>" . Html::rawElement( 'div', [ 'style' => 'margin-top:5px;'], ListPager::getPagingLinks( $title, $limit, $offset, count( $diWikiPages ) ) ) .
				$pageLister->getColumnList( $offset, $limit, $diWikiPages, null )
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

		$this->setHeaders();
		$out = $this->getOutput();

		$from  = $this->getRequest()->getVal( 'from', '' );
		$until = $this->getRequest()->getVal( 'until', '' );
		$limit = $this->getRequest()->getVal( 'limit', 50 );
		$offset = $this->getRequest()->getVal( 'offset', 0 );

		$diWikiPages = $this->getResults( $limit, $offset, $from, $until );
	//	$diWikiPages = $until !== '' ? array_reverse( $diWikiPages ) : $diWikiPages;
		$html = $this->getHtml( $diWikiPages, $limit, $offset, $from, $until );

		$out->setPageTitle( $this->msg( 'concepts' )->text() );
		$out->addHTML( $html );
	}

	protected function getGroupName() {
		return 'pages';
	}
}
