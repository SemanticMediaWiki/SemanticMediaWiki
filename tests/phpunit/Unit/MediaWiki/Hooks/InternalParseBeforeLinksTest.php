<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\InternalParseBeforeLinks
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinksTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $parserFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->parserFactory = $this->testEnvironment->getUtilityFactory()->newParserFactory();

		$this->stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\InternalParseBeforeLinks',
			new InternalParseBeforeLinks( $parser, $this->stripState )
		);
	}

	public function testNonProcessForEmptyText() {

		$text = '';

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->once() )
			->method( 'getOptions' );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$this->assertTrue(
			$instance->process( $text )
		);
	}

	public function testDisableProcessOfInterfaceMessageOnNonSpecialPage() {

		$text = 'Foo';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( false ) );

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->once() )
			->method( 'getInterfaceMessage' )
			->will( $this->returnValue( true ) );

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $parserOptions ) );

		$parser->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$this->assertTrue(
			$instance->process( $text )
		);
	}

	public function testProcessOfInterfaceMessageOnEnabledSpecialPage() {

		$text = 'Foo';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'isSpecial' )
			->with( $this->equalTo( 'Bar' ) )
			->will( $this->returnValue( true ) );

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->once() )
			->method( 'getInterfaceMessage' )
			->will( $this->returnValue( true ) );

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $parserOptions ) );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$instance->setOptions(
			[
				'smwgEnabledSpecialPage' => [ 'Bar' ]
			]
		);

		$instance->process( $text );
	}

	public function testProcessOfInterfaceMessageOnSpecialPageWithOnOffMarker() {

		$text = '[[SMW::off]]Foo[[SMW::on]]';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'isSpecial' )
			->with( $this->equalTo( 'Bar' ) )
			->will( $this->returnValue( true ) );

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$instance->process( $text );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testProcess( $title ) {

		$text   = 'Foo';
		$parser = $this->parserFactory->newFromTitle( $title );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$this->assertTrue(
			$instance->process( $text )
		);
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextChangeWithParserOuputUpdateIntegration( $parameters, $expected ) {

		$this->testEnvironment->withConfiguration(
			$parameters['settings']
		);

		$text   = $parameters['text'];
		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$this->stripState
		);

		$smwgEnabledSpecialPage = isset( $parameters['settings']['smwgEnabledSpecialPage'] ) ? $parameters['settings']['smwgEnabledSpecialPage'] : [];

		$instance->setOptions(
			[
				'smwgEnabledSpecialPage' => $smwgEnabledSpecialPage
			]
		);

		$this->assertTrue(
			$instance->process( $text )
		);

		$this->assertEquals(
			$expected['resultText'],
			$text
		);

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$this->assertEquals(
			$expected['propertyCount'] > 0,
			$parserData->hasSemanticData( $parser->getOutput() )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function titleProvider() {

		#0
		$provider[] = [ Title::newFromText( __METHOD__ ) ];

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		#1
		$provider[] = [ $title ];

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );


		#2
		$provider[] = [ $title ];

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		#3
		$provider[] = [ $title ];

		return $provider;
	}

	public function textDataProvider() {

		$provider = [];

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$provider[] = [
			[
				'title'    => $title,
				'settings' => [
					'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
					'smwgParserFeatures' => SMW_PARSER_STRICT
				],
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				],
				[
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount'  => 3,
					'propertyLabels' => [ 'Foo', 'Bar', 'FooBar' ],
					'propertyValues' => [ 'Dictumst', 'Tincidunt semper', '9001' ]
				]
		];

		// #1
		$provider[] = [
			[
				'title'    => $title,
				'settings' => [
					'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
					'smwgParserFeatures' => SMW_PARSER_STRICT
				],
				'text'  => '#REDIRECT [[Foo]]',
				],
				[
					'resultText' => '#REDIRECT [[Foo]]',
					'propertyCount'  => 1,
					'propertyKeys'   => [ '_REDI' ],
					'propertyValues' => [ 'Foo' ]
				]
		];

		// #2 NS_SPECIAL, processed but no annotations
		$title = Title::newFromText( 'Ask', NS_SPECIAL );

		$provider[] = [
			[
				'title'    => $title,
				'settings' => [
					'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
					'smwgParserFeatures' => SMW_PARSER_STRICT,
					'smwgEnabledSpecialPage' => [ 'Ask', 'Foo' ]
				],
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				],
				[
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount' => 0
				]
		];

		// #3 NS_SPECIAL, not processed, Title::isSpecial returns false
		$title = Title::newFromText( 'Foo', NS_SPECIAL );

		$provider[] = [
			[
				'title'    => $title,
				'settings' => [
					'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
					'smwgParserFeatures' => SMW_PARSER_STRICT,
					'smwgEnabledSpecialPage' => [ 'Ask', 'Foo' ]
				],
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				],
				[
					'resultText' => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
						' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[foo::9001]] et Donec.',
					'propertyCount' => 0
				]
		];

		// #4 NS_SPECIAL, not processed, invalid smwgEnabledSpecialPage setting
		$title = Title::newFromText( 'Foobar', NS_SPECIAL );

		$provider[] = [
			[
				'title'    => $title,
				'settings' => [
					'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
					'smwgParserFeatures' => SMW_PARSER_STRICT,
					'smwgEnabledSpecialPage' => []
				],
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				],
				[
					'resultText' => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
						' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[foo::9001]] et Donec.',
					'propertyCount' => 0
				]
		];

		return $provider;
	}

}
