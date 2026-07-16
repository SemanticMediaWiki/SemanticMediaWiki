<?php

namespace SMW\Tests\Unit\ParserFunctions;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Parser\PropertyLinkRenderer;
use SMW\ParserData;
use SMW\ParserFunctions\PropertyLinkParserFunction;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\ParserFunctions\PropertyLinkParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PropertyLinkParserFunctionTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyLinkRenderer = $this->getMockBuilder( PropertyLinkRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertyLinkParserFunction::class,
			new PropertyLinkParserFunction( $parserData, $propertyLinkRenderer )
		);
	}

	public function testRendersPropertyLinkForFirstParameter() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foo]]' ),
			$this->parse( [ 'Foo' ] )
		);
	}

	public function testUsesSecondParameterAsCaption() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foobar]]' ),
			$this->parse( [ 'Foo', 'Foobar' ] )
		);
	}

	public function testIgnoresAdditionalParameters() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foobar]]' ),
			$this->parse( [ 'Foo', 'Foobar', 'Baz' ] )
		);
	}

	public function testHashCaptionSuppressesLink() {
		$this->assertSame(
			'<span class="smw-property nolink">Foo</span>',
			$this->parse( [ 'Foo', '#' ] )
		);
	}

	public function testStripsLeadingParserObjectFromParameters() {
		$parserData = $this->newParserData( NS_MAIN );

		$instance = $this->newInstance( $parserData );

		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foo]]' ),
			$instance->parse( [ $this->newParser(), 'Foo' ] )
		);
	}

	public function testInvalidPropertyRecordsUserLanguageParserCacheKey() {
		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgSetParserCacheKeys' => [ 'userlang' ]
		] );

		$parserOutput = new ParserOutput();
		$parserData = new ParserData( $this->newTitle( NS_MAIN ), $parserOutput );

		$this->newInstance( $parserData )->parse( [ 'Fo%o' ] );

		$this->assertContains(
			'userlang',
			$parserOutput->getUsedOptions()
		);
	}

	public function testInvalidPropertyOnNonSemanticNamespaceDoesNotRecordParserCacheKey() {
		$this->testEnvironment->withConfiguration( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => false ],
			'smwgSetParserCacheKeys' => [ 'userlang' ]
		] );

		$parserOutput = new ParserOutput();
		$parserData = new ParserData( $this->newTitle( NS_MAIN ), $parserOutput );

		$this->newInstance( $parserData )->parse( [ 'Fo%o' ] );

		$this->assertNotContains(
			'userlang',
			$parserOutput->getUsedOptions()
		);
	}

	private function parse( array $rawParams ): string {
		return $this->newInstance( $this->newParserData( NS_MAIN ) )->parse( $rawParams );
	}

	private function newInstance( ParserData $parserData ): PropertyLinkParserFunction {
		return new PropertyLinkParserFunction(
			$parserData,
			new PropertyLinkRenderer( $parserData )
		);
	}

	private function newParserData( int $namespace ): ParserData {
		return new ParserData( $this->newTitle( $namespace ), new ParserOutput() );
	}

	private function newTitle( int $namespace ): Title {
		return MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__, $namespace );
	}

	private function newParser(): Parser {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$parser->method( 'getTitle' )
			->willReturn( $this->newTitle( NS_MAIN ) );

		$parser->method( 'getOutput' )
			->willReturn( new ParserOutput() );

		return $parser;
	}

	private function propertyLink( string $link ): string {
		return $this->testEnvironment->replaceNamespaceWithLocalizedText(
			SMW_NS_PROPERTY,
			'<span class="smw-property">' . $link . '</span>'
		);
	}

}
