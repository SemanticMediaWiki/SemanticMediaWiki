<?php

namespace SMW;

use Html;
use SMW\Utils\Pager;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\SQLStore\SQLStore;
use SMW\Utils\HtmlTabs;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Collator;

/**
 * Special page that lists available concepts
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class SpecialConcepts extends \SpecialPage {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'Concepts' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ) {

		$this->setHeaders();
		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.smw.page.styles' );

		$limit = $this->getRequest()->getVal( 'limit', 50 );
		$offset = $this->getRequest()->getVal( 'offset', 0 );

		$this->store = ApplicationFactory::getInstance()->getStore();

		$diWikiPages = $this->fetchFromTable( $limit, $offset );
		$html = $this->getHtml( $diWikiPages, $limit, $offset );

		$this->addHelpLink( wfMessage( 'smw-helplink-concepts' )->escaped(), true );

		$out->setPageTitle( $this->msg( 'concepts' )->text() );
		$out->addHTML( $html );
	}

	/**
	 * @since 1.9
	 *
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return DIWikiPage[]
	 */
	public function fetchFromTable( $limit, $offset ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$results = [];

		$fields = [
			'smw_id',
			'smw_title'
		];

		$conditions = [
			'smw_namespace' => SMW_NS_CONCEPT,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash IS NOT NULL',
			'concept_features > 0'
		];

		$options = [
			'LIMIT' => $limit + 1,
			'OFFSET' => $offset,
		];

		$res = $connection->select(
			[
				$connection->tableName( SQLStore::ID_TABLE ),
				$connection->tableName( SQLStore::CONCEPT_TABLE )
			],
			$fields,
			$conditions,
			__METHOD__,
			$options,
			[
				$connection->tableName( SQLStore::ID_TABLE ) => [ 'INNER JOIN', [ 'smw_id=s_id' ] ]
			]
		);

		foreach ( $res as $row ) {
			$results[] = new DIWikiPage( $row->smw_title, SMW_NS_CONCEPT );
		}

		return $results;
	}

	/**
	 * @since 1.9
	 *
	 * @param DIWikiPage[] $dataItems
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return string
	 */
	public function getHtml( $dataItems, $limit, $offset ) {

		if ( $this->store === null ) {
			$this->store = ApplicationFactory::getInstance()->getStore();
		}

		$count = count( $dataItems );
		$resultNumber = min( $limit, $count );

		if ( $resultNumber == 0 ) {
			$key = 'smw-special-concept-empty';
		} else {
			$key = 'smw-special-concept-count';
		}

		$listBuilder = new ListBuilder(
			$this->store,
			Collator::singleton()
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'concept' );

		$htmlTabs->isRTL(
			$this->getLanguage()->isRTL()
		);

		$html = Html::rawElement(
				'div',
				[ 'id' => 'mw-pages'],
			Html::rawElement(
				'div',
				[ 'class' => 'smw-page-navigation' ],
				Pager::pagination( $this->getPageTitle(), $limit, $offset, $count )
			) . Html::element(
				'div',
				[ 'class' => $key, 'style' => 'margin-top:10px;margin-bottom:10px;' ],
				$this->msg( $key, $resultNumber )->parse()
			) . $listBuilder->getColumnList( $dataItems )
		);

		$htmlTabs->tab( 'smw-concept-list', $this->msg( 'smw-concept-tab-list' ) );
		$htmlTabs->content( 'smw-concept-list', $html );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-concept clearfix' ]
		);

		return Html::rawElement(
			'p',
			[ 'class' => 'smw-special-concept-docu plainlinks' ],
			$this->msg( 'smw-special-concept-docu' )->parse()
		) . $html;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/properties-concepts-types';
	}

}
