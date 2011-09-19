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
	 * List of the queries print requests, used to determine the format
	 * when it's not povided. Set with setPrintRequests before passing
	 * to Validator.
	 * 
	 * @since 1.6.2
	 * 
	 * @var array|false
	 */
	protected $printRequests = null;
	
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
	 * @since 1.6.2
	 */	
	public function doManipulation( &$value, Parameter $parameter, array &$parameters ) {
		// Make sure the format value is valid.
		$value = self::getValidFormatName( $value );
		
		// Add the formats parameters to the parameter list.
		$queryPrinter = SMWQueryProcessor::getResultPrinter( $value );
		
		$parameters = array_merge( $parameters, $queryPrinter->getValidatorParameters() );
	}
	
	/**
	 * Takes a format name, which can be an alias and returns something
	 * that certainly valid. Aliases are resvolved. Invalid formats
	 * will result into 'auto' being returned.
	 * 
	 * @since 1.6.2
	 * 
	 * @param string $value
	 * 
	 * @return string
	 */
	protected function getValidFormatName( $value ) {
		global $smwgResultFormats;
		
		$value = trim( $value );
		
		if ( !array_key_exists( $value, $smwgResultFormats ) ) {
			$isAlias = self::resolveFormatAliases( $value );
			
			if ( !$isAlias ) {
				$value = $this->getDefaultFormat();
			}
		}
		
		return $value;
	}
	
	/**
	 * Turns format aliases into main formats.
	 *
	 * @since 1.6.2
	 *
	 * @param string $format
	 *
	 * @return boolean Indicates if the passed format was an alias, and thus was changed.
	 */
	public static function resolveFormatAliases( &$format ) {
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
	
	/**
	 * Determines and returns the default format, based on the queries print
	 * requests, if provided.
	 * 
	 * @since 1.6.2
	 * 
	 * @return string Array key in $smwgResultFormats
	 */
	protected function getDefaultFormat() {
		if ( is_null( $this->printRequests ) ) {
			return 'table';
		}
		else {
			$format = false;
			
			/**
			 * This hook allows extensions to override SMWs implementation of default result
			 * format handling.
			 * 
			 * @since 1.5.2
			 */
			wfRunHooks( 'SMWResultFormat', array( &$format, $this->printRequests, array() ) );		
	
			// If no default was set by an extension, use a table or list, depending on the column count.
			if ( $format === false ) {
				$format = count( $this->printRequests ) == 0 ? 'list' : 'table';
			}
			
			return $format;
		}
	}
	
	/**
	 * Sets the print requests of the query, used for determining
	 * the default format if none is provided.
	 * 
	 * @since 1.6.2
	 * 
	 * @param $printRequests array of SMWPrintRequest
	 */
	public function setPrintRequests( array /* of SMWPrintRequest */ $printRequests ) {
		$this->printRequests = $printRequests;
	}
	
}
