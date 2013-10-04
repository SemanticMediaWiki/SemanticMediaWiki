<?php

namespace SMW\Test;

use SMW\ParserTextProcessor;

use Title;
use ParserOutput;

/**
 * Tests for the ParserTextProcessor class
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\ParserTextProcessor
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserTextProcessorTemplateTransclusionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserTextProcessor';
	}

	/**
	 * Helper method that returns a ParserTextProcessor object
	 *
	 * @param $title
	 * @param $parserOutput
	 * @param $settings
	 *
	 * @return ParserTextProcessor
	 */
	private function newInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {
		return new ParserTextProcessor(
			$this->newParserData( $title, $parserOutput ),
			$this->newSettings( $settings )
		);
	}

	/**
	 * Helper method for processing a template transclusion by simulating template
	 * expensions using a callback to avoid having to integrate DB read/write
	 * process in order to access a Template
	 *
	 * @note Part of the routine has been taken from MW's ExtraParserTest
	 *
	 * @param $title
	 * @param $text
	 * @param $return
	 *
	 * @return text
	 */
	private function runTemplateTransclusion( Title $title, $text, $return ) {

		$parser  = new \Parser;
		$options = new \ParserOptions;
		$options->setTemplateCallback( function ( $title, $parser = false ) use ( $return ) {

			$text = $return;
			$deps = array();

			return array(
				'text' => $text,
				'finalTitle' => $title,
				'deps' => $deps
			);

		} );

		return $parser->preprocess( $text, $title, $options );
	}

	/**
	 * @test ParserTextProcessor::parse
	 * @dataProvider templateDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 * @param array $settings
	 * @param $text
	 * @param $tmplValue
	 * @param array $expected
	 */
	public function testPreprocessTemplateAndParse( $namespace, array $settings, $text, $tmplValue, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle( $namespace );
		$instance     = $this->newInstance( $title, $parserOutput, $settings );
		$outputText   = $this->runTemplateTransclusion( $title, $text, $tmplValue );

		$instance->parse( $outputText );

		$this->assertContains(
			$expected['resultText'],
			$outputText,
			'Asserts that the text compares to the expected output'
		);

		$parserData = $this->newParserData( $title, $parserOutput );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$parserData->getData(),
			'Asserts getData() returning instance'
		);

		$this->assertSemanticData(
			$parserData->getData(),
			$expected,
			'Asserts the SemanticData container'
		);
	}

	/**
	 * @return array
	 */
	public function templateDataProvider() {

		$provider = array();

		// #0 Bug 54967
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'[[Foo::{{Bam}}]]',
			'?bar',
			array(
				'resultText'    => '[[:?bar|?bar]]',
				'propertyCount' => 1,
				'propertyLabel' => array( 'Foo' ),
				'propertyValue' => array( '?bar' )
			)
		);

		return $provider;
	}

}
