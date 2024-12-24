<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\ParserFunctions\DeclareParserFunction;
use SMW\Tests\TestEnvironment;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\ParserFunctions\DeclareParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class DeclareParserFunctionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
			->willReturn( true );

		$ppframe->expects( $this->any() )
			->method( 'expand' )
			->willReturnArgument( 0 );

		$ppframe->expects( $this->any() )
			->method( 'getArgument' )
			->willReturnArgument( 0 );

		$instance = new DeclareParserFunction( $parserData );

		$this->assertIsString(

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
