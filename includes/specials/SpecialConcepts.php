<?php

namespace SMW;

use Html;
use SMW\Page\ListPager;
use SMW\SQLStore\SQLStore;
use SMWPageLister;
use SMW\Utils\HtmlTabs;

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
		$key = $resultNumber == 0 ? 'smw-special-concept-empty' : 'smw-special-concept-count';

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'concept' );

		$html = Html::rawElement(
				'div',
				array( 'id' => 'mw-pages'),
			Html::rawElement(
				'div',
				[ 'class' => 'smw-page-navigation' ],
				ListPager::pagination( $this->getPageTitle(), $limit, $offset, count( $diWikiPages ) )
			) . Html::element(
				'div',
				array( 'class' => $key, 'style' => 'margin-top:10px;' ),
				$this->msg( $key, $resultNumber )->parse()
			) . $pageLister->getColumnList( $offset, $limit, $diWikiPages, null )
		);

		$htmlTabs->tab( 'smw-concept-list', $this->msg( 'smw-concept-tab-list' ) );
		$htmlTabs->content( 'smw-concept-list', $html );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-concept clearfix' ]
		);

		return Html::rawElement(
			'p',
			array( 'class' => 'smw-special-concept-docu plainlinks' ),
			$this->msg( 'smw-special-concept-docu' )->parse()
		) . $html;
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
		$out->addModuleStyles( 'ext.smw.page.styles' );

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
