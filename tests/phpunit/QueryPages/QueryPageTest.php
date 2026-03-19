<?php

namespace SMW\Tests\QueryPages;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\QueryPages\QueryPage;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * @covers \SMW\QueryPages\QueryPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class QueryPageTest extends TestCase {

	/**
	 * Helper method that returns a QueryPage object
	 *
	 * @since 1.9
	 *
	 * @param string $search
	 *
	 * @return QueryPage
	 */
	private function newInstance( $search = '' ) {
		$queryPage = $this->getMockBuilder( QueryPage::class )
			->setMethods( [ 'getResults', 'formatResult' ] )
			->getMock();

		$context = $this->newContext( [ 'property' => $search ] );
		$context->setTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );

		$queryPage->setContext( $context );

		return $queryPage;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( QueryPage::class, $this->newInstance() );
	}

	/**
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

		$this->assertIsArray( $result );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @since 1.9
	 */
	public function testGetSearchForm() {
		$search = __METHOD__;
		$instance = $this->newInstance();

		$reflector = new ReflectionClass( QueryPage::class );
		$selectOptions = $reflector->getProperty( 'selectOptions' );
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

		$this->assertIsString( $result );

		// https://github.com/sebastianbergmann/phpunit/issues/1380
		// $this->assertTag( $matcher, $result );
		$this->assertStringContainsString( $search, $result );
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function linkParametersDataProvider() {
		$param = __METHOD__;

		return [
			[ '', [] ],
			[ null, [] ],
			[ $param, [ 'property' => $param ] ],
			[ "[{$param}]", [ 'property' => "[{$param}]" ] ],
			[ "[&{$param}...]", [ 'property' => "[&{$param}...]" ] ]
		];
	}

	private function newContext( $request = [] ) {
		$context = new RequestContext();

		if ( $request instanceof WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new FauxRequest( $request, true ) );
		}

		$context->setUser( new MockSuperUser() );

		return $context;
	}

}
