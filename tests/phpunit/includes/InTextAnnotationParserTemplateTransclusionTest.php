<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\InTextAnnotationParser;
use SMW\MediaWiki\MagicWordFinder;
use SMW\MediaWiki\RedirectTargetFinder;

use SMW\Settings;
use SMW\ParserData;
use SMW\Application;

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
class InTextAnnotationParserTemplateTransclusionTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $application;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = new SemanticDataValidator();
		$this->application = Application::getInstance();
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
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

		$outputText   = $this->runTemplateTransclusion( $title, $text, $tmplValue );

		$this->application->registerObject(
			'Settings',
			Settings::newFromArray( $settings )
		);

		$parserData = new ParserData( $title, $parserOutput );

		$instance = new InTextAnnotationParser(
			$parserData,
			new MagicWordFinder(),
			new RedirectTargetFinder()
		);

		$instance->parse( $outputText );

		$this->assertContains(
			$expected['resultText'],
			$outputText
		);

		$parserData = new ParserData( $title, $parserOutput );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$parserData->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
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
