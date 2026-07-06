<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\disposeOutdatedEntities;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Maintenance\disposeOutdatedEntities
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DisposeOutdatedEntitiesTest extends TestCase {

	private $testEnvironment;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessagereporter();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			disposeOutdatedEntities::class,
			new disposeOutdatedEntities()
		);
	}

	public function testExecute() {
		$instance = new disposeOutdatedEntities();

		$instance->setMessageReporter(
			$this->spyMessageReporter
		);

		$instance->execute();

		$this->assertStringContainsString(
			'Outdated entitie(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	/**
	 * @dataProvider shardConfigProvider
	 */
	public function testGetShardConfigError( bool $hasOf, bool $hasShard, int $of, int $shard, ?string $expected ) {
		$instance = new disposeOutdatedEntities();

		$this->assertSame(
			$expected,
			$instance->getShardConfigError( $hasOf, $hasShard, $of, $shard )
		);
	}

	public function shardConfigProvider() {
		$together = '--of and --shard must be used together.';
		$range = 'Invalid shard configuration: require --of >= 1 and 0 <= --shard < --of.';

		yield 'unsharded default (neither option)' => [ false, false, 1, 0, null ];
		yield 'valid shard 0 of 4' => [ true, true, 4, 0, null ];
		yield 'valid shard 3 of 4' => [ true, true, 4, 3, null ];
		yield 'shard without of' => [ false, true, 1, 1, $together ];
		yield 'of without shard' => [ true, false, 4, 0, $together ];
		yield 'of below 1' => [ true, true, 0, 0, $range ];
		yield 'shard equals of' => [ true, true, 4, 4, $range ];
		yield 'shard above range' => [ true, true, 2, 5, $range ];
		yield 'negative shard' => [ true, true, 4, -1, $range ];
	}

}
