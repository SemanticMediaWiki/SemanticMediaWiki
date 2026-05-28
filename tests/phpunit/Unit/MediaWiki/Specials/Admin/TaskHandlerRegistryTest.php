<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Request\WebRequest;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class TaskHandlerRegistryTest extends TestCase {

	private $testEnvironment;
	private $hookContainer;
	private $store;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TaskHandlerRegistry::class,
			new TaskHandlerRegistry( $this->store, $this->outputFormatter )
		);
	}

	public function testRegisterTaskHandlers() {
		$this->hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'SMW::Admin::RegisterTaskHandlers', $this->isType( 'array' ) );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler = $this->getMockBuilder( TaskHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TaskHandlerRegistry(
			$this->store,
			$this->outputFormatter
		);

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->registerTaskHandlers( [ $taskHandler ], $user );

		// Can only be used once per instance
		$instance->registerTaskHandlers( [ $taskHandler ], $user );
	}

	/**
	 * @dataProvider sectionTypeProvider
	 */
	public function testRegisterTaskHandler( $section ) {
		$taskHandler = $this->getMockBuilder( TaskHandler::class )
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

			public function handleRequest( WebRequest $webRequest ): void {
			}
		};
	}
}
