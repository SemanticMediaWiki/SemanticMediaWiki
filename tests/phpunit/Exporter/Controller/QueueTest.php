<?php

namespace SMW\Tests\Exporter\Controller;

use SMW\DIWikiPage;
use SMW\Exporter\Controller\Queue;

/**
 * @covers \SMW\Exporter\Queue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class QueueTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Queue::class,
			new Queue()
		);
	}

	public function testGetMembers() {
		$dataItem = DIWikiPage::newFromText( 'Foo' );
		$instance = new Queue();

		$instance->add( $dataItem, 1 );

		$this->assertEquals(
			[ 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' => $dataItem ],
			$instance->getMembers()
		);
	}

	public function testReset() {
		$dataItem = DIWikiPage::newFromText( 'Foo' );
		$instance = new Queue();

		$this->assertFalse(
			$instance->reset()
		);

		$instance->add( $dataItem, 1 );

		$this->assertEquals(
			$dataItem,
			$instance->reset()
		);
	}

	public function testDone() {
		$dataItem = DIWikiPage::newFromText( 'Foo' );
		$instance = new Queue();

		$this->assertFalse(
			$instance->isDone( $dataItem, 1 )
		);

		$instance->done( $dataItem, 1 );

		$this->assertTrue(
			$instance->isDone( $dataItem, 1 )
		);
	}

	public function testAddAndCount() {
		$dataItem = DIWikiPage::newFromText( 'Foo' );

		$instance = new Queue();
		$instance->add( $dataItem, 2 );

		$this->assertSame(
			1,
			$instance->count()
		);

		$instance->done( $dataItem, 1 );

		$this->assertSame(
			0,
			$instance->count()
		);

		$this->assertSame(
			0,
			$instance->count()
		);
	}

}
