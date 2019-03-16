<?php

namespace SMW\Tests\Factbox;

use ParserOutput;
use ReflectionClass;
use SMW\ApplicationFactory;
use SMW\Factbox\Factbox;
use SMW\Factbox\CheckMagicWords;
use SMW\ParserData;
use SMW\Tests\TestEnvironment;
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

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testMagicWordsFromParserOutputExtension( $text, array $expected ) {

		$title  = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$this->testEnvironment->withConfiguration(
			[
				'smwgNamespacesWithSemanticLinks' => [ $title->getNamespace() => true ],
				'smwgParserFeatures' => SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR
			]
		);

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

		$this->testEnvironment->withConfiguration( [
			'smwgShowFactboxEdit' => SMW_FACTBOX_HIDDEN,
			'smwgShowFactbox'     => SMW_FACTBOX_HIDDEN
			]
		);

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

		$checkMagicWords = new CheckMagicWords(
			[
				'preview' => isset( $expected['preview'] ) && $expected['preview'],
				'showFactboxEdit' => SMW_FACTBOX_HIDDEN,
				'showFactbox' => SMW_FACTBOX_HIDDEN
			]
		);

		$instance = new Factbox(
			$store,
			new ParserData( $title, $parserOutput ),
			$messageBuilder
		);

		$instance->setCheckMagicWords(
			$checkMagicWords
		);

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

		$provider = [];

		// #0 __NOFACTBOX__, this test should not generate a factbox output
		$provider[] = [
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __NOFACTBOX__ ',
			[
				'magicWords' => [ 'SMW_NOFACTBOX' ],
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			]
		];

		// #1 __SHOWFACTBOX__, this test should generate a factbox output
		$provider[] = [
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __SHOWFACTBOX__',
			[
				'magicWords' => [ 'SMW_SHOWFACTBOX' ],
				'constants'  => SMW_FACTBOX_NONEMPTY,
				'textOutput' => 'smwfactboxhead' // lazy check because we use assertContains
			]
		];

		// #2 empty
		$provider[] = [
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			[
				'magicWords' => null,
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			]
		];

		// #3 empty + preview option
		$provider[] = [
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			[
				'magicWords' => null,
				'preview'    => true,
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			]
		];

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
