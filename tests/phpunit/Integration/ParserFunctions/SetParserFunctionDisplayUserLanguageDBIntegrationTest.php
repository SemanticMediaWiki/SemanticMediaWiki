<?php

namespace SMW\Tests\Integration\ParserFunctions;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use SMW\Formatters\MessageFormatter;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\ParameterProcessorFactory;
use SMW\ParserFunctions\SetParserFunction;
use SMW\Services\ServicesFactory;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Guards that a displayed `#set` value fragments the parser cache by the
 * viewer's interface language exactly like the equivalent inline annotation,
 * so a page rendered in one language is not served from cache to viewers of
 * another. Requires a real store because the user-language flag is only set
 * while a typed value (e.g. a unit-converting quantity) is being rendered.
 *
 * @covers \SMW\ParserFunctions\SetParserFunction
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SetParserFunctionDisplayUserLanguageDBIntegrationTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, SMW_NS_PROPERTY => true ],
			'smwgSetParserCacheKeys' => [ 'userlang' ],
		] );
	}

	public function testDisplayingAUnitConvertingQuantityMarksTheParserCacheAsUserLanguageDependent() {
		$this->testEnvironment->getUtilityFactory()->newPageCreator()
			->createPage( Title::newFromText( 'Has area', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Quantity]] [[Corresponds to::1 km²]] [[Corresponds to::1000 m²]]' );

		$parserOutput = $this->displayValue( 'Has area=1000 m²' );

		$this->assertContains(
			'userlang',
			$parserOutput->getUsedOptions()
		);
	}

	public function testDisplayingAPlainPageValueDoesNotMarkTheParserCacheAsUserLanguageDependent() {
		$parserOutput = $this->displayValue( 'Has somewhere=Target page' );

		$this->assertNotContains(
			'userlang',
			$parserOutput->getUsedOptions()
		);
	}

	private function displayValue( string $assignment ): ParserOutput {
		$parserOutput = new ParserOutput();

		$parserData = ServicesFactory::getInstance()->newParserData(
			Title::newFromText( __CLASS__ ),
			$parserOutput
		);

		$setParserFunction = new SetParserFunction(
			$parserData,
			new MessageFormatter( MediaWikiServices::getInstance()->getContentLanguage() ),
			new WikitextTemplateRenderer()
		);

		$setParserFunction->parse(
			ParameterProcessorFactory::newFromArray( [ $assignment, '+display=link' ], true )
		);

		return $parserOutput;
	}

}
