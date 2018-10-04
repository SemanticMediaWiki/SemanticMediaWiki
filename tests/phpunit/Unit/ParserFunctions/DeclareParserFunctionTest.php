<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\ParserFunctions\DeclareParserFunction;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\ParserFunctions\DeclareParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class DeclareParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\DeclareParserFunction',
			new DeclareParserFunction( $parserData )
		);
	}

	/**
	 * @dataProvider argumentProvider
	 */
	public function testParse( $args, $expected ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$ppframe = $this->getMockBuilder( '\PPFrame' )
			->disableOriginalConstructor()
			->getMock();

		$ppframe->expects( $this->any() )
			->method( 'isTemplate' )
			->will( $this->returnValue( true ) );

		$ppframe->expects( $this->any() )
			->method( 'expand' )
			->will( $this->returnArgument( 0 ) );

		$ppframe->expects( $this->any() )
			->method( 'getArgument' )
			->will( $this->returnArgument( 0 ) );

		$instance = new DeclareParserFunction( $parserData );

		$this->assertInternalType(
			'string',
			$instance->parse( $ppframe, $args )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function argumentProvider() {

		$provider[] = [
			[ 'Has foo=Bar' ],
			[
				'propertyLabel'  => [ 'Has foo' ],
				'propertyValues' => [ 'Bar' ]
			]
		];

		return $provider;
	}

}
