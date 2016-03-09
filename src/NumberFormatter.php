<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class NumberFormatter {

	/**
	 * @var NumberFormatter
	 */
	private static $instance = null;

	/**
	 * @var Localizer
	 */
	private $localizer = null;

	/**
	 * @var integer
	 */
	private $maxNonExpNumber = null;

	/**
	 * @var string|null
	 */
	private	$thousandsSeparatorInContentLanguage = null;

	/**
	 * @var string|null
	 */
	private $decimalSeparatorInContentLanguage = null;

	/**
	 * @var string|null
	 */
	private $decimalSeparatorInUserLanguage = null;

	/**
	 * @since 2.1
	 *
	 * @param integer $maxNonExpNumber
	 * @param Localizer $localizer
	 */
	public function __construct( $maxNonExpNumber, Localizer $localizer ) {
		$this->maxNonExpNumber = $maxNonExpNumber;
		$this->localizer = $localizer;
	}

	/**
	 * @since 2.1
	 *
	 * @return Localizer
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self(
				$GLOBALS['smwgMaxNonExpNumber'],
				Localizer::getInstance()
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.1
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 *
	 * @param mixed $value input number
	 * @param integer|false $precision
	 *
	 * @return string
	 */
	public function getUnformattedNumberByPrecision( $value, $precision = false ) {

		if ( $precision === false ) {
			return $value;
		}

		return $this->doFormatByPrecision(
			$value,
			$precision,
			$this->getDecimalSeparatorForUserLanguage(),
			''
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param mixed $value input number
	 * @param integer|false $precision
	 *
	 * @return string
	 */
	public function getFormattedNumberByPrecision( $value, $precision = false ) {

		if ( $precision === false ) {
			return $value;
		}

		return $this->doFormatByPrecision(
			$value,
			$precision,
			$this->getDecimalSeparatorForUserLanguage(),
			$this->getThousandsSeparatorForContentLanguage()
		);
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
	public function getLocalizedFormattedNumber( $value, $precision = false ) {

		if ( $precision !== false ) {
			return $this->getFormattedNumberByPrecision( $value, $precision );
		}

		// BC configuration to keep default behaviour
		$precision = 3;
		$decseparator = $this->getDecimalSeparatorForUserLanguage();

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
			$value = $this->doFormatByPrecision(
				$value,
				$precision,
				$decseparator,
				$this->getThousandsSeparatorForContentLanguage()
			);

			// Make it more readable by removing ending .000 from nnn.000
			//    Assumes substr is faster than a regular expression replacement.
			$end = $decseparator . str_repeat( '0', $precision );
			$lenEnd = strlen( $end );

			if ( substr( $value, - $lenEnd ) === $end ) {
				$value = substr( $value, 0, - $lenEnd );
			} else {
				// If above replacement occurred, no need to do the next one.
				// Make it more readable by removing trailing zeroes from nn.n00.
				$value = preg_replace( "/(\\$decseparator\\d+?)0*$/u", '$1', $value, 1 );
			}
		}

		return $value;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getThousandsSeparatorForContentLanguage() {

		if ( $this->thousandsSeparatorInContentLanguage === null ) {
			$this->thousandsSeparatorInContentLanguage = wfMessage( 'smw_kiloseparator' )->inLanguage( $this->localizer->getContentLanguage() )->text();
		}

		return $this->thousandsSeparatorInContentLanguage;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getDecimalSeparatorForContentLanguage() {

		if ( $this->decimalSeparatorInContentLanguage === null ) {
			$this->decimalSeparatorInContentLanguage = wfMessage( 'smw_decseparator' )->inLanguage( $this->localizer->getContentLanguage() )->text();
		}

		return $this->decimalSeparatorInContentLanguage;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getDecimalSeparatorForUserLanguage() {

		if ( $this->decimalSeparatorInUserLanguage === null ) {
			$this->decimalSeparatorInUserLanguage = wfMessage( 'smw_decseparator' )->inLanguage( $this->localizer->getUserLanguage() )->text();
		}

		return $this->decimalSeparatorInUserLanguage;
	}

	private function doFormatByPrecision( $value, $precision = false, $decimal, $thousand ) {
		// Format to some level of precision; number_format does rounding and
		// locale formatting, x and y are used temporarily since number_format
		// supports only single characters for either
		$value = number_format( (float)$value, $precision, 'x', 'y' );
		$value = str_replace(
			array( 'x', 'y' ),
			array(
				$decimal,
				$thousand
			),
			$value
		);

		return $value;
	}

}
