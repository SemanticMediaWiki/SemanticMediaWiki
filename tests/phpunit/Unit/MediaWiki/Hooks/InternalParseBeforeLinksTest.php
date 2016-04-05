<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\Settings;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\UtilityFactory;
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
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->parserFactory = UtilityFactory::getInstance()->newParserFactory();
		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$text = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\InternalParseBeforeLinks',
			new InternalParseBeforeLinks( $parser, $text )
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
			$text
		);

		$this->assertTrue( $instance->process() );
	}

	public function testNonProcessForInterfaceMessage() {

		$text = 'Foo';

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

		$parser->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$text
		);

		$this->assertTrue( $instance->process() );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testProcess( $title ) {

		$text   = 'Foo';
		$parser = $this->parserFactory->newFromTitle( $title );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$text
		);

		$this->assertTrue( $instance->process() );
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextChangeWithParserOuputUpdateIntegration( $parameters, $expected ) {

		$text   = $parameters['text'];
		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$text
		);

		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $parameters['settings'] )
		);

		$this->assertTrue( $instance->process() );
		$this->assertEquals( $expected['resultText'], $text );

		$parserData = $this->applicationFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$this->assertEquals(
			$expected['propertyCount'] > 0,
			$parser->getOutput()->getProperty( 'smw-semanticdata-status' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function titleProvider() {

		#2
		$provider[] = array( Title::newFromText( __METHOD__ ) );

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		#1
		$provider[] = array( $title );

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecial' )
			->will( $this->returnValue( true ) );

		#2
		$provider[] = array( $title );

		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecial' )
			->will( $this->returnValue( false ) );

		#3
		$provider[] = array( $title );

		return $provider;
	}

	public function textDataProvider() {

		$provider = array();

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$provider[] = array(
			array(
				'title'    => $title,
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgEnabledInTextAnnotationParserStrictMode' => true,
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount'  => 3,
					'propertyLabels' => array( 'Foo', 'Bar', 'FooBar' ),
					'propertyValues' => array( 'Dictumst', 'Tincidunt semper', '9001' )
				)
		);

		$provider[] = array(
			array(
				'title'    => $title,
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgEnabledInTextAnnotationParserStrictMode' => true,
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
				),
				'text'  => '#REDIRECT [[Foo]]',
				),
				array(
					'resultText' => '#REDIRECT [[Foo]]',
					'propertyCount'  => 1,
					'propertyKeys'   => array( '_REDI' ),
					'propertyValues' => array( 'Foo' )
				)
		);

		// #1 NS_SPECIAL, processed but no annotations
		$title = Title::newFromText( 'Ask', NS_SPECIAL );

		$provider[] = array(
			array(
				'title'    => $title,
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgEnabledInTextAnnotationParserStrictMode' => true,
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
					'smwgEnabledSpecialPage' => array( 'Ask', 'Foo' )
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount' => 0
				)
		);

		// #2 NS_SPECIAL, not processed
		$title = Title::newFromText( 'Foo', NS_SPECIAL );

		$provider[] = array(
			array(
				'title'    => $title,
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgEnabledInTextAnnotationParserStrictMode' => true,
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
					'smwgEnabledSpecialPage' => array( 'Ask', 'Foo' )
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
						' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[foo::9001]] et Donec.',
					'propertyCount' => 0
				)
		);

		return $provider;
	}

}
