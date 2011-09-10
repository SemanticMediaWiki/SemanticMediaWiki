<?php

/**
 * Parameter manipulation ensuring the value is an file url.
 * 
 * @since 1.6.2
 * 
 * @file SMW_ParamFormat.php
 * @ingroup SMW
 * @ingroup ParameterManipulations
 * 
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWParamFormat extends ItemParameterManipulation {

	/**
	 * Constructor.
	 * 
	 * @since 1.6.2
	 */
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * @see ItemParameterManipulation::doManipulation
	 * 
	 * @since 0.7
	 */	
	public function doManipulation( &$value, Parameter $parameter, array &$parameters ) {
		global $smwgResultFormats;
		
		$value = trim( $value );
		
		if ( !array_key_exists( $value, $smwgResultFormats ) ) {
			$isAlias = self::resolveFormatAliases( $value );
			
			if ( !$isAlias ) {
				$value = 'auto';  // If it is an unknown format, defaults to list/table again
			}
		}
	}
	
	/**
	 * Turns format aliases into main formats.
	 *
	 * @param string $format
	 *
	 * @return boolean Indicates if the passed format was an alias, and thus was changed.
	 */
	static protected function resolveFormatAliases( &$format ) {
		global $smwgResultAliases;

		$isAlias = false;

		foreach ( $smwgResultAliases as $mainFormat => $aliases ) {
			if ( in_array( $format, $aliases ) ) {
				$format = $mainFormat;
				$isAlias = true;
				break;
			}
		}

		return $isAlias;
	}
	
}
