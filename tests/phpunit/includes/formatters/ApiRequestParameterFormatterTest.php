<?php

namespace SMW\Test;

use SMW\ApiRequestParameterFormatter;

use SMWQueryResult;

/**
 * @covers \SMW\ApiRequestParameterFormatter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApiRequestParameterFormatterTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiRequestParameterFormatter';
	}

	/**
	 * @since 1.9
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
	 * @since 1.9
	 *
	 * @return ApiRequestParameterFormatter
	 */
	private function newInstance( array $parameters ) {
		return new ApiRequestParameterFormatter( $parameters );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance( array() ) );
	}

	/**
	 * @since 1.9
	 */
	public function testGetAskArgsApiParameterEmpty() {

		$nstance = $this->newInstance( array() );

		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'conditions' ) );
		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'parameters' ) );
		$this->assertEmpty( $nstance->getAskArgsApiParameter( 'printouts' ) );
	}

	/**
	 * @dataProvider requestArgsApiParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetAskArgsApiParameter( $test, $type, $expected ) {

		$nstance = $this->newInstance( $test );

		$this->assertEquals( $expected, $nstance->getAskArgsApiParameter( $type ) );
	}

	/**
	 * @dataProvider requestAskApiParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetAskApiParameters( $test, $expected ) {

		$result = $this->newInstance( $test )->getAskApiParameters();

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
