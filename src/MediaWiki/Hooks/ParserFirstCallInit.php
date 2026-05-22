<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use ParamProcessor\Processor;
use SMW\ParserFunctionFactory;
use SMW\ParserFunctions\DocumentationParserFunction;
use SMW\ParserFunctions\InfoParserFunction;
use SMW\ParserFunctions\SectionTag;
use SMW\Settings;

/**
 * Hook: ParserFirstCallInit registers SMW parser functions and tag hooks.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ParserFirstCallInit implements ParserFirstCallInitHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ParserFunctionFactory $parserFunctionFactory,
		private readonly Settings $settings,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onParserFirstCallInit( $parser ) {
		$this->parserFunctionFactory->registerFunctionHandlers( $parser );

		[ $name, $definition, $flag ] = $this->parserFunctionFactory->getInfoParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		$parser->setHook( 'info', static function ( $input, array $attribs, Parser $parser, PPFrame $frame ) {
			$defaultParams = InfoParserFunction::getDefaultParams();
			$defaultParam = array_shift( $defaultParams );

			if ( $defaultParam !== null && $input !== null ) {
				$attribs[$defaultParam] = $input;
			}

			$processor = Processor::newDefault();
			$processor->setParameters(
				$attribs,
				InfoParserFunction::getParamDefinitions()
			);

			$result = $processor->processParameters();

			$handler = new InfoParserFunction();
			$resultText = $handler->handle( $parser, $result );

			return $parser->recursiveTagParse( $resultText, $frame );
		} );

		[ $name, $definition, $flag ] = $this->parserFunctionFactory->getDocumentationParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		$parser->setHook( 'smwdoc', static function ( $input, array $attribs, Parser $parser, PPFrame $frame ) {
			$defaultParams = DocumentationParserFunction::getDefaultParams();
			$defaultParam = array_shift( $defaultParams );

			if ( $defaultParam !== null && $input !== null ) {
				$attribs[$defaultParam] = $input;
			}

			$processor = Processor::newDefault();
			$processor->setParameters(
				$attribs,
				DocumentationParserFunction::getParamDefinitions()
			);

			$result = $processor->processParameters();

			$handler = new DocumentationParserFunction();
			$resultText = $handler->handle( $parser, $result );

			return $parser->recursiveTagParse( $resultText, $frame );
		} );

		// Support for <section> ... </section>
		SectionTag::register(
			$parser,
			$this->settings->get( 'smwgSupportSectionTag' )
		);

		return true;
	}

}
