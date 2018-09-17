<?php

namespace SMW\Tests\SQLStore\ChangeOp;

use SMW\SQLStore\ChangeOp\FieldChangeOp;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\ChangeOp\FieldChangeOp
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class FieldChangeOpTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FieldChangeOp::class,
			new FieldChangeOp()
		);
	}

	public function testChangeOp() {

		$op = [
			's_id' => 462,
			'o_serialized' => '1/2016/6/10/2/3/31/0',
			'o_sortkey' => '2457549.5857755',
		];

		$instance = new FieldChangeOp(
			$op
		);

		$this->assertFalse(
			$instance->has( 'foo' )
		);

		$this->assertSame(
			'1/2016/6/10/2/3/31/0',
			$instance->get( 'o_serialized' )
		);

		$instance->set( 'foo', 'bar' );

		$this->assertSame(
			'bar',
			$instance->get( 'foo' )
		);
	}

	public function testInvalidGetRequestThrowsException() {

		$instance = new FieldChangeOp();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'o_serialized' );
	}

}
