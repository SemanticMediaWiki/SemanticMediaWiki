<?php

namespace SMW\MediaWiki\Page\ListBuilder;

use Html;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMWDataItem as DataItem;
use SMW\Message;
use SMW\MediaWiki\Page\ListBuilder as ColsListBuilder;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ItemListBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var boolean
	 */
	private $isRTL = false;

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
	 * @since 3.1
	 *
	 * @param boolean $isRTL
	 */
	public function isRTL( $isRTL ) {
		$this->isRTL = (bool)$isRTL;
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
	public function buildHTML( DIProperty $property, DataItem $dataItem, RequestOptions $requestOptions ) {

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

		$colsListBuilder = new ColsListBuilder(
			$this->store
		);

		$colsListBuilder->isRTL(
			$this->isRTL
		);

		if ( $this->checkProperty ) {
			$colsListBuilder->setProperty( $property );
		}

		if ( $this->itemCount == 0 ) {
			return '';
		}

		$callback = null;
		$message = $this->msg( [ 'smw-propertylist-count', $this->itemCount ] );

		if ( $more ) {
			$message = $this->msg( ['smw-propertylist-count-more-available', $this->itemCount ] );
			$colsListBuilder->setLastItemFormatter( $this->getLastItemFormatter( $property, $dataItem ) );
		}

		return "\n<p>" . $message . $colsListBuilder->getColumnList( $subjectList, 5 );
	}

	private function getLastItemFormatter( $property, $dataItem ) {
		return function() use ( $property, $dataItem ) {
			return \Html::element(
				'a',
				[
					'href' => \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL(
						[
							'property' => $property->getLabel(),
							'value' => $dataItem->getDBKey()
						]
					)
				],
				$this->msg( 'smw_browse_more' )
			);
		};
	}

	private function msg( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, $this->languageCode );
	}
}
