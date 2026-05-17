<?php

namespace SMW\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SMW\Services\Exception\ServiceNotFoundException;
use SMW\Services\ServicesContainer;
use stdClass;

/**
 * @covers \SMW\Services\ServicesContainer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesContainerTest extends TestCase {

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

		$closure = static function ( $arg ) use( $fake ) {
			$fake->runService( $arg );
		};

		$instance->add( 'test', $closure );
		$instance->get( 'test', $fake );
	}

	public function testUnknownServiceThrowsException() {
		$instance = new ServicesContainer();

		$this->expectException( ServiceNotFoundException::class );
		$instance->get( 'test' );
	}

	public function testNonCallableServiceThrowsException() {
		$instance = new ServicesContainer(
			[
				'test' => 'Foo'
			]
		);

		$this->expectException( '\RuntimeException' );
		$instance->get( 'test' );
	}

	public function testIsRegisteredReturnsTrueForKnownKey() {
		$instance = new ServicesContainer(
			[
				'test' => [ $this, 'stdClassService' ]
			]
		);

		$this->assertTrue( $instance->isRegistered( 'test' ) );
	}

	public function testIsRegisteredReturnsFalseForUnknownKey() {
		$instance = new ServicesContainer();

		$this->assertFalse( $instance->isRegistered( 'unknown' ) );
	}

	public function testSingletonReturnsSameInstanceForSameArgs() {
		$instance = new ServicesContainer(
			[
				'test' => static function () {
					return new stdClass();
				}
			]
		);

		$first = $instance->singleton( 'test' );
		$second = $instance->singleton( 'test' );

		$this->assertSame( $first, $second );
	}

	public function testSingletonReturnsDifferentInstanceForDifferentArgs() {
		$instance = new ServicesContainer(
			[
				'test' => static function ( $arg ) {
					return new stdClass();
				}
			]
		);

		$first = $instance->singleton( 'test', 'arg1' );
		$second = $instance->singleton( 'test', 'arg2' );

		$this->assertNotSame( $first, $second );
	}

	public function testSingletonReturnsSameInstanceForSameArgsSecondCall() {
		$instance = new ServicesContainer(
			[
				'test' => static function ( $arg ) {
					return new stdClass();
				}
			]
		);

		$first = $instance->singleton( 'test', 'arg1' );
		$second = $instance->singleton( 'test', 'arg1' );

		$this->assertSame( $first, $second );
	}

	public function testCreateAlwaysReturnsFreshInstance() {
		$instance = new ServicesContainer(
			[
				'test' => static function () {
					return new stdClass();
				}
			]
		);

		$first = $instance->create( 'test' );
		$second = $instance->create( 'test' );

		$this->assertNotSame( $first, $second );
	}

	public function testCreateDoesNotShareCacheWithSingleton() {
		$instance = new ServicesContainer(
			[
				'test' => static function () {
					return new stdClass();
				}
			]
		);

		$singletonInstance = $instance->singleton( 'test' );
		$createdInstance = $instance->create( 'test' );

		$this->assertNotSame( $singletonInstance, $createdInstance );
	}

	public function testSingletonThrowsExceptionForUnknownKey() {
		$instance = new ServicesContainer();

		$this->expectException( ServiceNotFoundException::class );
		$instance->singleton( 'unknown' );
	}

	public function testCreateThrowsExceptionForUnknownKey() {
		$instance = new ServicesContainer();

		$this->expectException( ServiceNotFoundException::class );
		$instance->create( 'unknown' );
	}

	public function fakeService( $fake, $arg = '' ) {
		$fake->runService( $arg );
	}

	public function stdClassService( $arg = '' ) {
		return new stdClass();
	}

}
