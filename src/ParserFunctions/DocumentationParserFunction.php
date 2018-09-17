<?php

namespace SMW\ParserFunctions;

use ParamProcessor\ParamDefinition;
use ParamProcessor\ProcessedParam;
use ParamProcessor\ProcessingError;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookDefinition;
use ParserHooks\HookHandler;
use SMW\ParameterListDocBuilder;
use SMWQueryProcessor as QueryProcessor;

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
	private $language = 'en';

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
		$format = $parameters['format']->getValue();

		$formatParameters = QueryProcessor::getFormatParameters(
			$format
		);

		$this->language = $parameters['language']->getValue();

		if ( $formatParameters === [] ) {
			return $this->msg( 'smw-smwdoc-default-no-parameter-list', $format );
		}

		return $this->buildParameterListDocumentation( $parameters, $formatParameters );
	}

	/**
	 * @return HookDefinition
	 */
	public static function getHookDefinition() {
		return new HookDefinition(
			'smwdoc',
			[
				[
					'name' => 'format',
					'message' => 'smw-smwdoc-par-format',
					'values' => array_keys( $GLOBALS['smwgResultFormats'] ),
				],
				[
					'name' => 'language',
					'message' => 'smw-smwdoc-par-language',
					'default' => $GLOBALS['wgLanguageCode'],
				],
				[
					'name' => 'parameters',
					'message' => 'smw-smwdoc-par-parameters',
					'values' => [ 'all', 'specific', 'base' ],
					'default' => 'specific',
				],
			],
			[ 'format', 'language', 'parameters' ]
		);
	}

	/**
	 * @param ProcessedParam[] $parameters
	 *
	 * @return string
	 */
	private function buildParameterListDocumentation( array $parameters, $formatParameters ) {

		if ( $parameters['parameters']->getValue() === 'specific' ) {
			foreach ( array_keys( QueryProcessor::getParameters() ) as $name ) {
				unset( $formatParameters[$name] );
			}
		} elseif ( $parameters['parameters']->getValue() === 'base' ) {
			foreach ( array_diff_key( $formatParameters, QueryProcessor::getParameters() ) as $param ) {
				unset( $formatParameters[$param->getName()] );
			}
		}

		$docBuilder = new ParameterListDocBuilder(
			[ $this, 'msg' ]
		);

		if ( ( $parameterTable = $docBuilder->getParameterTable( $formatParameters ) ) !== ''  ) {
			return $parameterTable;
		}

		return $this->msg( 'smw-smwdoc-default-no-parameter-list', $parameters['format']->getValue() );
	}

	/**
	 * @since 3.0
	 *
	 * @param ...$args
	 *
	 * @return string
	 */
	public function msg( ...$args ) {
		return wfMessage( array_shift( $args ) )->params( $args )->useDatabase( true )->inLanguage( $this->language )->text();
	}

	/**
	 * @param ProcessingError[] $errors
	 * @return string
	 */
	private function getOutputForErrors( $errors ) {
		// TODO: see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1485
		return 'A fatal error occurred in the #smwdoc parser function';
	}

}
