<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\Highlighter;
use SMWDataValue as DataValue;
use SMWNumberValue as NumberValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class NumberValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof NumberValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof NumberValue ) {
			throw new RuntimeException( "The formatter is missing a valid NumberValue object" );
		}

		if ( $type === self::VALUE ) {
			return $this->valueFormat();
		}

		if ( $type === self::WIKI_SHORT || $type === self::HTML_SHORT ) {
			return $this->shortFormat( $linker );
		}

		if ( $type === self::WIKI_LONG || $type === self::HTML_LONG ) {
			return $this->longFormat( $linker );
		}

		return 'UNKNOWN';
	}

	private function valueFormat() {

		if ( !$this->dataValue->isValid() ) {
			return 'error';
		}

		$unit = $this->dataValue->getUnit();

		$number = $this->dataValue->getNormalizedFormattedNumber(
			$this->dataValue->getNumber()
		);

		if ( $unit === '' ) {
			return $number;
		}

		return $this->dataValue->hasPrefixalUnitPreference( $unit ) ? $unit . ' ' . $number : $number . ' ' . $unit;
	}

	private function shortFormat( $linker = null ) {

		$outformat = $this->dataValue->getOutputFormat();

		if ( $linker === null || ( $linker === false ) || ( $outformat == '-' ) || ( $outformat == '-u' ) || ( $outformat == '-n' ) || !$this->dataValue->isValid() ) {
			return $this->dataValue->getCaption();
		}

		$convertedUnitValues = $this->dataValue->getConvertedUnitValues();
		$tooltip = '';

		$i = 0;

		foreach ( $convertedUnitValues as $unit => $value ) {
			if ( $unit != $this->dataValue->getCanonicalMainUnit() ) {
				$number = $this->dataValue->getLocalizedFormattedNumber( $value );
				if ( $unit !== '' ) {
					$tooltip .= $this->dataValue->hasPrefixalUnitPreference( $unit ) ? $unit . '&#160;' . $number : $number . '&#160;' . $unit;
				} else{
					$tooltip .= $number;
				}
				$tooltip .= ' <br />';
				$i++;
				if ( $i >= 5 ) { // limit number of printouts in tooltip
					break;
				}
			}
		}

		if ( $tooltip === '' ) {
			return $this->dataValue->getCaption();
		}

		$highlighter = Highlighter::factory(
			Highlighter::TYPE_QUANTITY,
			$this->dataValue->getOption( DataValue::OPT_USER_LANGUAGE )
		);

		$highlighter->setContent(
			[
				'caption' => $this->dataValue->getCaption(),
				'content' => $tooltip
			]
		);

		return $highlighter->getHtml();
	}

	private function longFormat( $linker = null ) {

		if ( !$this->dataValue->isValid() ) {
			return $this->dataValue->getErrorText();
		}

		$outformat = $this->dataValue->getOutputFormat();
		$convertedUnitValues = $this->dataValue->getConvertedUnitValues();

		$result = '';
		$i = 0;

		foreach ( $convertedUnitValues as $unit => $value ) {

			if ( $i == 1 ) {
				$result .= ' (';
			} elseif ( $i > 1 ) {
				$result .= ', ';
			}

			$number = ( $outformat != '-' ? $this->dataValue->getLocalizedFormattedNumber( $value ) : $value );

			if ( $unit !== '' ) {
				$result .= $this->dataValue->hasPrefixalUnitPreference( $unit ) ? $unit . '&#160;' . $number : $number . '&#160;' . $unit;
			} else {
				$result .= $number;
			}

			$i++;

			if ( $outformat == '-' ) { // no further conversions for plain output format
				break;
			}
		}

		if ( $i > 1 ) {
			$result .= ')';
		}

		return $result;
	}

}
