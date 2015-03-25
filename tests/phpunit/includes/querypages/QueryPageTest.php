<?php

namespace SMW\Test;

/**
 * Tests for the QueryPage class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\QueryPage
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class QueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\QueryPage';
	}

	/**
	 * Helper method that returns a QueryPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return QueryPage
	 */
	private function newInstance( $search = '' ) {

		$queryPage = $this->getMockBuilder( $this->getClass() )
			->setMethods( array( 'getResults', 'formatResult' ) )
			->getMock();

		$context = $this->newContext( array( 'property' => $search ) );
		$context->setTitle( $this->newTitle() );

		$queryPage->setContext( $context );

		return $queryPage;
	}

	/**
	 * @test QueryPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test QueryPage::linkParameters
	 * @dataProvider linkParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testLinkParameters( $test, $expected ) {

		$search = $this->newRandomString();
		$result = $this->newInstance( $test )->linkParameters();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

	}

	/**
	 * @test QueryPage::getSearchForm
	 *
	 * @since 1.9
	 */
	public function testGetSearchForm() {

		$search   = $this->newRandomString();
		$instance = $this->newInstance();

		$reflector = $this->newReflector();
		$selectOptions = $reflector->getProperty( 'selectOptions' );
		$selectOptions->setAccessible( true );
		$selectOptions->setValue( $instance, array(
			'offset' => 1,
			'limit'  => 2,
			'end'    => 5,
			'count'  => 4
		) );

		$result = $instance->getSearchForm( $search );

		$matcher = array(
			'tag' => 'form',
			'descendant' => array(
				'tag' => 'input',
				'attributes' => array( 'name' => 'property', 'value' => $search )
			)
		);

		$this->assertInternalType( 'string', $result );

		// https://github.com/sebastianbergmann/phpunit/issues/1380
		// $this->assertTag( $matcher, $result );
		$this->assertContains( $search, $result );
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function linkParametersDataProvider() {
		$random = $this->newRandomString();

		return array(
			array( ''      , array() ),
			array( null    , array() ),
			array( $random , array( 'property' => $random ) ),
			array( "[{$random}]" , array( 'property' => "[{$random}]" ) ),
			array( "[&{$random}...]" , array( 'property' => "[&{$random}...]" ) )
		);
	}
}
