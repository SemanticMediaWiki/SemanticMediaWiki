<?php

namespace SMW\Page\ListBuilder;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\MediaWiki\Collator;
use SMW\Message;
use SMW\Page\ListPager;
use SMW\Query\Language\SomeProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMW\Utils\HtmlDivTable;
use SMW\Utils\NextPager;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWInfolink as Infolink;
use SMWPageLister as PageLister;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ValueListBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var integer
	 */
	private $pagingLimit = 0;

	/**
	 * @var integer
	 */
	private $maxPropertyValues = 3;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $pagingLimit
	 */
	public function setPagingLimit( $pagingLimit ) {
		$this->pagingLimit = $pagingLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $maxPropertyValues
	 */
	public function setMaxPropertyValues( $maxPropertyValues ) {
		$this->maxPropertyValues = $maxPropertyValues;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 * @param DataItem $dataItem
	 *
	 * @return string
	 */
	public function createHtml( DIProperty $property, DataItem $dataItem, array $query = [] ) {

		$limit = isset( $query['limit'] ) ? (int)$query['limit'] : 0;
		$offset = isset( $query['offset'] ) ? (int)$query['offset'] : 0;
		$from = isset( $query['from'] ) ? $query['from'] : 0;
		$until = isset( $query['until'] ) ? $query['until'] : 0;
		$filter = isset( $query['filter'] ) ? $query['filter'] : '';

		// limit==0: configuration setting to disable this completely
		if ( $limit < 1 ) {
			return '';
		}

		$dataItems = [];
		$isValueSearch = false;

		$options = PageLister::getRequestOptions( $limit, $from, $until );
		$options->setOffset( $offset );

		if ( $filter !== '' ) {
			$dataItems = $this->filterByValue( $property, $filter, $options );
			$isValueSearch = true;
		} else {
			$dataItems = $this->store->getAllPropertySubjects( $property, $options );
		}
		
		if ( $dataItems instanceof \Traversable ) {
			$dataItems = iterator_to_array( $dataItems );
		}

		if ( !$options->ascending ) {
			$dataItems = array_reverse( $dataItems );
		}

		$result = '';

		if ( count( $dataItems ) < 1 && !$isValueSearch ) {
			return $result;
		}

		$title = $dataItem->getTitle();
		$title->setFragment( '#SMWResults' );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		// Allow the DV formatter to access a specific language code
		$dataValue->setOption(
			DataValue::OPT_USER_LANGUAGE,
			$this->languageCode
		);

		$titleText = htmlspecialchars( $dataValue->getWikiValue() );
		$resultCount = count( $dataItems );

		$topic = $isValueSearch ? 'smw-property-page-list-search-count' : 'smw-property-page-list-count';

		$navNote = Html::rawElement(
			'div',
			[
				'class' => 'smw-page-nav-note'
			],
			Message::get(
				[ $topic, ( $resultCount < $limit ? $resultCount : $limit ), $filter ],
				Message::PARSE,
				$this->languageCode
			) . Html::rawElement(
				'div',
				[],
				''
			)
		);

		$objectList = $this->createValueList(
			$property,
			$dataItem,
			$dataItems,
			$limit,
			$until
		);

		$navContainer = Html::rawElement(
			'div',
			[
				'class' => 'smw-page-nav-container'
			],
			Html::rawElement(
				'div' ,
				[
					'class' => 'smw-page-nav-left'
				],
				ListPager::pagination( $title, $limit, $offset, $resultCount, $query )
			) . Html::rawElement(
				'div',
				[
					'class' => 'smw-page-nav-right'
				],
				ListPager::filter( $title, $limit, $offset, $filter )
			)
		);

		$result .= Html::rawElement(
			'div',
			[
				'class' => 'smw-page-navigation'
			],
			$navContainer . $navNote
		) . $objectList;

		return Html::rawElement(
			'a',
			[ 'name' => 'SMWResults' ],
			''
		) . Html::rawElement(
			'div',
			[ 'id' => 'mw-pages' ],
			$result
		);
	}

	private function createValueList( DIProperty $property, DataItem $dataItem, $diWikiPages, $limit, $until ) {

		if ( $diWikiPages instanceof \Iterator ) {
			$diWikiPages = iterator_to_array( $diWikiPages );
		}

		$ac = count( $diWikiPages );
		//$contentLanguage = Localizer::getInstance()->getContentLanguage();
		$title = $dataItem->getTitle();

		if ( $ac > $limit ) {
			if ( $until !== '' ) {
				$start = 1;
			} else {
				$start = 0;
				$ac = $ac - 1;
			}
		} else {
			$start = 0;
		}

		$html = '';
		$prev_start_char = 'None';

		for ( $index = $start; $index < $ac; $index++ ) {
			$diWikiPage = $diWikiPages[$index];
			$dvWikiPage = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPage, null );

			$sortKey = $this->store->getWikiPageSortKey( $diWikiPage );
			$start_char = Collator::singleton()->getFirstLetter( $sortKey );

			// Header for index letters
			if ( $start_char != $prev_start_char ) {
				$html .= HtmlDivTable::row(
					HtmlDivTable::cell(
						'<div id="' . htmlspecialchars( $start_char ) . '">' . htmlspecialchars( $start_char ) . "</div>",
						[
							'class' => "header-title"
						]
					) . HtmlDivTable::cell(
						'<div></div>',
						[
							'class' => "header-title"
						]
					),
					[
						'class' => "header-row"
					]
				);
				$prev_start_char = $start_char;
			}

			// Property values
			$ropts = new RequestOptions();
			$ropts->limit = $this->maxPropertyValues + 1;

			// Restrict the request otherwise the entire SemanticData record
			// is fetched which can in case of a subject with a large
			// subobject/subpage pool create excessive DB queries that are not
			// used for the display
			$ropts->conditionConstraint = true;

			$values = $this->store->getPropertyValues( $diWikiPage, $property, $ropts );

			// May return an iterator
			if ( $values instanceof \Iterator ) {
				$values = iterator_to_array( $values );
			}

			$hasLocalTimeOffsetPreference = Localizer::getInstance()->hasLocalTimeOffsetPreference();

			$i = 0;
			$pvCells = '';

			foreach ( $values as $di ) {
				if ( $i != 0 ) {
					$pvCells .= ', ';
				}
				$i++;

				if ( $i < $this->maxPropertyValues + 1 ) {
					$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $di, $property );
					$outputFormat = $dataValue->getOutputFormat();

					if ( $outputFormat === false ) {
						$outputFormat = 'LOCL' . ( $hasLocalTimeOffsetPreference ? '#TO' : '' );
					}

					$dataValue->setOutputFormat( $outputFormat );

					$pvCells .= $dataValue->getShortHTMLText( smwfGetLinker() ) . $dataValue->getInfolinkText( SMW_OUTPUT_HTML, smwfGetLinker() );
				} else {
					$searchlink = Infolink::newInversePropertySearchLink( '…', $dvWikiPage->getWikiValue(), $title->getText() );
					$pvCells .= $searchlink->getHTML( smwfGetLinker() );
				}
			}

			// Property name
			$searchlink = Infolink::newBrowsingLink( '+', $dvWikiPage->getWikiValue() );
			$html .= HtmlDivTable::row(
				HtmlDivTable::cell(
					$dvWikiPage->getShortHTMLText( smwfGetLinker() ) . '&#160;' . $searchlink->getHTML( smwfGetLinker() ),
					[
						'class' => "smwpropname",
						'data-list-index' => $index
					]
				) . HtmlDivTable::cell(
					$pvCells,
					[
						'class' => "smwprops"
					]
				),
				[
					'class' => "value-row"
				]
			);
		}

		return HtmlDivTable::table(
			$html,
			[
				'class' => "smw-property-page-results",
				'style' => "width: 100%;"
			]
		);
	}

	private function filterByValue( $property, $value, $options ) {

		$queryFactory = ApplicationFactory::getInstance()->getQueryFactory();
		$queryParser = $queryFactory->newQueryParser();

		$description = $queryParser->getQueryDescription(
			$queryParser->createCondition( $property, $value )
		);

		if ( $queryParser->getErrors() !== [] ) {
			return [];
		}

		// Make sure that no subproperty is included while executing the
		// query
		if ( $description instanceof SomeProperty ) {
			$description->setHierarchyDepth( 0 );
		}

		$query = $queryFactory->newQuery( $description );
		$query->setLimit( $options->limit );
		$query->setOffset( $options->offset );

		// We are not sorting via the backend as an ORDER BY will cause a
		// SQL filesort and means for a large pool of value assignments a
		// slow query
		$res = $this->store->getQueryResult( $query );
		$results = $res->getResults();

		$sort = [];
		$collator = Collator::singleton();

		foreach ( $results as $result ) {

			$firstLetter = $collator->getFirstLetter(
				$this->store->getWikiPageSortKey( $result )
			);

			$sort[$firstLetter . '#' . $result->getHash()] = $result;
		}

		// Sort on the spot via PHP, which should be enough for the search
		// and match functionality
		ksort( $sort );

		return array_values( $sort );
	}

}
