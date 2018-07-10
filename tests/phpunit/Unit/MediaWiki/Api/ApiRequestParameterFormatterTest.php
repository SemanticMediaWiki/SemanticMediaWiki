<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\ApiRequestParameterFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\ApiRequestParameterFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApiRequestParameterFormatterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	public function setUp() {
		$this->testEnvironment = new TestEnvironment();
	}

	public function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Api\ApiRequestParameterFormatter',
			new ApiRequestParameterFormatter( [] )
		);
	}

	public function testGetAskArgsApiForEmptyParameter() {

		$nstance = new ApiRequestParameterFormatter( [] );

		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'conditions' ) );
		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'parameters' ) );
		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'printouts' ) );
	}

	/**
	 * @dataProvider requestArgsApiParametersDataProvider
	 */
	public function testGetAskArgsApiParameter( $parameters, $type, $expected ) {

		$nstance = new ApiRequestParameterFormatter( $parameters );

		$this->assertEquals(
			$expected,
			$nstance->getAskArgsApiParameter( $type )
		);
	}

	/**
	 * @dataProvider requestAskApiParametersDataProvider
	 */
	public function testGetAskApiParameters( $parameters, $expected ) {

		$instance = new ApiRequestParameterFormatter( $parameters );
		$result = $instance->getAskApiParameters();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );
	}

	public function requestArgsApiParametersDataProvider() {
		return [
			[ [ 'conditions' => [ 'Lala' ] ],         'conditions', '[[Lala]]' ],
			[ [ 'conditions' => [ 'Lala', 'Lima' ] ], 'conditions', '[[Lala]] [[Lima]]' ],
			[ [ 'parameters' => [ 'Lila' ] ],         'parameters', [] ],
			[ [ 'parameters' => [ 'Lila=isFunny' ] ], 'parameters', [ 'Lila' => 'isFunny' ] ],
			[ [ 'parameters' => [ 'Lila=isFunny', 'Lula=isHappy' ] ], 'parameters', [ 'Lila' => 'isFunny', 'Lula' => 'isHappy' ] ],
		//	array( array( 'printouts'  => array( '?Linda' ) ),         'printouts', array( $this->newPrintRequest( '?Linda' ) ) ),
		//	array( array( 'printouts'  => array( '?Luna', '?Mars' ) ), 'printouts', array( $this->newPrintRequest( '?Luna' ), $this->newPrintRequest( '?Mars' ) ) ),
		];
	}

	public function requestAskApiParametersDataProvider() {
		return [
			[ [],  [] ],
			[ [ 'query' => '[[Modification date::+]]' ],  [ '[[Modification date::+]]' ] ],
			[ [ 'query' => '[[Modification date::+]]|?Modification date' ],  [ '[[Modification date::+]]', '?Modification date' ] ],
			[ [ 'query' => '[[Modification date::+]]|?Modification date|sort=desc' ],  [ '[[Modification date::+]]', '?Modification date', 'sort=desc' ] ],
		];
	}

	private function newPrintRequest( $printout ) {
		return new \SMW\Query\PrintRequest(
			\SMW\Query\PrintRequest::PRINT_PROP,
			$printout,
			\SMW\DataValueFactory::getInstance()->newPropertyValueByLabel( $printout )
		);
	}

}
