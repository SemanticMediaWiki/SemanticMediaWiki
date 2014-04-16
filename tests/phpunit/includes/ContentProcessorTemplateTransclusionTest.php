<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\ContentProcessor;
use SMW\ExtensionContext;
use SMW\Settings;
use SMW\ParserData;

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
class ContentProcessorTemplateTransclusionTest extends \PHPUnit_Framework_TestCase {

	private function acquireInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {

		$context = new ExtensionContext();
		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Settings', Settings::newFromArray( $settings ) );

		$parserData = new ParserData( $title, $parserOutput );

		return new ContentProcessor( $parserData, $context );
	}

	/**
	 * Helper method for processing a template transclusion by simulating template
	 * expensions using a callback to avoid having to integrate DB read/write
	 * process in order to access a Template
	 *
	 * @note Part of the routine has been taken from MW's ExtraParserTest
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
	 */
	public function testPreprocessTemplateAndParse( $namespace, array $settings, $text, $tmplValue, array $expected ) {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__, $namespace );
		$instance     = $this->acquireInstance( $title, $parserOutput, $settings );
		$outputText   = $this->runTemplateTransclusion( $title, $text, $tmplValue );

		$instance->parse( $outputText );

		$this->assertContains(
			$expected['resultText'],
			$outputText,
			'Asserts that the text compares to the expected output'
		);

		$parserData = new ParserData( $title, $parserOutput );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$parserData->getSemanticData()
		);

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

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
