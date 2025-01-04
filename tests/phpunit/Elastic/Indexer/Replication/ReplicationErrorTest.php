<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\ReplicationError;
use SMW\Tests\PHPUnitCompat;
use SMWDITime as DITime;

/**
 * @covers \SMW\Elastic\Indexer\Replication\ReplicationError
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ReplicationErrorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ReplicationError::class,
			new ReplicationError( ReplicationError::TYPE_EXCEPTION )
		);
	}

	public function testGet() {
		$instance = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION,
			[ 'foo' => 'bar' ]
		);

		$this->assertEquals(
			'bar',
			$instance->get( 'foo' )
		);
	}

	public function testGet_OnUnknownKeyThrowsException() {
		$instance = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION
		);

		$this->expectException( '\InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function testIsType() {
		$instance = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION
		);

		$this->assertIsBool(

			$instance->is( ReplicationError::TYPE_EXCEPTION )
		);
	}

	public function testGetData() {
		$data = [
			'Foo' => 'Bar'
		];

		$instance = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION,
			$data
		);

		$this->assertEquals(
			$data,
			$instance->getData()
		);
	}

	public function testGetType() {
		$instance = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION
		);

		$this->assertEquals(
			ReplicationError::TYPE_EXCEPTION,
			$instance->getType()
		);
	}

}
