<?php

namespace SMW\Tests;

use SMW\ChangePropListener;
use SMW\Tests\TestEnvironment;
use SMW\DIProperty;

/**
 * @covers \SMW\ChangePropListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropListenerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangePropListener::class,
			new ChangePropListener()
		);
	}

	public function testRegisterListenerAndCall() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->with( $this->equalTo( new DIProperty( 'Foo' ) ) )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'execute' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'execute' )
			->with( $this->equalTo( [ 'Bar' ] ) );

		$instance = new ChangePropListener();

		$instance->addListenerCallback( 'Foo', function( $record ) use( $test ) {
			$test->execute( $record );
		} );

		$instance->enabledListeners( $store );

		$instance->record( 42, [ 'Bar' ] );

		$instance->callListeners();
		TestEnvironment::executePendingDeferredUpdates();

		$instance->clearListeners();
	}

}
