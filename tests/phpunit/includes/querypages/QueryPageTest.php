<?php

namespace SMW\Test;

use ReflectionClass;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;

/**
 * @covers \SMW\QueryPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class QueryPageTest extends \PHPUnit_Framework_TestCase {

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

		$queryPage = $this->getMockBuilder( '\SMW\QueryPage' )
			->setMethods( [ 'getResults', 'formatResult' ] )
			->getMock();

		$context = $this->newContext( [ 'property' => $search ] );
		$context->setTitle( Title::newFromText( __METHOD__ ) );

		$queryPage->setContext( $context );

		return $queryPage;
	}

	/**
	 * @test QueryPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( '\SMW\QueryPage', $this->newInstance() );
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

		$search = __METHOD__;
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

		$search = __METHOD__;
		$instance = $this->newInstance();

		$reflector = new ReflectionClass( '\SMW\QueryPage' );
		$selectOptions = $reflector->getProperty( 'selectOptions' );
		$selectOptions->setAccessible( true );
		$selectOptions->setValue( $instance, [
			'offset' => 1,
			'limit'  => 2,
			'end'    => 5,
			'count'  => 4
		] );

		$result = $instance->getSearchForm( $search );

		$matcher = [
			'tag' => 'form',
			'descendant' => [
				'tag' => 'input',
				'attributes' => [ 'name' => 'property', 'value' => $search ]
			]
		];

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
		$param = __METHOD__;

		return [
			[ ''      , [] ],
			[ null    , [] ],
			[ $param , [ 'property' => $param ] ],
			[ "[{$param}]" , [ 'property' => "[{$param}]" ] ],
			[ "[&{$param}...]" , [ 'property' => "[&{$param}...]" ] ]
		];
	}

	private function newContext( $request = [] ) {

		$context = new \RequestContext();

		if ( $request instanceof \WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new \FauxRequest( $request, true ) );
		}

		$context->setUser( new MockSuperUser() );

		return $context;
	}

}
