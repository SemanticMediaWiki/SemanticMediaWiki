<?php

namespace SMW\Tests\Listener\ChangeListener;

use SMW\Listener\ChangeListener\CallableChangeListenerTrait;

/**
 * @covers \SMW\Listener\ChangeListener\CallableChangeListenerTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CallableChangeListenerTraitTest extends \PHPUnit_Framework_TestCase {

	private $changeKey;
	private $changeRecord;

	public function testCanTrigger() {

		$instance = $this->newCallableChangeListenerClass(
			[ 'foo' => [ [ $this, 'runChange' ] ] ]
		);

		$this->assertTrue(
			$instance->canTrigger( 'foo' )
		);
	}

	public function testTrigger() {

		$logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newCallableChangeListenerClass(
			[ 'foo' => [ [ $this, 'runChange' ] ] ]
		);

		$instance->setLogger(
			$logger
		);

		$instance->trigger( 'foo' );

		$this->assertEquals(
			'foo',
			$this->changeKey
		);

		$this->assertInstanceof(
			'\SMW\Listener\ChangeListener\ChangeRecord',
			$this->changeRecord
		);
	}

	public function runChange( $key, $record ) {
		$this->changeKey = $key;
		$this->changeRecord = $record;
	}

	private function newCallableChangeListenerClass( array $changeListeners = [] ) {
		return new class( $changeListeners ) {

			use CallableChangeListenerTrait;

			public function __construct( array $changeListeners ) {
				$this->changeListeners = $changeListeners;
			}

			public function registerChangeListener( $key, $changeListener ) {
				$this->changeListeners[$key][] = $changeListener;
			}

		};
	}

}
