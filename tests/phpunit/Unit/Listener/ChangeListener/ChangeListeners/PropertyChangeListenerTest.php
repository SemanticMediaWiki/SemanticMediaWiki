<?php

namespace SMW\Tests\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\DIProperty;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListenerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $logger;
	private $property;
	private $changeRecord;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertyChangeListener::class,
			new PropertyChangeListener( $this->store )
		);
	}

	public function testCanTrigger() {

		$property = new DIProperty( 'Foo' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$instance = new PropertyChangeListener( $this->store );
		$instance->addListenerCallback( $property, [ $this, 'observeChange' ] );


		$this->assertFalse(
			$instance->canTrigger( 'bar' )
		);

		$this->assertTrue(
			$instance->canTrigger( 'Foo' )
		);
	}

	public function testRecordAndMatch() {

		$property = new DIProperty( 'Foo' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$instance = new PropertyChangeListener( $this->store );
		$instance->addListenerCallback( $property, [ $this, 'observeChange' ] );

		$instance->setLogger(
			$this->logger
		);

		$instance->recordChange( 42, [ 'foo' => 'bar' ] );
		$instance->matchAndTriggerChangeListeners();

		$this->assertEquals(
			$property,
			$this->property
		);

		$records = [
			new ChangeRecord( [ 'foo' => 'bar' ] )
		];

		$this->assertEquals(
			new ChangeRecord( $records ),
			$this->changeRecord
		);
	}

	public function observeChange( $property, $record ) {
		$this->property = $property;
		$this->changeRecord = $record;
	}

}
