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
			new ApiRequestParameterFormatter( array() )
		);
	}

	public function testGetAskArgsApiForEmptyParameter() {

		$nstance = new ApiRequestParameterFormatter( array() );

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
		return array(
			array( array( 'conditions' => array( 'Lala' ) ),         'conditions', '[[Lala]]' ),
			array( array( 'conditions' => array( 'Lala', 'Lima' ) ), 'conditions', '[[Lala]] [[Lima]]' ),
			array( array( 'parameters' => array( 'Lila' ) ),         'parameters', array() ),
			array( array( 'parameters' => array( 'Lila=isFunny' ) ), 'parameters', array( 'Lila' => 'isFunny' ) ),
			array( array( 'parameters' => array( 'Lila=isFunny', 'Lula=isHappy' ) ), 'parameters', array( 'Lila' => 'isFunny', 'Lula' => 'isHappy' ) ),
			array( array( 'printouts'  => array( '?Linda' ) ),         'printouts', array( $this->newPrintRequest( '?Linda' ) ) ),
			array( array( 'printouts'  => array( '?Luna', '?Mars' ) ), 'printouts', array( $this->newPrintRequest( '?Luna' ), $this->newPrintRequest( '?Mars' ) ) ),
		);
	}

	public function requestAskApiParametersDataProvider() {
		return array(
			array( array(),  array() ),
			array( array( 'query' => '[[Modification date::+]]' ),  array( '[[Modification date::+]]' ) ),
			array( array( 'query' => '[[Modification date::+]]|?Modification date' ),  array( '[[Modification date::+]]', '?Modification date' ) ),
			array( array( 'query' => '[[Modification date::+]]|?Modification date|sort=desc' ),  array( '[[Modification date::+]]', '?Modification date', 'sort=desc' ) ),
		);
	}

	private function newPrintRequest( $printout ) {
		return new \SMW\Query\PrintRequest(
			\SMW\Query\PrintRequest::PRINT_PROP,
			$printout,
			\SMW\DataValueFactory::getInstance()->newPropertyValueByLabel( $printout )
		);
	}

}
