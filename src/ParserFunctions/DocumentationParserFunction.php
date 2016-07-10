<?php

namespace SMW\ParserFunctions;

use ParamProcessor\ParamDefinition;
use ParamProcessor\ProcessingError;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookDefinition;
use ParserHooks\HookHandler;
use SMW\ParameterListDocBuilder;
use SMWQueryProcessor;

/**
 * Class that provides the {{#smwdoc}} parser function, which displays parameter
 * documentation for a specified result format.
 *
 * @ingroup ParserFunction
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DocumentationParserFunction implements HookHandler {

	/**
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
		if ( $result->hasFatal() ) {
			return $this->getOutputForErrors( $result->getErrors() );
		}

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

		$docBuilder = new ParameterListDocBuilder( $this->newMessageFunction() );

		return $docBuilder->getParameterTable( $params );
	}

	private function newMessageFunction() {
		$language = $this->language;

		return function() use ( $language ) {
			$args = func_get_args();
			$key = array_shift( $args );
			return wfMessage( $key )->params( $args )->useDatabase( true )->inLanguage( $language )->text();
		};
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
	 * @param ProcessingError[] $errors
	 * @return string
	 */
	private function getOutputForErrors( $errors ) {
		// TODO: see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1485
		return 'A fatal error occurred in the #smwdoc parser function';
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
