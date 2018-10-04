<?php

namespace SMW\Page\ListBuilder;

use Html;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWPageLister as PageLister;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var integer
	 */
	private $listLimit = 0;

	/**
	 * @var string
	 */
	private $listHeader = '';

	/**
	 * @var boolean
	 */
	private $isUserDefined = false;

	/**
	 * @var boolean
	 */
	private $checkProperty = true;

	/**
	 * @var integer
	 */
	private $itemCount = 0;

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
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isUserDefined
	 */
	public function isUserDefined( $isUserDefined ) {
		$this->isUserDefined = $isUserDefined;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $listLimit
	 */
	public function setListLimit( $listLimit ) {
		$this->listLimit = $listLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $listHeader
	 */
	public function setListHeader( $listHeader ) {
		$this->listHeader = $listHeader;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $checkProperty
	 */
	public function checkProperty( $checkProperty ) {
		$this->checkProperty = $checkProperty;
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getItemCount() {
		return $this->itemCount;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 * @param DataItem $dataItem
	 * @param RequestOptions $requestOptions
	 *
	 * @return string
	 */
	public function createHtml( DIProperty $property, DataItem $dataItem, RequestOptions $requestOptions ) {

		$subjectList =  $this->store->getPropertySubjects(
			$property,
			$dataItem,
			$requestOptions
		);

		// May return an iterator
		if ( $subjectList instanceof \Iterator ) {
			$subjectList = iterator_to_array( $subjectList );
		}

		$more = false;

		// Pop the +1 look ahead from the list
		if ( is_array( $subjectList ) && count( $subjectList ) > $this->listLimit ) {
			array_pop( $subjectList );
			$more = true;
		}

		$result = '';
		$this->itemCount = is_array( $subjectList ) ? count( $subjectList ) : 0;

		$callback = null;
		$message = wfMessage( 'smw-propertylist-count', $this->itemCount )->text();

		if ( $more ) {
			$callback = function() use ( $property, $dataItem ) {
				return \Html::element(
					'a',
					[
						'href' => \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( [
							'property' => $property->getLabel(),
							'value' => $dataItem->getDBKey()
						] )
					],
					wfMessage( 'smw_browse_more' )->text()
				);
			};

			$message = wfMessage( 'smw-propertylist-count-more-available', $this->itemCount )->text();
		}

		if ( $this->itemCount > 0 ) {
			$titleText = htmlspecialchars( str_replace( '_', ' ', $dataItem->getDBKey() ) );
			$result .= "<div id=\"{$this->listHeader}\">" . "\n<p>";

			$result .= $message . "</p>";
			$property = $this->checkProperty ? $property : null;

			if ( $this->itemCount < 6 ) {
				$result .= PageLister::getShortList( 0, $this->itemCount, $subjectList, $property, $callback );
			} else {
				$result .= PageLister::getColumnList( 0, $this->itemCount, $subjectList, $property, $callback );
			}

			$result .= "\n</div>";
		}

		return $result;
	}

}
