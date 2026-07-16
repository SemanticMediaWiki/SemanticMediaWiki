<?php

namespace SMW\Tests\Unit\ParserFunctions;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DataModel\SemanticData;
use SMW\Formatters\MessageFormatter;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\MediaWiki\StripMarkerDecoder;
use SMW\ParameterProcessorFactory;
use SMW\ParserData;
use SMW\ParserFunctions\SetParserFunction;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\ParserFunctions\SetParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SetParserFunctionTest extends TestCase {

	private $testEnvironment;
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$templateRenderer = $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SetParserFunction::class,
			new SetParserFunction( $parserData, $messageFormatter, $templateRenderer )
		);
	}

	/**
	 * @dataProvider setParserProvider
	 */
	public function testParse( array $params ) {
		$parserData = ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->willReturnSelf();

		$messageFormatter->expects( $this->once() )
			->method( 'getHtml' )
			->willReturn( 'Foo' );

		$templateRenderer = $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);

		$this->assertIsArray(

			$instance->parse( ParameterProcessorFactory::newFromArray( $params ) )
		);
	}

	/**
	 * @dataProvider setParserProvider
	 */
	public function testInstantiatedPropertyValues( array $params, array $expected ) {
		$parserData = ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->willReturnSelf();

		$templateRenderer = $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);

		$instance->parse( ParameterProcessorFactory::newFromArray( $params ) );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testTemplateSupport() {
		$params = [ 'Foo=bar', 'Foo=foobar', 'BarFoo=9001', 'template=FooTemplate' ];

		$expected = [
			'errors' => 0,
			'propertyCount'  => 2,
			'propertyLabels' => [ 'Foo', 'BarFoo' ],
			'propertyValues' => [ 'Bar', '9001', 'Foobar' ]
		];

		$parserData = ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->willReturnSelf();

		$templateRenderer = new WikitextTemplateRenderer();

		$instance = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);

		$instance->parse(
			ParameterProcessorFactory::newFromArray( $params )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testParseWithoutTemplateReturnsNoParseTripleWithMessageHtml() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( 'HTML-OUTPUT' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar' ] )
		);

		$this->assertSame(
			[ 0 => 'HTML-OUTPUT', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	public function testParseWithTemplateReturnsParseableResult() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', 'template=FooTemplate' ] )
		);

		$this->assertFalse(
			$result['noparse']
		);
	}

	public function testDisplayLinkModeRendersLinkedValue() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display=link' ], true )
		);

		$this->assertSame(
			[ 0 => '[[:Bar|bar]]', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	public function testDisplayTextModeRendersUnlinkedValue() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display=text' ], true )
		);

		$this->assertSame(
			[ 0 => 'bar', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	/**
	 * `noparse` must not depend on whether a value is displayed. Returning
	 * `noparse => false` makes the Parser preprocess the result in a child
	 * frame built from the `#set` call's own arguments, which expands any
	 * `{{{n}}}` left in a stored value against those arguments (#7040).
	 * Link markup renders either way, so the flag can stay at its legacy value.
	 */
	public function testDisplayDoesNotFlipNoParse() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display' ], true )
		);

		// A value has to reach the output for the flag to be under test at all
		$this->assertNotSame(
			'',
			$result[0]
		);
		$this->assertTrue(
			$result['noparse']
		);
	}

	public function testBareDisplayDefaultsToLinkMode() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display' ], true )
		);

		$this->assertSame(
			'[[:Bar|bar]]',
			$result[0]
		);
	}

	public function testDisplayJoinsMultipleValuesInInputOrder() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=a;b', '+sep=;', '+display=text' ], true )
		);

		$this->assertSame(
			'a, b',
			$result[0]
		);
	}

	public function testDisplayRendersOnlyTheFlaggedAssignment() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=a', 'Bar=b', '+display=text' ], true )
		);

		$this->assertSame(
			'b',
			$result[0]
		);
	}

	/**
	 * The option is scoped to the property of the assignment it follows, not to
	 * that assignment alone, because the parameter processor merges repeated
	 * assignments of one property into a single value list before `#set` sees
	 * them. Pinned so the scope is a documented choice rather than an accident.
	 */
	public function testDisplayAppliesToEveryValueOfARepeatedProperty() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=a', 'Foo=b', '+display=text' ], true )
		);

		$this->assertSame(
			'a, b',
			$result[0]
		);
	}

	public function testLastDisplayModeWinsForARepeatedProperty() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray(
				[ 'Foo=a', '+display=link', 'Foo=b', '+display=text' ],
				true
			)
		);

		$this->assertSame(
			'a, b',
			$result[0]
		);
	}

	public function testDisplayModeIsCaseInsensitive() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display=TEXT' ], true )
		);

		$this->assertSame(
			'bar',
			$result[0]
		);
	}

	/**
	 * `@json` assignments are expanded into their property names only after the
	 * display option has been keyed to the literal `@json` parameter, so the
	 * option matches no property and is silently ignored.
	 */
	public function testDisplayIsIgnoredForJsonAssignments() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ '@json={"Foo":"bar"}', '+display' ], true )
		);

		$this->assertSame(
			'',
			$result[0]
		);
	}

	public function testInvalidValueIsNotDisplayed() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );

		// '+bad' contains a character from the invalid property character list,
		// producing an invalid DataValue that must never render
		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ '+bad=x', '+display' ], true )
		);

		$this->assertSame(
			[ 0 => '', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	public function testUnknownDisplayModeAddsWarningAndDisplaysNothing() {
		$messageFormatter = $this->newMessageFormatterExpectingKey(
			'smw-parser-function-set-display-invalid-mode',
			'foo'
		);

		$instance = new SetParserFunction(
			$this->newParserData(),
			$messageFormatter,
			$this->newTemplateRendererMock()
		);

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display=foo' ], true )
		);

		$this->assertSame(
			[ 0 => '', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	public function testDisplayCombinedWithTemplateAddsConflictErrorAndKeepsTemplateOutput() {
		$messageFormatter = $this->newMessageFormatterExpectingKey(
			'smw-parser-function-set-display-template-conflict'
		);

		$instance = new SetParserFunction(
			$this->newParserData(),
			$messageFormatter,
			new WikitextTemplateRenderer()
		);

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display', 'template=FooTemplate' ], true )
		);

		$this->assertFalse(
			$result['noparse']
		);
		$this->assertStringContainsString(
			'FooTemplate',
			$result[0]
		);
		$this->assertStringNotContainsString(
			'[[:Bar',
			$result[0]
		);
	}

	public function testStripMarkerValueDisplaysTheRawOriginal() {
		$stripMarkerDecoder = $this->getMockBuilder( StripMarkerDecoder::class )
			->disableOriginalConstructor()
			->getMock();

		$stripMarkerDecoder->method( 'decode' )->willReturn( 'DECODED' );

		$instance = $this->newSetParserFunctionWithHtmlOutput( '' );
		$instance->setStripMarkerDecoder( $stripMarkerDecoder );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=RAW', '+display' ], true )
		);

		$this->assertSame(
			'RAW',
			$result[0]
		);
		// `noparse` only controls whether the Parser preprocesses the result in
		// a child frame, so it has no bearing on how a strip marker in the
		// output is handled; the legacy value stands here as it does for every
		// other displayed value (#7040)
		$this->assertTrue(
			$result['noparse']
		);
	}

	public function testInvalidDisplayedValueMarksVariesByUserLanguage() {
		$parserData = $this->newParserDataMockExpectingVariesByUserLanguage( $this->once() );

		$instance = new SetParserFunction(
			$parserData,
			$this->newMessageFormatterMock(),
			$this->newTemplateRendererMock()
		);

		$instance->parse(
			ParameterProcessorFactory::newFromArray( [ '+bad=x', '+display' ], true )
		);
	}

	public function testValidDisplayedValueWithoutUserLanguageOutputDoesNotMarkVariesByUserLanguage() {
		$parserData = $this->newParserDataMockExpectingVariesByUserLanguage( $this->never() );

		$instance = new SetParserFunction(
			$parserData,
			$this->newMessageFormatterMock(),
			$this->newTemplateRendererMock()
		);

		$instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display' ], true )
		);
	}

	public function testDisplayedFileValueRegistersFileUsage() {
		$parserOutput = new ParserOutput();

		$instance = $this->newSetParserFunctionForParserOutput( $parserOutput );

		$instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=File:Example.png', '+display' ], true )
		);

		$this->assertArrayHasKey(
			'Example.png',
			$parserOutput->getImages()
		);
	}

	public function testDisplayedFileValueInTextModeDoesNotRegisterFileUsage() {
		$parserOutput = new ParserOutput();

		$instance = $this->newSetParserFunctionForParserOutput( $parserOutput );

		$instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=File:Example.png', '+display=text' ], true )
		);

		$this->assertSame(
			[],
			$parserOutput->getImages()
		);
	}

	public function testErrorHtmlColonsAreEncodedWhenDisplayValuesAreShown() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( 'Warn:ing' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar', '+display=text' ], true )
		);

		$this->assertSame(
			'barWarn&#58;ing',
			$result[0]
		);
	}

	public function testErrorHtmlIsNotEncodedWithoutDisplayOutput() {
		$instance = $this->newSetParserFunctionWithHtmlOutput( 'Warn:ing' );

		$result = $instance->parse(
			ParameterProcessorFactory::newFromArray( [ 'Foo=bar' ], true )
		);

		$this->assertSame(
			[ 0 => 'Warn:ing', 'noparse' => true, 'isHTML' => false ],
			$result
		);
	}

	private function newSetParserFunctionWithHtmlOutput( string $html ): SetParserFunction {
		$parserData = ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->method( 'addFromArray' )->willReturnSelf();
		$messageFormatter->method( 'getHtml' )->willReturn( $html );

		$templateRenderer = $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		return new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);
	}

	private function newSetParserFunctionForParserOutput( ParserOutput $parserOutput ): SetParserFunction {
		$parserData = ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__ ),
			$parserOutput
		);

		return new SetParserFunction(
			$parserData,
			$this->newMessageFormatterMock(),
			$this->newTemplateRendererMock()
		);
	}

	private function newParserData(): ParserData {
		return ApplicationFactory::getInstance()->newParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__ ),
			new ParserOutput()
		);
	}

	private function newParserDataMockExpectingVariesByUserLanguage( InvocationOrder $expected ): ParserData {
		$semanticData = new SemanticData(
			DIWikiPage::newFromText( __CLASS__ )
		);

		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$parserData->method( 'getSemanticData' )->willReturn( $semanticData );
		$parserData->method( 'canUse' )->willReturn( false );

		$parserData->expects( $expected )
			->method( 'markVariesByUserLanguage' );

		return $parserData;
	}

	private function newMessageFormatterExpectingKey( string $key, string ...$params ): MessageFormatter {
		$messageFormatter = $this->newMessageFormatterMock();

		$messageFormatter->expects( $this->once() )
			->method( 'addFromKey' )
			->with( $key, ...$params )
			->willReturnSelf();

		return $messageFormatter;
	}

	private function newMessageFormatterMock(): MessageFormatter {
		$messageFormatter = $this->getMockBuilder( MessageFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->method( 'addFromArray' )->willReturnSelf();
		$messageFormatter->method( 'getHtml' )->willReturn( '' );

		return $messageFormatter;
	}

	private function newTemplateRendererMock(): WikitextTemplateRenderer {
		return $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function setParserProvider() {
		// #0 Single data set
		// {{#set:
		// |Foo=bar
		// }}
		$provider[] = [
			[ 'Foo=bar' ],
			[
				'errors' => 0,
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			]
		];

		// #1 Empty data set
		// {{#set:
		// |Foo=
		// }}
		$provider[] = [
			[ 'Foo=' ],
			[
				'errors' => 0,
				'propertyCount'  => 0,
				'propertyLabels' => '',
				'propertyValues' => ''
			]
		];

		// #2 Multiple data set
		// {{#set:
		// |BarFoo=9001
		// |Foo=bar
		// }}
		$provider[] = [
			[ 'Foo=bar', 'BarFoo=9001' ],
			[
				'errors' => 0,
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Foo', 'BarFoo' ],
				'propertyValues' => [ 'Bar', '9001' ]
			]
		];

		// #3 Multiple data set with an error record
		// {{#set:
		// |_Foo=9001 --> will raise an error
		// |Foo=bar
		// }}
		$provider[] = [
			[ 'Foo=bar', '_Foo=9001' ],
			[
				'errors' => 1,
				'propertyCount'  => 2,
				'strictPropertyValueMatch' => false,
				'propertyKeys' => [ 'Foo', '_ERRC' ],
				'propertyValues' => [ 'Bar' ]
			]
		];

		return $provider;
	}

}
