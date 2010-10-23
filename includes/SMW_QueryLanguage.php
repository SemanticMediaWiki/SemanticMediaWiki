<?php

/**
 * Static class for functions related to the SMW query language.
 * 
 * @since 1.5.3
 * 
 * @file SMW_QueryLanguage.php
 * @ingroup SMW
 * 
 * @author Jeroen De Dauw
 */
final class SMWQueryLanguage {
	
	/**
	 * Associative array that maps the comparator strings (keys) to
	 * the SMW_CMP_ enum (values). Call initializeComparators before using.
	 * 
	 * @since 1.5.3
	 * 
	 * @var array
	 */
	protected static $comparators = array();
	
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
	public static function getComparatorFromString( $string, $defaultComparator = SMW_CMP_EQ ) {
		self::initializeComparators();
		return array_key_exists( $string, self::$comparators ) ? self::$comparators[$string] : $defaultComparator;
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
	public static function getStringForComparator( $comparator ) {
		self::initializeComparators();
		static $reverseCache = false;
		
		if ( $reverseCache === false ) {
			$reverseCache = array_reverse( self::$comparators );
		}
		
		if ( array_key_exists( $comparator, $reverseCache ) ) {
			return $reverseCache[$comparator];
		}
		else {
			throw new Exception( "Comparator $comparator does not have a string representatation" );
		}
	}
	
	/**
	 * Initializes the $comparators field.
	 * 
	 * @since 1.5.3
	 */
	protected static function initializeComparators() {
		global $smwgQComparators, $smwStrictComparators;
		static $initialized = false;
		
		if ( $initialized ) {
			return;
		}
		
		$initialized = true;
		
		$comparators = array(
			'' => SMW_CMP_EQ,
			'<' => $smwStrictComparators ? SMW_CMP_LESS : SMW_CMP_LEQ,
			'>' => $smwStrictComparators ? SMW_CMP_GRTR : SMW_CMP_GEQ,
			'≤' => SMW_CMP_LEQ,
			'≥' => SMW_CMP_GEQ,
			'!' => SMW_CMP_NEQ,
			'~' => SMW_CMP_LIKE,
			'!~' => SMW_CMP_NLKE,
			'<<' => SMW_CMP_LESS,
			'>>' => SMW_CMP_GRTR,
		);
		
		$allowedComparators = explode( '|', $smwgQComparators );		
		
		// Remove the comparators that are not allowed.
		foreach ( $comparators as $string => $comparator ) {
			if ( !in_array( $string, $allowedComparators ) ) {
				unset( $comparators[$string] );
			}
		}

		self::$comparators = $comparators;
	}
	
}
