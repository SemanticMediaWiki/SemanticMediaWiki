<?php

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
			$wantedunit = SMWNumberValue::normalizeUnit( $this->m_outformat );
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

		$this->m_unitin = $this->m_unitids[$printunit];
		$this->m_unitvalues = false; // this array depends on m_unitin if displayunits were used, better invalidate it here
		$value = $this->m_dataitem->getNumber() * $this->m_unitfactors[$this->m_unitin];

		$this->m_caption = '';
		if ( $this->m_outformat != '-u' ) { // -u is the format for displaying the unit only
			$this->m_caption .= ( ( $this->m_outformat != '-' ) && ( $this->m_outformat != '-n' ) ? NumberFormatter::getInstance()->formatNumberToLocalizedText( $value ) : $value );
		}

		if ( ( $printunit !== '' ) && ( $this->m_outformat != '-n' ) ) { // -n is the format for displaying the number only
			if ( $this->m_outformat != '-u' ) {
				$this->m_caption .=  ( $this->m_outformat != '-' ? '&#160;' : ' ' );
			}
			$this->m_caption .= $printunit;
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

		$this->m_unitids = array();
		$this->m_unitfactors = array();
		$this->m_mainunit = false;

		if ( !is_null( $this->m_property ) ) {
			$propertyDiWikiPage = $this->m_property->getDiWikiPage();
		}

		if ( is_null( $this->m_property ) || is_null( $propertyDiWikiPage ) ) {
			return; // we cannot find conversion factors without the property
		}

		$factors = \SMW\StoreFactory::getStore()->getPropertyValues( $propertyDiWikiPage, new SMWDIProperty( '_CONV' ) );
		if ( count( $factors ) == 0 ) { // no custom type
			$this->addError( wfMessage( 'smw_nounitsdeclared' )->inContentLanguage()->text() );
			return;
		}

		$number = $unit = '';

		foreach ( $factors as $di ) {
			if ( !( $di instanceof SMWDIBlob ) ||
			     ( SMWNumberValue::parseNumberValue( $di->getString(), $number, $unit ) != 0 ) ||
			     ( $number == 0 ) ) {
				continue; // ignore corrupted data and bogus inputs
			}
			$unit_aliases = preg_split( '/\s*,\s*/u', $unit );
			$first = true;
			foreach ( $unit_aliases as $unit ) {
				$unit = SMWNumberValue::normalizeUnit( $unit );
				if ( $first ) {
					$unitid = $unit;
					if ( $number == 1 ) { // add main unit to front of array (displayed first)
						$this->m_mainunit = $unit;
						$this->m_unitfactors = array( $unit => 1 ) + $this->m_unitfactors;
					} else { // non-main units are not ordered (can be modified via display units)
						$this->m_unitfactors[$unit] = $number;
					}
					$first = false;
				}
				// add all known units to m_unitids to simplify checking for them
				$this->m_unitids[$unit] = $unitid;
			}
		}

		if ( $this->m_mainunit === false ) { // No unit with factor 1? Make empty string the main unit.
			$this->m_mainunit = '';
		}

		// always add an extra empty unit; not as a synonym for the main unit but as a new unit with ID ''
		// so if users do not give any unit, the conversion tooltip will still display the main unit for clarity
		// (the empty unit is never displayed; we filter it when making conversion values)
		$this->m_unitfactors = array( '' => 1 ) + $this->m_unitfactors;
		$this->m_unitids[''] = '';
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

		$dataItems = \SMW\StoreFactory::getStore()->getPropertyValues( $this->m_property->getDIWikiPage(), new SMWDIProperty( '_UNIT' ) );
		$units = array();

		foreach ( $dataItems as $di ) { // Join all if many annotations exist. Discouraged (random order) but possible.
			if ( $di instanceof SMWDIBlob ) {
				$units = $units + preg_split( '/\s*,\s*/u', $di->getString() );
			}
		}

		foreach ( $units as $unit ) {
			$unit = SMWNumberValue::normalizeUnit( $unit );
			if ( array_key_exists( $unit, $this->m_unitids ) ) {
				$this->m_displayunits[] = $unit; // do not avoid duplicates, users can handle this
			} // note: we ignore unsuppported units -- no way to display them
		}
	}
}
