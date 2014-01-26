<?php

namespace SMW\Test;

use SMW\ContentProcessor;
use SMW\ExtensionContext;

use Title;
use ParserOutput;

/**
 * @covers \SMW\ContentProcessor
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ContentProcessorTemplateTransclusionTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ContentProcessor';
	}

	/**
	 * @since  1.9
	 *
	 * @return ContentProcessor
	 */
	private function newInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $this->newSettings( $settings ) );

		$parserData = $this->newParserData( $title, $parserOutput );

		return new ContentProcessor( $parserData, $context );
	}

	/**
	 * Helper method for processing a template transclusion by simulating template
	 * expensions using a callback to avoid having to integrate DB read/write
	 * process in order to access a Template
	 *
	 * @note Part of the routine has been taken from MW's ExtraParserTest
	 *
	 * @since 1.9
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
	 * @dataProvider templateDataProvider
	 *
	 * @since 1.9
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

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getData()
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
				'resultText'     => '[[:?bar|?bar]]',
				'propertyCount'  => 1,
				'propertyLabels' => array( 'Foo' ),
				'propertyValues' => array( '?bar' )
			)
		);

		return $provider;
	}

}
