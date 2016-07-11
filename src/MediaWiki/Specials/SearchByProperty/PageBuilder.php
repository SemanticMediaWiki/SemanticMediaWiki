<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use Html;
use SMW\ApplicationFactory;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\MessageBuilder;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMWDataValue as DataValue;
use SMWInfolink as Infolink;
use SMWStringValue as StringValue;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author Denny Vrandecic
 * @author Daniel Herzig
 * @author Markus Kroetzsch
 * @author mwjames
 */
class PageBuilder {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var PageRequestOptions
	 */
	private $pageRequestOptions;

	/**
	 * @var QueryResultLookup
	 */
	private $queryResultLookup;

	/**
	 * @var MessageBuilder
	 */
	private $messageBuilder;

	/**
	 * @var Linker
	 */
	private $linker;

	/**
	 * @since 2.1
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param PageRequestOptions $pageRequestOptions
	 * @param QueryResultLookup $queryResultLookup
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, PageRequestOptions $pageRequestOptions, QueryResultLookup $queryResultLookup ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->pageRequestOptions = $pageRequestOptions;
		$this->queryResultLookup = $queryResultLookup;
		$this->linker = smwfGetLinker();
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getHtml() {

		$this->pageRequestOptions->initialize();
		$this->messageBuilder = $this->htmlFormRenderer->getMessageBuilder();

		list( $resultMessage, $resultList, $resultCount ) = $this->getResultHtml();

		if ( ( $resultList === '' || $resultList === null ) &&
			$this->pageRequestOptions->property->getDataItem() instanceof DIProperty &&
			$this->pageRequestOptions->valueString === '' ) {
			list( $resultMessage, $resultList, $resultCount ) = $this->tryToFindAtLeastOnePropertyTableReferenceFor(
				$this->pageRequestOptions->property->getDataItem()
			);
		}

		if ( $resultList === '' || $resultList === null ) {
			$resultList = $this->messageBuilder->getMessage( 'smw_result_noresults' )->text();
		}

		$pageDescription = Html::rawElement(
			'p',
			array( 'class' => 'smw-sp-searchbyproperty-description' ),
			$this->messageBuilder->getMessage( 'smw-sp-searchbyproperty-description' )->parse()
		);

		$resultListHeader = Html::element(
			'h2',
			array(),
			$this->messageBuilder->getMessage( 'smw-sp-searchbyproperty-resultlist-header' )->text()
		);

		return $pageDescription . $this->getHtmlForm( $resultMessage, $resultCount ) . $resultListHeader . $resultList;
	}

	private function getHtmlForm( $resultMessage, $resultCount ) {

		// Precaution to avoid any inline breakage caused by a div element
		// within a paragraph (e.g Highlighter content)
		$resultMessage = str_replace( 'div', 'span', $resultMessage );

		$html = $this->htmlFormRenderer
			->setName( 'searchbyproperty' )
			->withFieldset()
			->addParagraph( $resultMessage )
			->addPaging(
				$this->pageRequestOptions->limit,
				$this->pageRequestOptions->offset,
				$resultCount )
			->addHorizontalRule()
			->addInputField(
				$this->messageBuilder->getMessage( 'smw_sbv_property' )->text(),
				'property',
				$this->pageRequestOptions->propertyString,
				'smw-property-input' )
			->addNonBreakingSpace()
			->addInputField(
				$this->messageBuilder->getMessage( 'smw_sbv_value' )->text(),
				'value',
				$this->pageRequestOptions->valueString,
				'smw-value-input' )
			->addNonBreakingSpace()
			->addSubmitButton( $this->messageBuilder->getMessage( 'smw_sbv_submit' )->text() )
			->getForm();

		return $html;
	}

	private function getResultHtml() {

		$resultList = '';

		if ( $this->pageRequestOptions->propertyString === '' || !$this->pageRequestOptions->propertyString ) {
			return array( $this->messageBuilder->getMessage( 'smw_sbv_docu' )->text(), '', 0 );
		}

		// #1728
		if ( !$this->pageRequestOptions->property->isValid() ) {
			return array( implode( ',', $this->pageRequestOptions->property->getErrors() ), '', 0 );
		}

		if ( $this->pageRequestOptions->valueString !== '' && !$this->pageRequestOptions->value->isValid() ) {
			return array( implode( ',', $this->pageRequestOptions->value->getErrors() ), '', 0 );
		}

		$exactResults = $this->queryResultLookup->doQuery( $this->pageRequestOptions );
		$exactCount = count( $exactResults );

		if ( $this->canQueryNearbyResults( $exactCount ) ) {
			return $this->getNearbyResults( $exactResults, $exactCount );
		}

		if ( $this->pageRequestOptions->valueString === '' ) {
			$resultMessageKey = 'smw-sp-searchbyproperty-nonvaluequery';
		} else {
			$resultMessageKey = 'smw-sp-searchbyproperty-valuequery';
		}

		$resultMessage = $this->messageBuilder->getMessage(
			$resultMessageKey,
			$this->pageRequestOptions->property->getShortHTMLText( $this->linker ),
			$this->pageRequestOptions->value->getShortHTMLText( $this->linker ) )->text();

		if ( $exactCount > 0 ) {
			$resultList = $this->makeResultList( $exactResults, $this->pageRequestOptions->limit, true );
		}

		return array( str_replace( '_', ' ', $resultMessage ), $resultList, $exactCount );
	}

	private function getNearbyResults( $exactResults, $exactCount ) {

		$resultList = '';

		$greaterResults = $this->queryResultLookup->doQueryForNearbyResults(
			$this->pageRequestOptions,
			$exactCount,
			true
		);

		$smallerResults = $this->queryResultLookup->doQueryForNearbyResults(
			$this->pageRequestOptions,
			$exactCount,
			false
		);

		// Calculate how many greater and smaller results should be displayed
		$greaterCount = count( $greaterResults );
		$smallerCount = count( $smallerResults );

		if ( ( $greaterCount + $smallerCount + $exactCount ) > $this->pageRequestOptions->limit ) {
			$lhalf = round( ( $this->pageRequestOptions->limit - $exactCount ) / 2 );

			if ( $lhalf < $greaterCount ) {
				if ( $lhalf < $smallerCount ) {
					$smallerCount = $lhalf;
					$greaterCount = $lhalf;
				} else {
					$greaterCount = $this->pageRequestOptions->limit - ( $exactCount + $smallerCount );
				}
			} else {
				$smallerCount = $this->pageRequestOptions->limit - ( $exactCount + $greaterCount );
			}
		}

		if ( ( $greaterCount + $smallerCount + $exactCount ) == 0 ) {
			return array( '', $resultList, 0 );
		}

		$resultMessage = $this->messageBuilder->getMessage(
			'smw_sbv_displayresultfuzzy',
			$this->pageRequestOptions->property->getShortHTMLText( $this->linker ),
			$this->pageRequestOptions->value->getShortHTMLText( $this->linker ) )->text();

		$resultList .= $this->makeResultList( $smallerResults, $smallerCount, false );

		if ( $exactCount == 0 ) {
			$resultList .= "&#160;<em><strong><small>" . $this->messageBuilder->getMessage( 'parentheses' )
				->rawParams( $this->pageRequestOptions->value->getLongHTMLText() )
				->escaped() . "</small></strong></em>";
		} else {
			$resultList .= $this->makeResultList( $exactResults, $exactCount, true, true );
		}

		$resultList .= $this->makeResultList( $greaterResults, $greaterCount, true );

		return array( $resultMessage, $resultList, $greaterCount + $exactCount );
	}

	/**
	 * Creates the HTML for a bullet list with all the results of the set
	 * query. Values can be highlighted to show exact matches among nearby
	 * ones.
	 *
	 * @param array $results (array of (array of one or two SMWDataValues))
	 * @param integer $number How many results should be displayed? -1 for all
	 * @param boolean $first If less results should be displayed than
	 * 	given, should they show the first $number results, or the last
	 * 	$number results?
	 * @param boolean $highlight Should the results be highlighted?
	 *
	 * @return string  HTML with the bullet list, including header
	 */
	private function makeResultList( $results, $number, $first, $highlight = false ) {

		if ( $number > 0 ) {
			$results = $first ?
				array_slice( $results, 0, $number ) :
				array_slice( $results, $number );
		}

		$html = '';

		foreach ( $results as $result ) {

			$result[0]->setOutputFormat( 'LOCL' );
			$listitem = $result[0]->getLongHTMLText( $this->linker );

			if ( $this->canShowSearchByPropertyLink( $result[0] ) ) {

				$value = $result[0] instanceof StringValue ? $result[0]->getWikiValueForLengthOf( 72 ) : $result[0]->getWikiValue();

				$listitem .= '&#160;&#160;' . Infolink::newPropertySearchLink(
					'+',
					$this->pageRequestOptions->propertyString,
					$value
				)->getHTML( $this->linker );
			} elseif ( $result[0]->getTypeID() === '_wpg' ) {

				// Add browsing link for wikipage results
				// Note: non-wikipage results are possible using inverse properties
				$listitem .= '&#160;&#160;' . Infolink::newBrowsingLink(
					'+',
					$result[0]->getLongWikiText()
				)->getHTML( $this->linker );
			}

			// Show value if not equal to the value that was searched
			// or if the current results are to be highlighted:
			if ( array_key_exists( 1, $result ) &&
				( $result[1] instanceof DataValue ) &&
				( !$result[1]->getDataItem() instanceof \SMWDIError ) &&
				( !$this->pageRequestOptions->value->getDataItem()->equals( $result[1]->getDataItem() )
					|| $highlight ) ) {

				$result[1]->setOutputFormat( 'LOCL' );

				$listitem .= "&#160;<em><small>" . $this->messageBuilder->getMessage( 'parentheses' )
					->rawParams( $result[1]->getLongHTMLText( $this->linker ) )
					->escaped() . "</small></em>";
			}

			// Highlight values
			if ( $highlight ) {
				$listitem = "<strong>$listitem</strong>";
			}

			$html .= "<li>$listitem</li>";
		}

		return "<ul>$html</ul>";
	}

	private function canQueryNearbyResults( $exactCount ) {
		return $exactCount < ( $this->pageRequestOptions->limit / 3 ) && $this->pageRequestOptions->nearbySearch && $this->pageRequestOptions->valueString !== '';
	}

	private function canShowSearchByPropertyLink ( DataValue $dataValue ) {
		$dataTypeClass = DataTypeRegistry::getInstance()->getDataTypeClassById( $dataValue->getTypeID() );
		return $this->pageRequestOptions->value instanceof $dataTypeClass && $this->pageRequestOptions->valueString === '';
	}

	private function tryToFindAtLeastOnePropertyTableReferenceFor( DIProperty $property ) {

		$resultList = '';
		$resultMessage = '';
		$resultCount = 0;
		$extra = '';

		$dataItem = ApplicationFactory::getInstance()->getStore()->getPropertyTableIdReferenceFinder()->tryToFindAtLeastOneReferenceForProperty(
			$property
		);

		if ( !$dataItem instanceof DIWikiPage ) {
			$resultMessage = 'No reference found.';
			return array( $resultMessage, $resultList, $resultCount );
		}

		// In case the item has already been marked as deleted but is yet pending
		// for removal
		if ( $dataItem->getInterWiki() === ':smw-delete' ) {
			$resultMessage = 'Item reference "' . $dataItem->getSubobjectName() . '" has already been marked for removal.';
			$dataItem = new DIWikiPage( $dataItem->getDBKey(), $dataItem->getNamespace() );
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem
		);

		$dataValue->setOutputFormat( 'LOCL' );

		if ( $dataValue->isValid() ) {
			//$resultMessage = 'Item reference for a zero-marked property.';
			$resultList = $dataValue->getShortHtmlText( $this->linker ) . ' ' . $extra;
			$resultCount++;

			$resultList .= '&#160;&#160;' . Infolink::newBrowsingLink(
				'+',
				$dataValue->getLongWikiText()
			)->getHTML( $this->linker );
		}

		return array( $resultMessage, $resultList, $resultCount );
	}

}
