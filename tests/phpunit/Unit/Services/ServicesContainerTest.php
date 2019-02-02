<?php

namespace SMW\Tests\Services;

use SMW\Services\ServicesContainer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Services\ServicesContainer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesContainerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ServicesContainer::class,
			new ServicesContainer()
		);
	}

	public function testGet() {

		$mock = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runService' ] )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'runService' );

		$instance = new ServicesContainer(
			[
				'test' => [ $this, 'fakeService' ]
			]
		);

		$instance->get( 'test', $mock );
	}

	public function testGetTypedService() {

		$instance = new ServicesContainer(
			[
				'test' => [
					'_service' => [ $this, 'stdClassService' ],
					'_type' => '\stdClass'
				]
			]
		);

		$this->assertInstanceOf(
			'\stdClass',
			$instance->get( 'test' )
		);
	}

	public function testGet_MultipleArgs() {

		$fake = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runService' ] )
			->getMock();

		$fake->expects( $this->once() )
			->method( 'runService' )
			->with( $this->stringContains( 'FOO' ) );

		$instance = new ServicesContainer(
			[
				'test' => [ $this, 'fakeService' ]
			]
		);

		$instance->get( 'test', $fake, 'FOO' );
	}

	public function testAdd() {

		$fake = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runService' ] )
			->getMock();

		$fake->expects( $this->once() )
			->method( 'runService' );

		$instance = new ServicesContainer();

		$instance->add( 'test', [ $this, 'fakeService' ] );
		$instance->get( 'test', $fake );
	}

	public function testAddClosure() {

		$fake = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runService' ] )
			->getMock();

		$fake->expects( $this->once() )
			->method( 'runService' );

		$instance = new ServicesContainer();

		$closure = function( $arg ) use( $fake ) {
			$fake->runService( $arg );
		};

		$instance->add( 'test', $closure );
		$instance->get( 'test', $fake );
	}

	public function testUnknownServiceThrowsException() {

		$instance = new ServicesContainer();

		$this->setExpectedException( '\SMW\Services\Exception\ServiceNotFoundException' );
		$instance->get( 'test' );
	}

	public function testNonCallableServiceThrowsException() {

		$instance = new ServicesContainer(
			[
				'test' => 'Foo'
			]
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->get( 'test' );
	}

	public function fakeService( $fake, $arg = '' ) {
		$fake->runService( $arg );
	}

	public function stdClassService( $arg = '' ) {
		return new \stdClass();
	}

}
