<?php

namespace SMW\DataValues\Number;

use SMW\PropertySpecificationLookup;
use SMW\EntityCache;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;
use SMWNumberValue as NumberValue;

/**
 * Returns conversion data from a cached instance to enable a responsive query
 * feedback and eliminate possible repeated DB requests.
 *
 * The cache is evicted as soon as the subject that contains "Corresponds to"
 * is altered.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UnitConverter {

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private $unitIds = [];

	/**
	 * @var array
	 */
	private $unitFactors = [];

	/**
	 * @var false|string
	 */
	private $mainUnit = false;

	/**
	 * @var array
	 */
	protected $prefixalUnitPreference = [];

	/**
	 * @since 2.4
	 *
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 * @param EntityCache $entityCache
	 */
	public function __construct( PropertySpecificationLookup $propertySpecificationLookup, EntityCache $entityCache ) {
		$this->propertySpecificationLookup = $propertySpecificationLookup;
		$this->entityCache = $entityCache;
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
	 * @param NumberValue $numberValue
	 */
	public function loadConversionData( NumberValue $numberValue ) {

		$this->errors = [];
		$property = $numberValue->getProperty();

		if ( $property === null || ( $subject = $property->getDiWikiPage() ) === null ) {
			return;
		}

		$key = $this->entityCache->makeCacheKey( 'unit', $subject->getHash() );

		if ( ( $data = $this->entityCache->fetch( $key ) ) !== false ) {
			$this->unitIds = $data['ids'];
			$this->unitFactors = $data['factors'];
			$this->mainUnit = $data['main'];
			$this->prefixalUnitPreference = $data['prefix'];
		} else {
			$this->initConversionData( $subject, $key, $numberValue );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param NumberValue $numberValue
	 */
	public function fetchConversionData( NumberValue $numberValue ) {

		$property = $numberValue->getProperty();

		$this->unitIds = [];
		$this->unitFactors = [];
		$this->mainUnit = false;
		$this->prefixalUnitPreference = [];
		$this->errors = [];

		$factors = $this->propertySpecificationLookup->getSpecification(
			$property->getDiWikiPage(),
			new DIProperty( '_CONV' )
		);

		$numberValue->setContextPage( $property->getDiWikiPage() );

		if ( $factors === null || $factors === [] ) { // no custom type
			return $this->errors[] = 'smw_nounitsdeclared';
		}

		$number = '';
		$unit = '';
		$asPrefix = false;

		foreach ( $factors as $di ) {

			// ignore corrupted data and bogus inputs
			if ( !( $di instanceof DIBlob ) ||
			     ( $numberValue->parseNumberValue( $di->getString(), $number, $unit, $asPrefix ) != 0 ) ||
			     ( $number == 0 ) ) {
				continue;
			}

			$this->matchUnitAliases(
				$numberValue,
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
		$this->unitFactors = [ '' => 1 ] + $this->unitFactors;
		$this->unitIds[''] = '';
	}

	private function initConversionData( $subject, $key, $numberValue ) {

		$this->fetchConversionData( $numberValue );

		foreach ( $this->errors as $error ) {
			$numberValue->addErrorMsg( $error );
		}

		if ( $this->errors !== [] ) {
			return;
		}

		$data = [
			'ids' => $this->unitIds,
			'factors' => $this->unitFactors,
			'main' => $this->mainUnit,
			'prefix' => $this->prefixalUnitPreference
		];

		$this->entityCache->save( $key, $data );

		// Connect to the property page so that it can be flushed once the
		// property page content changes
		$this->entityCache->associate( $subject, $key );
	}

	private function matchUnitAliases( $numberValue, $number, $asPrefix, array $unitAliases ) {
		$first = true;

		foreach ( $unitAliases as $unit ) {
			$unit = $numberValue->normalizeUnit( $unit );

			// Legacy match the preserve some behaviour where spaces where normalized
			// no matter what
			$normalizedUnit = $numberValue->normalizeUnit(
				str_replace( [ '&nbsp;', '&#160;', '&thinsp;', ' ' ], '', $unit )
			);

			if ( $first ) {
				$unitid = $unit;
				if ( $number == 1 ) { // add main unit to front of array (displayed first)
					$this->mainUnit = $unit;
					$this->unitFactors = [ $unit => 1 ] + $this->unitFactors;
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
