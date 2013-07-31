<?php

namespace SMW\Test;

use SMW\ApiRequestParameterFormatter;
use SMW\ArrayAccessor;

use SMWQueryResult;

/**
 * Tests for the ApiRequestParameterFormatter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ApiRequestParameterFormatter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ApiRequestParameterFormatterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiRequestParameterFormatter';
	}

	/**
	 * Helper method that returns a SMWPrintRequest object
	 *
	 * @since 1.9
	 *
	 * @param string $printout
	 *
	 * @return SMWPrintRequest
	 */
	private function newPrintRequest( $printout ) {
		return new \SMWPrintRequest(
			\SMWPrintRequest::PRINT_PROP,
			$printout,
			\SMWPropertyValue::makeUserProperty( $printout )
		);
	}

	/**
	 * Helper method that returns a ApiRequestParameterFormatter object
	 *
	 * @since 1.9
	 *
	 * @param array $parameters
	 *
	 * @return ApiRequestParameterFormatter
	 */
	private function getInstance( array $parameters ) {
		return new ApiRequestParameterFormatter( $parameters );
	}

	/**
	 * @test ApiRequestParameterFormatter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance( array() ) );
	}

	/**
	 * @test ApiRequestParameterFormatter::getAskArgsApiParameters
	 *
	 * @since 1.9
	 */
	public function testGetAskArgsApiParametersEmpty() {

		$result = $this->getInstance( array() )->getAskArgsApiParameters();

		$this->assertInstanceOf( '\SMW\ArrayAccessor', $result );
		$this->assertEmpty( $result->get( 'conditions' ) );
		$this->assertEmpty( $result->get( 'parameters' ) );
		$this->assertEmpty( $result->get( 'printouts' ) );
	}

	/**
	 * @test ApiRequestParameterFormatter::getAskArgsApiParameters
	 * @dataProvider requestArgsApiParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $type
	 * @param $expected
	 */
	public function testGetAskArgsApiParameters( $test, $type, $expected ) {

		$result = $this->getInstance( $test )->getAskArgsApiParameters();

		$this->assertInstanceOf( '\SMW\ArrayAccessor', $result );
		$this->assertEquals( $expected, $result->get( $type ) );
	}

	/**
	 * @test ApiRequestParameterFormatter::getAskApiParameters
	 * @dataProvider requestAskApiParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testGetAskApiParameters( $test, $expected ) {

		$result = $this->getInstance( $test )->getAskApiParameters();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * AskArgsApi data provider
	 *
	 * @return array
	 */
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

	/**
	 * AskApi data provider
	 *
	 * @return array
	 */
	public function requestAskApiParametersDataProvider() {
		return array(
			array( array(),  array() ),
			array( array( 'query' => '[[Modification date::+]]' ),  array( '[[Modification date::+]]' ) ),
			array( array( 'query' => '[[Modification date::+]]|?Modification date' ),  array( '[[Modification date::+]]', '?Modification date' ) ),
			array( array( 'query' => '[[Modification date::+]]|?Modification date|sort=desc' ),  array( '[[Modification date::+]]', '?Modification date', 'sort=desc' ) ),
		);
	}
}
