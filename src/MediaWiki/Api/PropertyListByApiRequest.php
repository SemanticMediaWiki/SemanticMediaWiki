<?php

namespace SMW\MediaWiki\Api;

use Exception;
use SMW\DataItems\Property;
use SMW\Property\SpecificationLookup;
use SMW\RequestOptions;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\StringCondition;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyListByApiRequest {

	/**
	 * @var RequestOptions
	 */
	private $requestOptions = null;

	private array $propertyList = [];

	private array $namespaces = [];

	private array $meta = [];

	private int $limit = 50;

	private int $continueOffset = 1;

	private string $languageCode = '';

	private bool $listOnly = false;

	/**
	 * @since 2.4
	 */
	public function __construct(
		private readonly Store $store,
		private readonly SpecificationLookup $propertySpecificationLookup,
	) {
	}

	/**
	 * @since 2.4
	 *
	 * @param int $limit
	 */
	public function setLimit( $limit ): void {
		$this->limit = (int)$limit;
	}

	/**
	 * @since 2.5
	 *
	 * @param bool $listOnly
	 */
	public function setListOnly( $listOnly ): void {
		$this->listOnly = (bool)$listOnly;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ): void {
		$this->languageCode = (string)$languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getPropertyList(): array {
		return $this->propertyList;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getNamespaces(): array {
		return $this->namespaces;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getMeta(): array {
		return $this->meta;
	}

	/**
	 * @since 2.4
	 *
	 * @param array
	 */
	public function getContinueOffset(): int {
		return $this->continueOffset;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $property
	 *
	 * @return bool
	 */
	public function findPropertyListBy( $property = '' ): bool {
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

	private function doModifyRequestOptionsWith( $property, RequestOptions $requestOptions ): RequestOptions {
		if ( $property === '' ) {
			return $requestOptions;
		}

		if ( $property[0] !== '_' ) {
			$property = str_replace( "_", " ", $property );
		}

		// Try to match something like _MDAT to find a label and
		// make the request a success
		try {
			$property = Property::newFromUserLabel( $property )->getLabel();
		} catch ( Exception $e ) {
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

	private function addPropertyToList( array $value ): void {
		if ( $value === [] || !$value[0] instanceof Property ) {
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

	private function findPropertyDescriptionBy( Property $property ): string|array|null {
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

	private function matchPropertiesToPreferredLabelBy( $label ): void {
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
