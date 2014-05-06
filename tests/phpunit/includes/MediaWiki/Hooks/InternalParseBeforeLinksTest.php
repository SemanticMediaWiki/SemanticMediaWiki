<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ParserFactory;
use SMW\Tests\Util\Mock\MockObjectBuilder;
use SMW\Tests\Util\Mock\MediaWikiMockObjectRepository;

use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\ExtensionContext;

use SMW\DIC\ObjectFactory;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\InternalParseBeforeLinks
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinksTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		parent::setUp();
		ObjectFactory::getInstance()->invokeContext( new ExtensionContext() );
	}

	protected function tearDown() {
		ObjectFactory::clear();
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

	/**
	 * @dataProvider titleProvider
	 */
	public function testProcess( $title ) {

		$text   = '';
		$parser = ParserFactory::newFromTitle( $title );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$text
		);

		$this->assertTrue( $instance->process() );
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testSemanticDataParserOuputUpdateIntegration( $parameters, $expected ) {

		$text   = $parameters['text'];
		$parser = ParserFactory::newFromTitle( $parameters['title'] );

		$instance = new InternalParseBeforeLinks(
			$parser,
			$text
		);

		ObjectFactory::getInstance()->registerObject(
			'Settings',
			Settings::newFromArray( $parameters['settings'] )
		);

		$this->assertTrue( $instance->process() );
		$this->assertEquals( $expected['resultText'], $text );

		$parserData = ObjectFactory::getInstance()->newByParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$this->assertEquals(
			$expected['propertyCount'] > 0,
			$parser->getOutput()->getProperty( 'smw-semanticdata-status' )
		);

		$semanticDataValidator = new SemanticDataValidator();

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	/**
	 * @return array
	 */
	public function titleProvider() {

		$mockObjectBuilder = new MockObjectBuilder();
		$mockObjectBuilder->registerRepository( new MediaWikiMockObjectRepository() );

		$provider = array();

		$provider[] = array( Title::newFromText( __METHOD__ ) );

		$title = $mockObjectBuilder->newObject( 'Title', array(
			'isSpecialPage' => true,
		) );

		$provider[] = array( $title );

		$title = $mockObjectBuilder->newObject( 'Title', array(
			'isSpecialPage' => true,
			'isSpecial'     => true,
		) );

		$provider[] = array( $title );

		$title = $mockObjectBuilder->newObject( 'Title', array(
			'isSpecialPage' => true,
			'isSpecial'     => false,
		) );

		$provider[] = array( $title );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$provider[] = array(
			array(
				'title'    => $title,
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
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
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
				),
				'text'  => '#REDIRECT [[Foo]]',
				),
				array(
					'resultText' => '#REDIRECT [[Foo]]',
					'propertyCount'  => 1,
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
