<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\TaskFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Api\TaskFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TaskFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $apiFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new TaskFactory();

		$this->assertInstanceOf(
			TaskFactory::class,
			$instance
		);
	}

	public function testGetAllowedTypes() {
		$instance = new TaskFactory();

		$this->assertIsArray(

			$instance->getAllowedTypes()
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testNewByType( $type ) {
		$instance = new TaskFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Api\Tasks\Task',
			$instance->newByType( $type )
		);
	}

	public function testNewByTypeOnUnknownTypeThrowsException() {
		$instance = new TaskFactory();

		$this->expectException( '\RuntimeException' );
		$instance->newByType( '__foo__' );
	}

	public function typeProvider() {
		$taskFactory = new TaskFactory();

		yield $taskFactory->getAllowedTypes();
	}

}
