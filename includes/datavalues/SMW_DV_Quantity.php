<?php

use SMW\DataValues\UnitConversionFetcher;
use SMW\Message;
use SMW\NumberFormatter;

/**
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements unit support custom units, for which users have
 * provided linear conversion factors within the wiki. Those user settings
 * are retrieved from a property page associated with this object.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWQuantityValue extends SMWNumberValue {

	/**
	 * Array with format (canonical unit ID string) => (conversion factor)
	 * @var float[]|bool
	 */
	protected $m_unitfactors = false;

	/**
	 * Array with format (normalised unit string) => (canonical unit ID string)
	 * @var string[]|bool
	 */
	protected $m_unitids = false;

	/**
	 * Ordered array of (normalized) units that should be displayed in tooltips, etc.
	 * @var string[]|bool
	 */
	protected $m_displayunits = false;

	/**
	 * Main unit in canonical form (recognised by the conversion factor 1)
	 * @var string|bool
	 */
	protected $m_mainunit = false;

	protected function convertToMainUnit( $number, $unit ) {
		$this->initConversionData();

		if ( array_key_exists( $unit, $this->m_unitids ) ) {
			$this->m_unitin = $this->m_unitids[$unit];
			assert( '$this->m_unitfactors[$this->m_unitin] != 0 /* Should be filtered by initConversionData() */' );
			$this->m_dataitem = new SMWDINumber( $number / $this->m_unitfactors[$this->m_unitin], $this->m_typeid );
			return true;
		} else { // unsupported unit
			return false;
		}
	}

	protected function makeConversionValues() {
		if ( $this->m_unitvalues !== false ) {
			return; // do this only once
		}

		$this->m_unitvalues = array();

		if ( !$this->isValid() ) {
			return;
		}

		$this->initDisplayData();

		if ( count( $this->m_displayunits ) == 0 ) { // no display units, just show all
			foreach ( $this->m_unitfactors as $unit => $factor ) {
				if ( $unit !== '' ) { // filter out the empty fallback unit that is always there
					$this->m_unitvalues[$unit] = $this->m_dataitem->getNumber() * $factor;
				}
			}
		} else {
			foreach ( $this->m_displayunits as $unit ) {
				/// NOTE We keep non-ID units unless the input unit is used, so display units can be used to pick
				/// the preferred form of a unit. Doing this requires us to recompute the conversion values whenever
				/// the m_unitin changes.
				$unitkey = ( $this->m_unitids[$unit] == $this->m_unitin ) ? $this->m_unitids[$unit] : $unit;
				$this->m_unitvalues[$unitkey] = $this->m_dataitem->getNumber() * $this->m_unitfactors[$this->m_unitids[$unit]];
			}
		}
	}

	protected function makeUserValue() {
		$printunit = false; // the normalised string of a known unit to use for printouts

		// Check if a known unit is given as outputformat:
		if ( ( $this->m_outformat ) && ( $this->m_outformat != '-' ) &&
		     ( $this->m_outformat != '-n' ) && ( $this->m_outformat != '-u' ) ) { // first try given output unit
			$wantedunit = $this->normalizeUnit( $this->m_outformat );
			if ( array_key_exists( $wantedunit, $this->m_unitids ) ) {
				$printunit = $wantedunit;
			}
		}

		// Alternatively, try to use the main display unit as a default:
		if ( $printunit === false ) {
			$this->initDisplayData();
			if ( count( $this->m_displayunits ) > 0 ) {
				$printunit = reset( $this->m_displayunits );
			}
		}
		// Finally, fall back to main unit:
		if ( $printunit === false ) {
			$printunit = $this->getUnit();
		}

		$asPrefix = isset( $this->prefixalUnitPreference[$printunit] ) && $this->prefixalUnitPreference[$printunit];

		$this->m_unitin = $this->m_unitids[$printunit];
		$this->m_unitvalues = false; // this array depends on m_unitin if displayunits were used, better invalidate it here

		$value = $this->m_dataitem->getNumber() * $this->m_unitfactors[$this->m_unitin];

		$this->m_caption = '';

		if ( $this->m_outformat != '-u' ) { // -u is the format for displaying the unit only
			$this->m_caption .= ( ( $this->m_outformat != '-' ) && ( $this->m_outformat != '-n' ) ? $this->getLocalizedFormattedNumber( $value ) : $this->getNormalizedFormattedNumber( $value ) );
		}

		if ( ( $printunit !== '' ) && ( $this->m_outformat != '-n' ) ) { // -n is the format for displaying the number only

			$sep = '';

			if ( $this->m_outformat != '-u' ) {
				$sep =  ( $this->m_outformat != '-' ? '&#160;' : ' ' );
			}

			$this->m_caption = $asPrefix ? $printunit . $sep . $this->m_caption : $this->m_caption . $sep . $printunit;
		}
	}

	public function getUnitList() {
		$this->initConversionData();
		return array_keys( $this->m_unitfactors );
	}

	public function getUnit() {
		$this->initConversionData();
		return $this->m_mainunit;
	}

/// The remaining functions are relatively "private" but are kept protected since
/// subclasses might exploit this to, e.g., "fake" conversion factors instead of
/// getting them from the database. A cheap way of making built-in types.

	/**
	 * This method initializes $m_unitfactors, $m_unitids, and $m_mainunit.
	 */
	protected function initConversionData() {
		if ( $this->m_unitids !== false ) {
			return; // do the below only once
		}

		$unitConversionFetcher = new UnitConversionFetcher( $this );
		$unitConversionFetcher->fetchCachedConversionData( $this->m_property );

		if ( $unitConversionFetcher->getErrors() !== array() ) {
			foreach ( $unitConversionFetcher->getErrors() as $error ) {
				$this->addErrorMsg(
					$error,
					Message::TEXT,
					Message::USER_LANGUAGE
				);
			}
		}

		$this->m_unitids = $unitConversionFetcher->getUnitIds();
		$this->m_unitfactors = $unitConversionFetcher->getUnitFactors();
		$this->m_mainunit = $unitConversionFetcher->getMainUnit();
		$this->prefixalUnitPreference = $unitConversionFetcher->getPrefixalUnitPreference();
	}

	/**
	 * This method initializes $m_displayunits.
	 */
	protected function initDisplayData() {
		if ( $this->m_displayunits !== false ) {
			return; // do the below only once
		}
		$this->initConversionData(); // needed to normalise unit strings
		$this->m_displayunits = array();

		if ( is_null( $this->m_property ) || is_null( $this->m_property->getDIWikiPage() ) ) {
			return;
		}

		$units = $this->getPropertySpecificationLookup()->getDisplayUnitsFor(
			$this->getProperty()
		);

		foreach ( $units as $unit ) {
			$unit = $this->normalizeUnit( $unit );
			if ( array_key_exists( $unit, $this->m_unitids ) ) {
				$this->m_displayunits[] = $unit; // do not avoid duplicates, users can handle this
			} // note: we ignore unsuppported units -- no way to display them
		}
	}
}
