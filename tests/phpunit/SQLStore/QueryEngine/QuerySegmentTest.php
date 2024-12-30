<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegment;

/**
 * @covers \SMW\SQLStore\QueryEngine\QuerySegment
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QuerySegmentTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegment',
			new QuerySegment()
		);
	}

	public function testInitialStateAfterReset() {
		$instance = new QuerySegment();
		$instance->reset();

		$this->assertSame(
			0,
			$instance->queryNumber
		);

		$this->assertEquals(
			't0',
			$instance->alias
		);

		$this->assertSame(
			1,
			$instance::$qnum
		);

		$this->assertEquals(
			$instance::Q_TABLE,
			$instance->type
		);

		$this->assertEquals(
			[],
			$instance->components
		);

		$this->assertEquals(
			[],
			$instance->sortfields
		);

		$this->assertSame(
			'',
			$instance->joinfield
		);

		$this->assertSame(
			'',
			$instance->joinTable
		);

		$this->assertSame(
			'',
			$instance->from
		);

		$this->assertSame(
			'',
			$instance->where
		);
	}

}
