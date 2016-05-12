<?php

namespace SMW\MediaWiki\Api;

use SMW\DIProperty;
use SMW\PropertySpecificationLookup;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StringCondition;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyListByApiRequest {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var RequestOptions
	 */
	private $requestOptions = null;

	/**
	 * @var array
	 */
	private $propertyList = array();

	/**
	 * @var array
	 */
	private $namespaces = array();

	/**
	 * @var array
	 */
	private $meta = array();

	/**
	 * @var array
	 */
	private $continueOffset = 0;

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
		$this->requestOptions = new RequestOptions();
		$this->requestOptions->sort = true;
		$this->requestOptions->limit = 50;
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $limit
	 */
	public function setLimit( $limit ) {
		$this->requestOptions->limit = (int)$limit;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = (string)$languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getPropertyList() {
		return $this->propertyList;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getMeta() {
		return $this->meta;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getContinueOffset() {
		return $this->continueOffset;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function findPropertyListFor( $property = '' ) {

		$this->meta = array();
		$this->propertyList = array();
		$this->namespaces = array();

		$this->requestOptions->limit++; // increase by one to look ahead
		$this->continueOffset = 1;

		if ( $property !== '' ) {
			$property = $this->preprocessPropertyString( $property );

			$this->requestOptions->addStringCondition(
				$property,
				StringCondition::STRCOND_MID
			);

			// Disjunctive condition to allow for auto searches of foaf OR Foaf
			$this->requestOptions->addStringCondition(
				ucfirst( $property ),
				StringCondition::STRCOND_MID,
				true
			);
		}

		$propertyListLookup = $this->store->getPropertiesSpecial( $this->requestOptions );
		$this->requestOptions->limit--;

		foreach ( $propertyListLookup->fetchList() as $value ) {

			if ( $this->continueOffset > $this->requestOptions->limit ) {
				break;
			}

			$this->addPropertyToList( $value );
			$this->continueOffset++;
		}

		$this->continueOffset = $this->continueOffset > $this->requestOptions->limit ? $this->requestOptions->limit : 0;
		$this->namespaces = array_keys( $this->namespaces );

		$this->meta = array(
			'limit' => $this->requestOptions->limit,
			'count' => count( $this->propertyList ),
			'isCached' => $propertyListLookup->isFromCache()
		);

		return true;
	}

	private function preprocessPropertyString( $property ) {

		if ( $property{0} !== '_' ) {
			return str_replace( "_", " ", $property );
		}

		// Try to match something like _MDAT to find a label and
		// make the request a success
		try {
			$property = DIProperty::newFromUserLabel( $property )->getLabel();
		} catch ( \Exception $e ) {
			$property = '';
		}

		return $property;
	}

	private function addPropertyToList( array $value ) {

		if ( $value === array() || !$value[0] instanceof DIProperty ) {
			return;
		}

		$key = $value[0]->getKey();

		if ( strpos( $key, ':' ) !== false ) {
			$this->namespaces[substr( $key, 0, strpos( $key, ':' ) )] = true;
		}

		$this->propertyList[$key] = array(
			'label' => $value[0]->getLabel(),
			'key'   => $value[0]->getKey(),
			'isUserDefined' => $value[0]->isUserDefined(),
			'usageCount'  => $value[1],
			'description' => $this->tryToFindPropertyDescriptionFor( $value[0] )
		);
	}

	private function tryToFindPropertyDescriptionFor( DIProperty $property ) {

		if ( $this->languageCode !== '' ) {
			$this->propertySpecificationLookup->setLanguageCode( $this->languageCode );
		}

		$description = $this->propertySpecificationLookup->getPropertyDescriptionFor(
			$property
		);

		if ( $description === '' || $description === null ) {
			return $description;
		}

		return array(
			$this->propertySpecificationLookup->getLanguageCode() => $description
		);
	}

}
