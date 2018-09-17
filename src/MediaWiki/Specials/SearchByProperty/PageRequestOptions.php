<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use SMW\DataValueFactory;
use SMW\DataValues\TelephoneUriValue;
use SMW\Encoder;
use SMWNumberValue as NumberValue;

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
	 * @var Encoder
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
		$this->urlEncoder = new Encoder();
	}

	/**
	 * @since 2.1
	 */
	public function initialize() {

		$params = explode( '/', $this->queryString );
		reset( $params );
		$escaped = false;

		// Remove empty elements
		$params = array_filter( $params, 'strlen' );

		$property = isset( $this->requestOptions['property'] ) ? $this->requestOptions['property'] : current( $params );
		$value = isset( $this->requestOptions['value'] ) ? $this->requestOptions['value'] : next( $params );

		// Auto-generated link is marked with a leading :
		if ( $property !== '' && $property{0} === ':' ) {
			$escaped = true;
			$property = $this->urlEncoder->unescape( ltrim( $property, ':' ) );
		}

		$this->property = DataValueFactory::getInstance()->newPropertyValueByLabel(
			str_replace( [ '_' ], [ ' ' ], $property )
		);

		if ( !$this->property->isValid() ) {
			$this->propertyString = $property;
			$this->value = null;
			$this->valueString = $value;
		} else {
			$this->propertyString = $this->property->getDataItem()->getLabel();
			$this->valueString = $this->getValue( (string)$value, $escaped );
		}

		$this->setLimit();
		$this->setOffset();
		$this->setNearbySearch();
	}

	private function getValue( $value, $escaped ) {

		$this->value = DataValueFactory::getInstance()->newDataValueByProperty(
			$this->property->getDataItem()
		);

		$value = $this->unescape( $value, $escaped );
		$this->value->setUserValue( $value );

		return $this->value->isValid() ? $this->value->getWikiValue() : $value;
	}

	private function unescape( $value, $escaped ) {

		if ( $this->value instanceof NumberValue ) {
			$value = $escaped ? str_replace( [ '-20', '-2D' ], [ ' ', '-' ], $value ) : $value;
			// Do not try to decode things like 1.2e-13
			// Signals that we don't want any precision limitation
			$this->value->setOption( NumberValue::NO_DISP_PRECISION_LIMIT, true );
		} elseif ( $this->value instanceof TelephoneUriValue ) {
			$value = $escaped ? str_replace( [ '-20', '-2D' ], [ ' ', '-' ], $value ) : $value;
			// No encoding to avoid turning +1-201-555-0123
			// into +1 1U523 or further obfuscate %2B1-2D201-2D555-2D0123 ...
		} else {
			$value = $escaped ? $this->urlEncoder->unescape( $value ) : $value;
		}

		return $value;
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
