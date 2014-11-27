<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;

use SMW\DeclareParserFunction;
use SMW\ApplicationFactory;

use Title;
use ParserOutput;

/**
 * @covers \SMW\DeclareParserFunction
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class DeclareParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DeclareParserFunction',
			new DeclareParserFunction( $parserData )
		);
	}

	/**
	 * @dataProvider argumentProvider
	 */
	public function testParse( $args, $expected ) {

		$parserData = $this->applicationFactory->newParserData(
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

		$provider[] = array(
			array( 'Has foo=Bar' ),
			array(
				'propertyLabel'  => array( 'Has foo' ),
				'propertyValues' => array( 'Bar' )
			)
		);

		return $provider;
	}

}
