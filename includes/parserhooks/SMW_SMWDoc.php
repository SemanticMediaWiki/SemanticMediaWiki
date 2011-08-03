<?php

/**
 * Class for the 'smwdoc' parser hooks, 
 * which displays parameter documentation for a specified result format.
 * 
 * @since 1.6
 * 
 * @file SMW_SMWDoc.php
 * @ingroup SMW
 * 
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWSMWDoc extends ParserHook {
	
	/**
	 * Field to store the value of the language parameter.
	 * 
	 * @since 1.6.1
	 * 
	 * @var string
	 */
	protected $language;
	
	/**
	 * No LSB in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */
	public static function staticMagic( array &$magicWords, $langCode ) {
		$instance = new self;
		return $instance->magic( $magicWords, $langCode );
	}
	
	/**
	 * No LSB in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */	
	public static function staticInit( Parser &$parser ) {
		$instance = new self;
		return $instance->init( $parser );
	}	
	
	/**
	 * Gets the name of the parser hook.
	 * @see ParserHook::getName
	 * 
	 * @since 1.6
	 * 
	 * @return string
	 */
	protected function getName() {
		return 'smwdoc';
	}
	
	/**
	 * Returns an array containing the parameter info.
	 * @see ParserHook::getParameterInfo
	 * 
	 * @since 1.6
	 * 
	 * @return array
	 */
	protected function getParameterInfo( $type ) {
		$params = array();
		
		$params['format'] = new Parameter( 'format' );
		$params['format']->addCriteria( new CriterionInArray( array_keys( $GLOBALS['smwgResultFormats'] ) ) );
		$params['format']->setMessage( 'smw-smwdoc-par-format' );
		
		$params['language'] = new Parameter( 'language' );
		$params['language']->setDefault( $GLOBALS['wgLanguageCode'] );
		$params['language']->setMessage( 'smw-smwdoc-par-language' );
		
		return $params;
	}
	
	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 * 
	 * @since 1.6
	 * 
	 * @return array
	 */
	protected function getDefaultParameters( $type ) {
		return array( 'format', 'language' );
	}
	
	/**
	 * Renders and returns the output.
	 * @see ParserHook::render
	 * 
	 * @since 1.0
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public function render( array $parameters ) {
		$this->language = $parameters['language'];
		
		$params = $this->getFormatParameters( $parameters['format'] );
		
		return $this->getParameterTable( $params );		
	}
	
	/**
	 * Returns the wikitext for a table listing the provided parameters.
	 *
	 * @since 1.6
	 *
	 * @param array $parameters
	 *
	 * @return string
	 */
	protected function getParameterTable( array $parameters ) {
		$tableRows = array();
		$hasAliases = false;
		
		foreach ( $parameters as $parameter ) {
			$hasAliases = count( $parameter->getAliases() ) != 0;
			if ( $hasAliases ) break; 
		}
		
		foreach ( $parameters as $parameter ) {
			if ( $parameter->getName() != 'format' ) {
				$tableRows[] = $this->getDescriptionRow( $parameter, $hasAliases );
			}
		}

		$table = '';

		if ( count( $tableRows ) > 0 ) {
			$tableRows = array_merge( array(
			'!' . $this->msg( 'validator-describe-header-parameter' ) ."\n" .
			( $hasAliases ? '!' . $this->msg( 'validator-describe-header-aliases' ) ."\n" : '' ) .
			'!' . $this->msg( 'validator-describe-header-type' ) ."\n" .
			'!' . $this->msg( 'validator-describe-header-default' ) ."\n" .
			'!' . $this->msg( 'validator-describe-header-description' )
			), $tableRows );

			$table = implode( "\n|-\n", $tableRows );

			$table = 
					'{| class="wikitable sortable"' . "\n" .
					$table .
					"\n|}";
		}

		return $table;
	}
	
	/**
	 * Returns the wikitext for a table row describing a single parameter.
	 *
	 * @since 1.6
	 *
	 * @param Parameter $parameter
	 * @param boolean $hasAliases
	 *
	 * @return string
	 */
	protected function getDescriptionRow( Parameter $parameter, $hasAliases ) {
		if ( $hasAliases ) {
			$aliases = $parameter->getAliases();
			$aliases = count( $aliases ) > 0 ? implode( ', ', $aliases ) : '-';
		}


		$description = $parameter->getMessage();
		if ( $description === false ) {
			$description = $parameter->getDescription();
			if ( $description === false ) $description = '-';
		}
		else {
			$description = $this->msg( $description );
		}

		$type = $parameter->getTypeMessage();

		$default = $parameter->isRequired() ? "''" . $this->msg( 'validator-describe-required' ) . "''" : $parameter->getDefault();
		if ( is_array( $default ) ) {
			$default = implode( ', ', $default );
		}
		else if ( is_bool( $default ) ) {
			$default = $default ? 'yes' : 'no';
		}
		
		if ( $default === '' ) $default = "''" . $this->msg( 'validator-describe-empty' ) . "''";

		return "| {$parameter->getName()}\n"
. ( $hasAliases ? '| ' . $aliases . "\n" : '' ) .
<<<EOT
| {$type}
| {$default}
| {$description}
EOT;
	}
	
	protected function getFormatParameters( $format ) {
		if ( array_key_exists( $format, $GLOBALS['smwgResultFormats'] ) ) {
			return SMWQueryProcessor::getResultPrinter( $format )->getValidatorParameters();
		}
		else {
			return array();
		}
	}
	
	/**
	 * @see ParserHook::getMessage()
	 * 
	 * @since 1.6.1
	 */
	public function getMessage() {
		return 'smw-smwdoc-description';
	}
	
	/**
	 * Message function that takes into account the language parameter.
	 * 
	 * @since 1.6.1
	 * 
	 * @param string $key
	 * @param array $args
	 * 
	 * @return string
	 */
	protected function msg( $key ) {
		$args = func_get_args();
		$key = array_shift( $args );
		return wfMsgReal( $key, $args, true, $this->language );
	}
	
}