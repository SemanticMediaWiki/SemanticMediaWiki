<?php

namespace SMW\Tests\Factbox;

use SMW\ParserData;
use SMW\Settings;
use SMW\Factbox\Factbox;
use SMW\ApplicationFactory;

use ReflectionClass;
use ParserOutput;
use Title;

/**
 * @covers \SMW\Factbox\Factbox
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxMagicWordsTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testMagicWordsFromParserOutputExtension( $text, array $expected ) {

		$title  = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$settings = Settings::newFromArray( array(
			'smwgNamespacesWithSemanticLinks' => array( $title->getNamespace() => true ),
			'smwgEnabledInTextAnnotationParserStrictMode' => true,
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
			)
		);

		$this->applicationFactory->registerObject( 'Settings', $settings );

		$parserData = new ParserData( $title, $parserOutput );

		$inTextAnnotationParser = ApplicationFactory::getInstance()->newInTextAnnotationParser( $parserData );
		$inTextAnnotationParser->parse( $text );

		$this->assertEquals(
			$expected['magicWords'],
			$this->getMagicwords( $parserOutput )
		);
	}

	/**
	 * @dataProvider textDataProvider
	 */
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function testGetMagicWords( $text, array $expected ) { // @codingStandardsIgnoreEnd

		$title = Title::newFromText( __METHOD__ );

		$settings = Settings::newFromArray( array(
			'smwgShowFactboxEdit' => SMW_FACTBOX_HIDDEN,
			'smwgShowFactbox'     => SMW_FACTBOX_HIDDEN
			)
		);

		$this->applicationFactory->registerObject( 'Settings', $settings );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->any() )
			->method( 'getExtensionData' )
			->will( $this->returnValue( $expected['magicWords'] ) );

		// MW 1.19, 1.20
		$parserOutput->mSMWMagicWords = $expected['magicWords'];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Factbox(
			$store,
			new ParserData( $title, $parserOutput ),
			$messageBuilder
		);

		if ( isset( $expected['preview'] ) && $expected['preview'] ) {
			$instance->useInPreview( true );
		}

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );

		$magic = $reflector->getMethod( 'getMagicWords' );
		$magic->setAccessible( true );

		$result = $magic->invoke( $instance );

		$this->assertInternalType( 'integer', $result );
		$this->assertEquals( $expected['constants'], $result );
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 __NOFACTBOX__, this test should not generate a factbox output
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __NOFACTBOX__ ',
			array(
				'magicWords' => array( 'SMW_NOFACTBOX' ),
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		// #1 __SHOWFACTBOX__, this test should generate a factbox output
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __SHOWFACTBOX__',
			array(
				'magicWords' => array( 'SMW_SHOWFACTBOX' ),
				'constants'  => SMW_FACTBOX_NONEMPTY,
				'textOutput' => 'smwfactboxhead' // lazy check because we use assertContains
			)
		);

		// #2 empty
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			array(
				'magicWords' => array(),
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		// #3 empty + preview option
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			array(
				'magicWords' => array(),
				'preview'    => true,
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	protected function getMagicwords( $parserOutput ) {

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $parserOutput->getExtensionData( 'smwmagicwords' );
		}

		return $parserOutput->mSMWMagicWords;
	}

}
