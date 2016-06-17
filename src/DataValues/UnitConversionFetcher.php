<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;
use SMWNumberValue as NumberValue;

/**
 * Returns conversion data from a cache instance to enable a responsive query
 * feedback and eliminate possible repeated DB requests.
 *
 * The cache is evicted as soon as the property that contains "Corresponds to"
 * is altered.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UnitConversionFetcher {

	/**
	 * @var NumberValue
	 */
	private $numberValue;

	/**
	 * @var CachedPropertyValuesPrefetcher
	 */
	private $cachedPropertyValuesPrefetcher;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var array
	 */
	private $unitIds = array();

	/**
	 * @var array
	 */
	private $unitFactors = array();

	/**
	 * @var false|string
	 */
	private $mainUnit = false;

	/**
	 * @var array
	 */
	protected $prefixalUnitPreference = array();

	/**
	 * @since 2.4
	 *
	 * @param NumberValue $numberValue
	 * @param CachedPropertyValuesPrefetcher|null $cachedPropertyValuesPrefetcher
	 */
	public function __construct( NumberValue $numberValue, CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher = null ) {
		$this->numberValue = $numberValue;
		$this->cachedPropertyValuesPrefetcher = $cachedPropertyValuesPrefetcher;

		if ( $this->cachedPropertyValuesPrefetcher === null ) {
			$this->cachedPropertyValuesPrefetcher = ApplicationFactory::getInstance()->getCachedPropertyValuesPrefetcher();
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getUnitIds() {
		return $this->unitIds;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getUnitFactors() {
		return $this->unitFactors;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getMainUnit() {
		return $this->mainUnit;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPrefixalUnitPreference() {
		return $this->prefixalUnitPreference;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 */
	public function fetchConversionData( DIProperty $property ) {

		$this->unitIds = array();
		$this->unitFactors = array();
		$this->mainUnit = false;
		$this->prefixalUnitPreference = array();
		$this->errors = array();

		$factors = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getDiWikiPage(),
			new DIProperty( '_CONV' )
		);

		$this->numberValue->setContextPage( $property->getDiWikiPage() );

		if ( $factors === null || $factors === array() ) { // no custom type
			return $this->errors[] = 'smw_nounitsdeclared';
		}

		$number = '';
		$unit = '';

		foreach ( $factors as $di ) {

			// ignore corrupted data and bogus inputs
			if ( !( $di instanceof DIBlob ) ||
			     ( $this->numberValue->parseNumberValue( $di->getString(), $number, $unit, $asPrefix ) != 0 ) ||
			     ( $number == 0 ) ) {
				continue;
			}

			$this->matchUnitAliases(
				$number,
				$asPrefix,
				preg_split( '/\s*,\s*/u', $unit )
			);
		}

		// No unit with factor 1? Make empty string the main unit.
		if ( $this->mainUnit === false ) {
			$this->mainUnit = '';
		}

		// Always add an extra empty unit; not as a synonym for the main unit
		// but as a new unit with ID '' so if users do not give any unit, the
		// conversion tooltip will still display the main unit for clarity
		// (the empty unit is never displayed; we filter it when making
		// conversion values)
		$this->unitFactors = array( '' => 1 ) + $this->unitFactors;
		$this->unitIds[''] = '';
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty|null $property
	 */
	public function fetchCachedConversionData( DIProperty $property = null ) {

		if ( $property === null || ( $propertyDiWikiPage = $property->getDiWikiPage() ) === null ) {
			return;
		}

		$blobStore = $this->cachedPropertyValuesPrefetcher->getBlobStore();

		// Ensure that when the property page is altered the cache gets
		// evicted
		$hash = $this->cachedPropertyValuesPrefetcher->getRootHashFor(
			$propertyDiWikiPage
		);

		$container = $blobStore->read(
			$hash
		);

		$key = '--conv';

		if ( $container->has( $key ) ) {
			$data = $container->get( $key );
			$this->unitIds = $data['ids'];
			$this->unitFactors = $data['factors'];
			$this->mainUnit = $data['main'];
			$this->prefixalUnitPreference = $data['prefix'];
			return;
		}

		$this->fetchConversionData( $property );

		if ( $this->errors !== array() ) {
			return;
		}

		$data = array(
			'ids' => $this->unitIds,
			'factors' => $this->unitFactors,
			'main' => $this->mainUnit,
			'prefix' => $this->prefixalUnitPreference
		);

		$container->set( $key, $data );

		$blobStore->save(
			$container
		);
	}

	private function matchUnitAliases( $number, $asPrefix, array $unitAliases ) {
		$first = true;

		foreach ( $unitAliases as $unit ) {
			$unit = $this->numberValue->normalizeUnit( $unit );

			// Legacy match the preserve some behaviour where spaces where normalized
			// no matter what
			$normalizedUnit = $this->numberValue->normalizeUnit(
				str_replace( array( '&nbsp;', '&#160;', '&thinsp;', ' ' ), '', $unit )
			);

			if ( $first ) {
				$unitid = $unit;
				if ( $number == 1 ) { // add main unit to front of array (displayed first)
					$this->mainUnit = $unit;
					$this->unitFactors = array( $unit => 1 ) + $this->unitFactors;
				} else { // non-main units are not ordered (can be modified via display units)
					$this->unitFactors[$unit] = $number;
				}
				$first = false;
			}
			// add all known units to m_unitids to simplify checking for them
			$this->unitIds[$unit] = $unitid;
			$this->unitIds[$normalizedUnit] = $unitid;
			$this->prefixalUnitPreference[$unit] = $asPrefix;
		}
	}

}
