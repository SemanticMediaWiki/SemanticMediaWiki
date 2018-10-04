<?php

namespace SMW\DataValues\Number;

use InvalidArgumentException;
use SMW\Message;
use SMW\Options;
use SMWNumberValue as NumberValue;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class IntlNumberFormatter {

	/**
	 * Localization related constants
	 */
	const CONTENT_LANGUAGE = Message::CONTENT_LANGUAGE;
	const USER_LANGUAGE = Message::USER_LANGUAGE;
	const PREFERRED_LANGUAGE = 'preferred.language';

	/**
	 * Separator related constants
	 */
	const DECIMAL_SEPARATOR = NumberValue::DECIMAL_SEPARATOR;
	const THOUSANDS_SEPARATOR = NumberValue::THOUSANDS_SEPARATOR;

	/**
	 * Format related constants
	 */
	const DEFAULT_FORMAT = 'default.format';
	const VALUE_FORMAT = 'value.format';

	/**
	 * @var IntlNumberFormatter
	 */
	private static $instance = null;

	/**
	 * @var Options
	 */
	private $options = null;

	/**
	 * @var integer
	 */
	private $maxNonExpNumber = null;

	/**
	 * @var integer
	 */
	private $defaultPrecision = 3;

	/**
	 * @since 2.1
	 *
	 * @param integer $maxNonExpNumber
	 */
	public function __construct( $maxNonExpNumber ) {
		$this->maxNonExpNumber = $maxNonExpNumber;
		$this->options = new Options();
	}

	/**
	 * @since 2.1
	 *
	 * @return IntlNumberFormatter
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self(
				$GLOBALS['smwgMaxNonExpNumber']
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 */
	public function reset() {
		$this->options->set( self::DECIMAL_SEPARATOR, false );
		$this->options->set( self::THOUSANDS_SEPARATOR, false );
		$this->options->set( self::USER_LANGUAGE, false );
		$this->options->set( self::CONTENT_LANGUAGE, false );
		$this->options->set( self::PREFERRED_LANGUAGE, false );
	}

	/**
	 * @since 2.4
	 *
	 * @return string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {
		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $type
	 * @param string|integer $locale
	 *
	 * @return string
	 */
	public function getSeparatorByLanguage( $type, $locale = '' ) {

		$language = $locale === self::USER_LANGUAGE ? $this->getUserLanguage() : $this->getContentLanguage();

		if ( $type === self::DECIMAL_SEPARATOR ) {
			return $this->getPreferredLocalizedSeparator( self::DECIMAL_SEPARATOR, 'smw_decseparator', $language );
		}

		if ( $type === self::THOUSANDS_SEPARATOR ) {
			return $this->getPreferredLocalizedSeparator( self::THOUSANDS_SEPARATOR, 'smw_kiloseparator', $language );
		}

		throw new InvalidArgumentException( $type . " is unknown" );
	}

	/**
	 * This method formats a float number value according to the given language and
	 * precision settings, with some intelligence to produce readable output. Used
	 * to format a number that was not hand-formatted by a user.
	 *
	 * @param mixed $value input number
	 * @param integer|false $precision optional positive integer, controls how many digits after
	 * the decimal point are shown
	 * @param string|integer $format
	 *
	 * @since 2.1
	 *
	 * @return string
	 */
	public function format( $value, $precision = false, $format = '' ) {

		if ( $format === self::VALUE_FORMAT ) {
			return $this->getValueFormattedNumberWithPrecision( $value, $precision );
		}

		if ( $precision !== false || $format === self::DEFAULT_FORMAT ) {
			return $this->getDefaultFormattedNumberWithPrecision( $value, $precision );
		}

		return $this->doFormatByHeuristicRuleWith( $value, $precision );
	}

	/**
	 * This method formats a float number value according to the given language and
	 * precision settings, with some intelligence to produce readable output. Used
	 * to format a number that was not hand-formatted by a user.
	 *
	 * @param mixed $value input number
	 * @param integer|false $precision optional positive integer, controls how many digits after
	 * the decimal point are shown
	 *
	 * @since 2.1
	 *
	 * @return string
	 */
	private function doFormatByHeuristicRuleWith( $value, $precision = false ) {

		// BC configuration to keep default behaviour
		$precision = $this->defaultPrecision;

		$decseparator = $this->getSeparatorByLanguage(
			self::DECIMAL_SEPARATOR,
			self::USER_LANGUAGE
		);

		// If number is a trillion or more, then switch to scientific
		// notation. If number is less than 0.0000001 (i.e. twice precision),
		// then switch to scientific notation. Otherwise print number
		// using number_format. This may lead to 1.200, so then use trim to
		// remove trailing zeroes.
		$doScientific = false;

		// @todo: Don't do all this magic for integers, since the formatting does not fit there
		//       correctly. E.g. one would have integers formatted as 1234e6, not as 1.234e9, right?
		// The "$value!=0" is relevant: we want to scientify numbers that are close to 0, but never 0!
		if ( ( $precision > 0 ) && ( $value != 0 ) ) {
			$absValue = abs( $value );
			if ( $absValue >= $this->maxNonExpNumber ) {
				$doScientific = true;
			} elseif ( $absValue < pow( 10, - $precision ) ) {
				$doScientific = true;
			} elseif ( $absValue < 1 ) {
				if ( $absValue < pow( 10, - $precision ) ) {
					$doScientific = true;
				} else {
					// Increase decimal places for small numbers, e.g. .00123 should be 5 places.
					for ( $i = 0.1; $absValue <= $i; $i *= 0.1 ) {
						$precision++;
					}
				}
			}
		}

		if ( $doScientific ) {
			// Should we use decimal places here?
			$value = sprintf( "%1.6e", $value );
			// Make it more readable by removing trailing zeroes from n.n00e7.
			$value = preg_replace( '/(\\.\\d+?)0*e/u', '${1}e', $value, 1 );
			// NOTE: do not use the optional $count parameter with preg_replace. We need to
			//      remain compatible with PHP 4.something.
			if ( $decseparator !== '.' ) {
				$value = str_replace( '.', $decseparator, $value );
			}
		} else {
			$value = $this->doFormatWithPrecision(
				$value,
				$precision,
				$decseparator,
				$this->getSeparatorByLanguage( self::THOUSANDS_SEPARATOR, self::USER_LANGUAGE )
			);

			// Make it more readable by removing ending .000 from nnn.000
			//    Assumes substr is faster than a regular expression replacement.
			$end = $decseparator . str_repeat( '0', $precision );
			$lenEnd = strlen( $end );

			if ( substr( $value, - $lenEnd ) === $end ) {
				$value = substr( $value, 0, - $lenEnd );
			} else {
				$decseparator = preg_quote( $decseparator, '/' );
				// If above replacement occurred, no need to do the next one.
				// Make it more readable by removing trailing zeroes from nn.n00.
				$value = preg_replace( "/($decseparator\\d+?)0*$/u", '$1', $value, 1 );
			}
		}

		return $value;
	}

	private function getValueFormattedNumberWithPrecision( $value, $precision = false ) {

		// The decimal are in ISO format (.), the separator as plain representation
		// may collide with the content language (FR) therefore use the content language
		// to match the decimal separator
		if ( $this->isScientific( $value ) ) {
			return $this->doFormatExponentialNotation( $value );
		}

		if ( $precision === false || $precision === null ) {
			$precision = $this->getPrecisionFrom( $value );
		}

		return $this->doFormatWithPrecision(
			$value,
			$precision,
			$this->getSeparatorByLanguage( self::DECIMAL_SEPARATOR, self::CONTENT_LANGUAGE ),
			''
		);
	}

	private function getDefaultFormattedNumberWithPrecision( $value, $precision = false ) {

		if ( $precision === false ) {
			return $this->isDecimal( $value ) ? $this->applyDefaultPrecision( $value ) : floatval( $value );
		}

		return $this->doFormatWithPrecision(
			$value,
			$precision,
			$this->getSeparatorByLanguage( self::DECIMAL_SEPARATOR, self::USER_LANGUAGE ),
			$this->getSeparatorByLanguage( self::THOUSANDS_SEPARATOR, self::USER_LANGUAGE )
		);
	}

	private function isDecimal( $value ) {
		return floor( $value ) !== $value;
	}

	private function isScientific( $value ) {
		return strpos( $value, 'E' ) !== false || strpos( $value, 'e' ) !== false;
	}

	private function applyDefaultPrecision( $value ) {
		return round( $value, $this->defaultPrecision );
	}

	private function getPrecisionFrom( $value ) {
		return strlen( strrchr( $value, "." ) ) - 1;
	}

	private function doFormatExponentialNotation( $value ) {
		return str_replace(
			[ '.', 'E' ],
			[ $this->getSeparatorByLanguage( self::DECIMAL_SEPARATOR, self::CONTENT_LANGUAGE ), 'e' ],
			$value
		);
	}

	private function doFormatWithPrecision( $value, $precision = false, $decimal, $thousand ) {

		$replacement = 0;

		// Don't try to be more precise than the actual value (e.g avoid turning
		// 72.769482308 into 72.76948230799999350892904)
		if ( ( $actualPrecision = $this->getPrecisionFrom( $value ) ) < $precision && $actualPrecision > 0 && !$this->isScientific( $value ) ) {
			$replacement = $precision - $actualPrecision;
			$precision = $actualPrecision;
		}

		$value = (float)$value;
		$isNegative = $value < 0;

		// Format to some level of precision; number_format does rounding and
		// locale formatting, x and y are used temporarily since number_format
		// supports only single characters for either
		$value = number_format( $value, $precision, 'x', 'y' );

		// Due to https://bugs.php.net/bug.php?id=76824
		if ( $isNegative && $value >= 0 ) {
			$value = "-$value";
		}

		$value = str_replace(
			[ 'x', 'y' ],
			[
				$decimal,
				$thousand
			],
			$value
		);

		if ( $replacement > 0 ) {
			 $value .= str_repeat( '0', $replacement );
		}

		return $value;
	}

	private function getUserLanguage() {

		$language = Message::USER_LANGUAGE;

		// The preferred language is set when the output formatter contained
		// something like LOCL@es

		if ( $this->options->has( self::PREFERRED_LANGUAGE ) && $this->options->get( self::PREFERRED_LANGUAGE ) ) {
			$language = $this->options->get( self::PREFERRED_LANGUAGE );
		} elseif ( $this->options->has( self::USER_LANGUAGE ) && $this->options->get( self::USER_LANGUAGE ) ) {
			$language = $this->options->get( self::USER_LANGUAGE );
		}

		return $language;
	}

	private function getContentLanguage() {

		$language = Message::CONTENT_LANGUAGE;

		if ( $this->options->has( self::CONTENT_LANGUAGE ) && $this->options->get( self::CONTENT_LANGUAGE ) ) {
			$language = $this->options->get( self::CONTENT_LANGUAGE );
		}

		return $language;
	}

	private function getPreferredLocalizedSeparator( $custom, $standard, $language ) {

		if ( $this->options->has( $custom ) && ( $separator = $this->options->get( $custom ) ) !== false ) {
			return $separator;
		}

		return Message::get( $standard, Message::TEXT, $language );
	}

}
