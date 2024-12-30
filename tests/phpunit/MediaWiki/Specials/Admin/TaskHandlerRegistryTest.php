<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class TaskHandlerRegistryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $hookDispatcher;
	private $store;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TaskHandlerRegistry::class,
			new TaskHandlerRegistry( $this->store, $this->outputFormatter )
		);
	}

	public function testRegisterTaskHandlers() {
		$this->hookDispatcher->expects( $this->once() )
			->method( 'onRegisterTaskHandlers' );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TaskHandlerRegistry(
			$this->store,
			$this->outputFormatter
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->registerTaskHandlers( [ $taskHandler ], $user );

		// Can only be used once per instance
		$instance->registerTaskHandlers( [ $taskHandler ], $user );
	}

	/**
	 * @dataProvider sectionTypeProvider
	 */
	public function testRegisterTaskHandler( $section ) {
		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler->expects( $this->once() )
			->method( 'getSection' )
			->willReturn( $section );

		$instance = new TaskHandlerRegistry(
			$this->store,
			$this->outputFormatter
		);

		$instance->registerTaskHandler( $taskHandler );

		$this->assertEquals(
			[ $taskHandler ],
			$instance->get( $section )
		);
	}

	public function testRegisterTaskHandler_Actionable() {
		$taskHandler = $this->newActionableTask();

		$instance = new TaskHandlerRegistry(
			$this->store,
			$this->outputFormatter
		);

		$instance->registerTaskHandler( $taskHandler );

		$this->assertEquals(
			[ $taskHandler ],
			$instance->get( TaskHandler::ACTIONABLE )
		);
	}

	public function sectionTypeProvider() {
		yield [
			TaskHandler::SECTION_MAINTENANCE
		];

		yield [
			TaskHandler::SECTION_ALERTS
		];

		yield [
			TaskHandler::SECTION_SUPPLEMENT
		];

		yield [
			TaskHandler::SECTION_SUPPORT
		];
	}

	public function newActionableTask() {
		return new class extends TaskHandler implements ActionableTask {

			public function getHtml() {
				return '';
			}

			public function getTask(): string {
				return 'Foo';
			}

			public function isTaskFor( string $action ): bool {
				return '';
			}

			public function handleRequest( \WebRequest $webRequest ) {
				return '';
			}
		};
	}
}
