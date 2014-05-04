<?php

namespace SMW\Tests;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\DIC\ObjectFactory;

use SMW\InTextAnnotationParser;
use SMW\ExtensionContext;
use SMW\Settings;
use SMW\ParserData;

use Title;
use ParserOutput;

/**
 * @covers \SMW\InTextAnnotationParser
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InTextAnnotationParserForTemplateTransclusionTest extends \PHPUnit_Framework_TestCase {

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

		ObjectFactory::getInstance()->invokeContext( new ExtensionContext() );

		ObjectFactory::getInstance()->registerObject(
			'Settings',
			Settings::newFromArray( $settings )
		);

		$parserData = new ParserData( $title, $parserOutput );

		$instance   = ObjectFactory::getInstance()->newInTextAnnotationParser( $parserData );
		$outputText = $this->runTemplateTransclusion( $title, $text, $tmplValue );

		$instance->parse( $outputText );

		$this->assertContains(
			$expected['resultText'],
			$outputText
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
