<?php

namespace SMW;

use ParamProcessor\ParamDefinition;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookDefinition;
use ParserHooks\HookHandler;
use SMWQueryProcessor;

/**
 * Class that provides the {{#smwdoc}} parser function, which displays parameter
 * documentation for a specified result format.
 *
 * @ingroup ParserFunction
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DocumentationParserFunction implements HookHandler {

	/**
	 * Field to store the value of the language parameter.
	 *
	 * @since 1.6.1
	 *
	 * @var string
	 */
	private $language;

	/**
	 * @param Parser $parser
	 * @param ProcessingResult $result
	 *
	 * @return mixed
	 */
	public function handle( Parser $parser, ProcessingResult $result ) {
		$parameters = $result->getParameters();

		$this->language = $parameters['language']->getValue();

		$params = $this->getFormatParameters( $parameters['format']->getValue() );

		if ( $parameters['parameters']->getValue() === 'specific' ) {
			foreach ( array_keys( SMWQueryProcessor::getParameters() ) as $name ) {
				unset( $params[$name] );
			}
		}
		elseif ( $parameters['parameters']->getValue() === 'base' ) {
			foreach ( array_diff_key( $params, SMWQueryProcessor::getParameters() ) as $param ) {
				unset( $params[$param->getName()] );
			}
		}

		return $this->getParameterTable( $params );
	}

	/**
	 * Returns the wikitext for a table listing the provided parameters.
	 *
	 * @since 1.6
	 *
	 * @param ParamDefinition[] $paramDefinitions
	 *
	 * @return string
	 */
	private function getParameterTable( array $paramDefinitions ) {
		$tableRows = array();
		$hasAliases = false;

		foreach ( $paramDefinitions as $parameter ) {
			$hasAliases = count( $parameter->getAliases() ) != 0;
			if ( $hasAliases ) {
				break;
			}
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
	 * @param ParamDefinition $parameter
	 * @param boolean $hasAliases
	 *
	 * @return string
	 */
	private function getDescriptionRow( ParamDefinition $parameter, $hasAliases ) {
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

		if ( $default === '' ) {
			$default = "''" . $this->msg( 'validator-describe-empty' ) . "''";
		}

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
	private function getFormatParameters( $format ) {
		if ( !array_key_exists( $format, $GLOBALS['smwgResultFormats'] ) ) {
			return array();
		}

		return ParamDefinition::getCleanDefinitions(
			SMWQueryProcessor::getResultPrinter( $format )->getParamDefinitions( SMWQueryProcessor::getParameters() )
		);
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
	private function msg( $key ) {
		$args = func_get_args();
		$key = array_shift( $args );
		return wfMessage( $key )->params( $args )->useDatabase( true )->inLanguage( $this->language )->text();
	}

	public static function getHookDefinition() {
		return new HookDefinition(
			'smwdoc',
			array(
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
			),
			array( 'format', 'language', 'parameters' )
		);
	}

}
