<?php

namespace SMW\Query;

/**
 * Static class for functions related to the SMW query language.
 *
 * Note: the query language "definition" is located at various places in the SMW codebase.
 * SMWQueryParser defines most of the actual query syntax.
 * SMWDescription defines the semantic elements of the query language.
 * This class is an attempt to gradualy migrate to having all the stuff at one location,
 * clearly distinguised from non-language code.
 *
 * @since 1.5.3
 *
 * @author Jeroen De Dauw
 */
class QueryComparator {

	/**
	 * @var QueryComparator
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $comparators = null;

	/**
	 * @var array
	 */
	private $reverseCache = array();

	/**
	 * @since 2.3
	 *
	 * @param string $comparatorList
	 * @param boolean $strictComparators
	 */
	public function __construct( $comparatorList, $strictComparators ) {
		$this->comparators = $this->getEnabledComparators( $comparatorList, $strictComparators );
	}

	/**
	 * @since 2.3
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self(
				$GLOBALS['smwgQComparators'],
				$GLOBALS['smwStrictComparators']
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.3
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * Gets an array with all suported comparator strings.
	 * The string for SMW_CMP_EQ, which is an empty string, is not in this list.
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function getComparatorStrings() {
		return array_keys( $this->comparators );
	}

	/**
	 * Gets the SMW_CMP_ for a string comparator, falling back to the
	 * $defaultComparator when none is found.
	 *
	 * @since 1.5.3
	 *
	 * @param string $string
	 * @param integer $defaultComparator Item of the SMW_CMP_ enum
	 *
	 * @return integer Item of the SMW_CMP_ enum
	 */
	public function getComparatorFromString( $string, $defaultComparator = SMW_CMP_EQ ) {

		if ( $string === '' ) {
			return SMW_CMP_EQ;
		}

		return array_key_exists( $string, $this->comparators ) ? $this->comparators[$string] : $defaultComparator;
	}

	/**
	 * Extract possible comparators from a value and alter it to consist
	 * only of the remaining effective value string (without the comparator).
	 *
	 * @since 2.4
	 *
	 * @param $value
	 *
	 * @return integer
	 */
	public function extractComparatorFromString( &$value ) {

		$comparator = SMW_CMP_EQ;

		foreach ( $this->getComparatorStrings() as $string ) {
			if ( strpos( $value, $string ) === 0 ) {
				$comparator = $this->getComparatorFromString( substr( $value, 0, strlen( $string ) ) );
				$value = substr( $value, strlen( $string ) );
				break;
			}
		}

		return $comparator;
	}

	/**
	 * Gets the comparator string for a comparator.
	 *
	 * @since 1.5.3
	 *
	 * @param $comparator
	 *
	 * @return string
	 */
	public function getStringForComparator( $comparator ) {

		if ( $this->reverseCache === array() ) {
			$this->reverseCache = array_flip( $this->comparators );
		}

		if ( $comparator == SMW_CMP_EQ ) {
			return '';
		} elseif ( array_key_exists( $comparator, $this->reverseCache ) ) {
			return $this->reverseCache[$comparator];
		}

		throw new Exception( "Comparator $comparator does not have a string representatation" );
	}

	private function getEnabledComparators( $comparatorList, $strictComparators ) {

		// Note: Comparators that contain other comparators at the beginning of the string need to be at beginning of the array.
		$comparators = array(
			'!~' => SMW_CMP_NLKE,
			'<<' => SMW_CMP_LESS,
			'>>' => SMW_CMP_GRTR,
			'<' => $strictComparators ? SMW_CMP_LESS : SMW_CMP_LEQ,
			'>' => $strictComparators ? SMW_CMP_GRTR : SMW_CMP_GEQ,
			'≤' => SMW_CMP_LEQ,
			'≥' => SMW_CMP_GEQ,
			'!' => SMW_CMP_NEQ,
			'~' => SMW_CMP_LIKE,
		);

		if ( strpos( $comparatorList, '|' ) === false ) {
			return $comparators;
		}

		$allowedComparators = explode( '|', $comparatorList );

		// Remove the comparators that are not allowed.
		foreach ( $comparators as $string => $comparator ) {
			if ( !in_array( $string, $allowedComparators ) ) {
				unset( $comparators[$string] );
			}
		}

		return $comparators;
	}

}
