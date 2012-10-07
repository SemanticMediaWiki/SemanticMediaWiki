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
	 * @param $type
	 *
	 * @return array
	 */
	protected function getParameterInfo( $type ) {
		return array(
			array(
				'name' => 'format',
				'message' => 'smw-smwdoc-par-format',
				'values' => array_keys( $GLOBALS['smwgResultFormats'] ),
			),
			array(
				'name' => 'language',
				'message' => 'smw-smwdoc-par-language',
				'default' => $GLOBALS['wgLanguageCode'],
			),
			array(
				'name' => 'parameters',
				'message' => 'smw-smwdoc-par-parameters',
				'values' => array( 'all', 'specific', 'base' ),
				'default' => 'specific',
			),
		);
	}

	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 *
	 * @since 1.6
	 *
	 * @param $type
	 *
	 * @return array
	 */
	protected function getDefaultParameters( $type ) {
		return array( 'format', 'language', 'parameters' );
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

		if ( $parameters['parameters'] === 'specific' ) {
			foreach ( array_keys( SMWQueryProcessor::getParameters() ) as $name ) {
				unset( $params[$name] );
			}
		}
		elseif ( $parameters['parameters'] === 'base' ) {
			foreach ( array_diff_key( $params, SMWQueryProcessor::getParameters() ) as $param ) {
				unset( $params[$param->getName()] );
			}
		}

		return $this->parseWikitext( $this->getParameterTable( $params ) );
	}

	/**
	 * Returns the wikitext for a table listing the provided parameters.
	 *
	 * @since 1.6
	 *
	 * @param $paramDefinitions array of IParamDefinition
	 *
	 * @return string
	 */
	protected function getParameterTable( array $paramDefinitions ) {
		$tableRows = array();
		$hasAliases = false;

		foreach ( $paramDefinitions as $parameter ) {
			$hasAliases = count( $parameter->getAliases() ) != 0;
			if ( $hasAliases ) break;
		}

		foreach ( $paramDefinitions as $parameter ) {
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
	 * @param IParamDefinition $parameter
	 * @param boolean $hasAliases
	 *
	 * @return string
	 */
	protected function getDescriptionRow( IParamDefinition $parameter, $hasAliases ) {
		if ( $hasAliases ) {
			$aliases = $parameter->getAliases();
			$aliases = count( $aliases ) > 0 ? implode( ', ', $aliases ) : '-';
		}

		$description = $this->msg( $parameter->getMessage() );

		$type = $this->msg( $parameter->getTypeMessage() );

		$default = $parameter->isRequired() ? "''" . $this->msg( 'validator-describe-required' ) . "''" : $parameter->getDefault();
		if ( is_array( $default ) ) {
			$default = implode( ', ', $default );
		}
		elseif ( is_bool( $default ) ) {
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

	/**
	 * @param string $format
	 *
	 * @return array of IParamDefinition
	 */
	protected function getFormatParameters( $format ) {
		if ( array_key_exists( $format, $GLOBALS['smwgResultFormats'] ) ) {
			return ParamDefinition::getCleanDefinitions(
				SMWQueryProcessor::getResultPrinter( $format )->getParamDefinitions( SMWQueryProcessor::getParameters() )
			);
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
		return wfMessage( $key )->params( $args )->useDatabase( true )->inLanguage( $this->language )->text();
	}
}
