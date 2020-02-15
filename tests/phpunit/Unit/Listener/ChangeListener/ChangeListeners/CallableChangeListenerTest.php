<?php

namespace SMW\Tests\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListeners\CallableChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeListeners\CallableChangeListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ChangeRecordTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $logger;
	private $key;
	private $changeRecord;

	protected function setUp() : void {
		parent::setUp();

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CallableChangeListener::class,
			new CallableChangeListener()
		);
	}

	public function testCanTrigger() {

		$instance = new CallableChangeListener();
		$instance->addListenerCallback( 'foo', [ $this, 'observeChange' ] );


		$this->assertFalse(
			$instance->canTrigger( 'bar' )
		);

		$this->assertTrue(
			$instance->canTrigger( 'foo' )
		);
	}

	public function testTrigger() {

		$instance = new CallableChangeListener();
		$instance->addListenerCallback( 'foo', [ $this, 'observeChange' ] );

		$instance->setLogger(
			$this->logger
		);

		$instance->setAttrs( [ 'foobar' => 'bar' ] );
		$instance->trigger( 'foo' );

		$this->assertEquals(
			'foo',
			$this->key
		);

		$this->assertEquals(
			new ChangeRecord( [ 'foobar' => 'bar' ] ),
			$this->changeRecord
		);
	}

	public function observeChange( $key, $record ) {
		$this->key = $key;
		$this->changeRecord = $record;
	}

}
