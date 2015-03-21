<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use SMW\DataValueFactory;
use SMW\UrlEncoder;
use SMWPropertyValue as PropertyValue;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class PageRequestOptions {

	/**
	 * @var string
	 */
	private $queryString;

	/**
	 * @var array
	 */
	private $requestOptions;

	/**
	 * @var UrlEncoder
	 */
	private $urlEncoder;

	/**
	 * @var PropertyValue
	 */
	public $property;

	/**
	 * @var string
	 */
	public $propertyString;

	/**
	 * @var string
	 */
	public $valueString;

	/**
	 * @var DataValue
	 */
	public $value;

	/**
	 * @var integer
	 */
	public $limit = 20;

	/**
	 * @var integer
	 */
	public $offset = 0;

	/**
	 * @var boolean
	 */
	public $nearbySearch = false;

	/**
	 * @since 2.1
	 *
	 * @param string $queryString
	 * @param array $requestOptions
	 */
	public function __construct( $queryString, array $requestOptions ) {
		$this->queryString = $queryString;
		$this->requestOptions = $requestOptions;
		$this->urlEncoder = new UrlEncoder();
	}

	/**
	 * @since 2.1
	 */
	public function initialize() {

		$params = explode( '/', $this->queryString );
		reset( $params );

		// Remove empty elements
		$params = array_filter( $params, 'strlen' );

		$property = isset( $this->requestOptions['property'] ) ? $this->requestOptions['property'] : current( $params );
		$value = isset( $this->requestOptions['value'] ) ? $this->requestOptions['value'] : next( $params );

		$property = $this->urlEncoder->decode( $property );
		$value = str_replace( array( '-25' ), array( '%' ), $value );

		$this->property = PropertyValue::makeUserProperty( $property );

		if ( !$this->property->isValid() ) {
			$this->propertyString = $property;
			$this->value = null;
			$this->valueString = $value;
		} else {
			$this->propertyString = $this->property->getWikiValue();

			$this->value = DataValueFactory::getInstance()->newPropertyObjectValue(
				$this->property->getDataItem(),
				$this->urlEncoder->decode( $value )
			);

			$this->valueString = $this->value->isValid() ? $this->value->getWikiValue() : $value;
		}

		$this->setLimit();
		$this->setOffset();
		$this->setNearbySearch();
	}

	private function setLimit() {
		if ( isset( $this->requestOptions['limit'] ) ) {
			$this->limit = intval( $this->requestOptions['limit'] );
		}
	}

	private function setOffset() {
		if ( isset( $this->requestOptions['offset'] ) ) {
			$this->offset = intval( $this->requestOptions['offset'] );
		}
	}

	private function setNearbySearch() {

		if ( $this->value === null ) {
			return null;
		}

		if ( isset( $this->requestOptions['nearbySearchForType'] ) && is_array( $this->requestOptions['nearbySearchForType'] ) ) {
			$this->nearbySearch = in_array( $this->value->getTypeID(), $this->requestOptions['nearbySearchForType'] );
		}
	}

}
