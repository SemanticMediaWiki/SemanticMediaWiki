<?php

namespace SMW\MediaWiki\Api;

use SMW\ApplicationFactory;
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
	private $propertyList = [];

	/**
	 * @var array
	 */
	private $namespaces = [];

	/**
	 * @var array
	 */
	private $meta = [];

	/**
	 * @var integer
	 */
	private $limit = 50;

	/**
	 * @var array
	 */
	private $continueOffset = 1;

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @var boolean
	 */
	private $listOnly = false;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $limit
	 */
	public function setLimit( $limit ) {
		$this->limit = (int)$limit;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $listOnly
	 */
	public function setListOnly( $listOnly ) {
		$this->listOnly = (bool)$listOnly;
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
	public function findPropertyListBy( $property = '' ) {

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->limit = $this->limit;

		$isFromCache = false;

		//
		$this->matchPropertiesToPreferredLabelBy( $property );

		// Increase by one to look ahead
		$requestOptions->limit++;

		$requestOptions = $this->doModifyRequestOptionsWith(
			$property,
			$requestOptions
		);

		$propertyListLookup = $this->store->getPropertiesSpecial( $requestOptions );

		// Restore original limit
		$requestOptions->limit--;

		foreach ( $propertyListLookup->fetchList() as $value ) {

			if ( $this->continueOffset > $requestOptions->limit ) {
				break;
			}

			$this->addPropertyToList( $value );
			$this->continueOffset++;
		}

		$this->continueOffset = $this->continueOffset > $requestOptions->limit ? $requestOptions->limit : 0;
		$this->namespaces = array_keys( $this->namespaces );

		$this->meta = [
			'limit' => $requestOptions->limit,
			'count' => count( $this->propertyList ),
			'isCached' => $propertyListLookup->isFromCache()
		];

		return true;
	}

	private function doModifyRequestOptionsWith( $property, $requestOptions ) {

		if ( $property === '' ) {
			return $requestOptions;
		}

		if ( $property{0} !== '_' ) {
			$property = str_replace( "_", " ", $property );
		}

		// Try to match something like _MDAT to find a label and
		// make the request a success
		try {
			$property = DIProperty::newFromUserLabel( $property )->getLabel();
		} catch ( \Exception $e ) {
			$property = '';
		}

		$requestOptions->addStringCondition(
			$property,
			StringCondition::STRCOND_MID
		);

		// Disjunctive condition to allow for auto searches to match foaf OR Foaf
		$requestOptions->addStringCondition(
			ucfirst( $property ),
			StringCondition::STRCOND_MID,
			true
		);

		// Allow something like FOO to match the search string `foo`
		$requestOptions->addStringCondition(
			strtoupper( $property ),
			StringCondition::STRCOND_MID,
			true
		);

		return $requestOptions;
	}

	private function addPropertyToList( array $value ) {

		if ( $value === [] || !$value[0] instanceof DIProperty ) {
			return;
		}

		$property = $value[0];
		$key = $property->getKey();

		if ( strpos( $key, ':' ) !== false ) {
			$this->namespaces[substr( $key, 0, strpos( $key, ':' ) )] = true;
		}

		$this->propertyList[$key] = [
			'label' => $property->getLabel(),
			'key'   => $property->getKey()
		];

		if ( $this->listOnly ) {
			return;
		}

		$this->propertyList[$key]['isUserDefined'] = $property->isUserDefined();
		$this->propertyList[$key]['usageCount'] = $value[1];
		$this->propertyList[$key]['description'] = $this->findPropertyDescriptionBy( $property );
	}

	private function findPropertyDescriptionBy( DIProperty $property ) {

		$description = $this->propertySpecificationLookup->getPropertyDescriptionByLanguageCode(
			$property,
			$this->languageCode
		);

		if ( $description === '' || $description === null ) {
			return $description;
		}

		return [
			$this->languageCode => $description
		];
	}

	private function matchPropertiesToPreferredLabelBy( $label ) {

		$propertyLabelFinder = ApplicationFactory::getInstance()->getPropertyLabelFinder();

		// Use the proximity search on a text field
		$label = '~*' . $label . '*';

		$results = $propertyLabelFinder->findPropertyListFromLabelByLanguageCode(
			$label,
			$this->languageCode
		);

		foreach ( $results as $result ) {
			$this->addPropertyToList( [ $result, 0 ] );
		}
	}

}
