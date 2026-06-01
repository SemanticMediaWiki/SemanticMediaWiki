<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataModel\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class InTextAnnotationParserTemplateTransclusionTest extends TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
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
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$user = RequestContext::getMain()->getUser();
		$options = new ParserOptions( $user );
		$options->setTemplateCallback( static function ( $title, $parser = false ) use ( $return ) {
			$text = $return;
			$deps = [];

			return [
				'text' => $text,
				'finalTitle' => $title,
				'deps' => $deps
			];
		} );

		return $parser->preprocess( $text, $title, $options );
	}

	/**
	 * @dataProvider templateDataProvider
	 */
	public function testPreprocessTemplateAndParse( $namespace, array $settings, $text, $tmplValue, array $expected ) {
		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__, $namespace );

		$outputText = $this->runTemplateTransclusion( $title, $text, $tmplValue );

		$this->testEnvironment->withConfiguration( $settings );

		$parserData = $this->applicationFactory->newParserData(
			$title,
			$parserOutput
		);

		$instance = $this->applicationFactory->newInTextAnnotationParser(
			$parserData
		);

		$instance->parse( $outputText );

		$this->assertStringContainsString(
			$expected['resultText'],
			$outputText
		);

		$parserData = $this->applicationFactory->newParserData(
			$title,
			$parserOutput
		);

		$this->assertInstanceOf(
			SemanticData::class,
			$parserData->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testPropertyLinkWithTooltipVariesByUserLanguage() {
		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgParserFeatures' => [ 'inline-errors' ],
			'smwgMainCacheType' => 'hash'
		] );

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$parserData = $this->applicationFactory->newParserData(
			$title,
			new ParserOutput()
		);

		$instance = $this->applicationFactory->newInTextAnnotationParser(
			$parserData
		);

		// The `@@@` syntax renders a property link. `Modification date` is a
		// predefined property whose link carries a tooltip with a localized
		// title and description, so the rendered output (returned directly by
		// makePropertyLink) varies by the viewer's interface language.
		$text = 'Foo [[Modification date::@@@]] baz';
		$instance->parse( $text );

		$this->assertTrue(
			$parserData->variesByUserLanguage(),
			'A `@@@` property link that renders a localized tooltip must record userlang'
		);
	}

	public function testPlainPropertyLinkWithoutTooltipDoesNotVaryByUserLanguage() {
		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgParserFeatures' => [ 'inline-errors' ],
			'smwgMainCacheType' => 'hash'
		] );

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$parserData = $this->applicationFactory->newParserData(
			$title,
			new ParserOutput()
		);

		$instance = $this->applicationFactory->newInTextAnnotationParser(
			$parserData
		);

		// A user-defined property without a description carries no tooltip, so
		// the property link is content-stable across languages.
		$text = 'Foo [[Has unknown property::@@@]] baz';
		$instance->parse( $text );

		$this->assertFalse(
			$parserData->variesByUserLanguage(),
			'A `@@@` property link without a tooltip must not record userlang'
		);
	}

	public function testPropertyLinkWithAnnotatedLanguageDoesNotVaryByUserLanguage() {
		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgParserFeatures' => [ 'inline-errors' ],
			'smwgMainCacheType' => 'hash'
		] );

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$parserData = $this->applicationFactory->newParserData(
			$title,
			new ParserOutput()
		);

		$instance = $this->applicationFactory->newInTextAnnotationParser(
			$parserData
		);

		// `@@@en` pins the tooltip to a fixed language, so the rendered output
		// is content-stable across languages and must not record userlang.
		$text = 'Foo [[Modification date::@@@en]] baz';
		$instance->parse( $text );

		$this->assertFalse(
			$parserData->variesByUserLanguage(),
			'A `@@@<lang>` property link with an annotated language must not record userlang'
		);
	}

	public function templateDataProvider() {
		$provider = [];

		// #0 Bug 54967
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors' ],
				'smwgMainCacheType'      => 'hash'
			],
			'[[Foo::{{Bam}}]]',
			'?bar',
			[
				'resultText'     => '[[:?bar|?bar]]',
				'propertyCount'  => 1,
				'propertyLabels' => [ 'Foo' ],
				'propertyValues' => [ '?bar' ]
			]
		];

		return $provider;
	}

}
