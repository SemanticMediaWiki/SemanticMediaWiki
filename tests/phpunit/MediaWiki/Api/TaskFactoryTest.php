<?php

namespace SMW\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\TaskFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TaskFactoryTest extends TestCase {

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
			Task::class,
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
