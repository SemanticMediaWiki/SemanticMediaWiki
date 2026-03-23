<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler;
use SMW\MediaWiki\Specials\Admin\AlertsTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\DisposeJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\PropertyStatsRebuildJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\TableSchemaTaskHandler;
use SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\DuplicateLookupTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler;
use SMW\MediaWiki\Specials\Admin\SupplementTaskHandler;
use SMW\MediaWiki\Specials\Admin\SupportListTaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\TaskHandlerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TaskHandlerFactoryTest extends TestCase {

	private $testEnvironment;
	private $hookDispatcher;
	private $store;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->htmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TaskHandlerFactory::class,
			new TaskHandlerFactory( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testNewTaskHandlerRegistry() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$adminFeatures = 0;

		$instance = new TaskHandlerFactory(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertInstanceOf(
			TaskHandlerRegistry::class,
			$instance->newTaskHandlerRegistry( $user, $adminFeatures )
		);
	}

	/**
	 * @dataProvider methodProvider
	 */
	public function testCanConstructByFactory( $method, $expected ) {
		$instance = new TaskHandlerFactory(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$this->assertInstanceOf(
			$expected,
			call_user_func( [ $instance, $method ] )
		);
	}

	public function methodProvider() {
		$provider[] = [
			'newTableSchemaTaskHandler',
			TableSchemaTaskHandler::class
		];

		$provider[] = [
			'newSupportListTaskHandler',
			SupportListTaskHandler::class
		];

		$provider[] = [
			'newConfigurationListTaskHandler',
			ConfigurationListTaskHandler::class
		];

		$provider[] = [
			'newOperationalStatisticsListTaskHandler',
			OperationalStatisticsListTaskHandler::class
		];

		$provider[] = [
			'newEntityLookupTaskHandler',
			EntityLookupTaskHandler::class
		];

		$provider[] = [
			'newDataRefreshJobTaskHandler',
			DataRefreshJobTaskHandler::class
		];

		$provider[] = [
			'newDisposeJobTaskHandler',
			DisposeJobTaskHandler::class
		];

		$provider[] = [
			'newPropertyStatsRebuildJobTaskHandler',
			PropertyStatsRebuildJobTaskHandler::class
		];

		$provider[] = [
			'newFulltextSearchTableRebuildJobTaskHandler',
			FulltextSearchTableRebuildJobTaskHandler::class
		];

		$provider[] = [
			'newDeprecationNoticeTaskHandler',
			DeprecationNoticeTaskHandler::class
		];

		$provider[] = [
			'newAlertsTaskHandler',
			AlertsTaskHandler::class
		];

		$provider[] = [
			'newDuplicateLookupTaskHandler',
			DuplicateLookupTaskHandler::class
		];

		$provider[] = [
			'newMaintenanceTaskHandler',
			MaintenanceTaskHandler::class
		];

		$provider[] = [
			'newSupplementTaskHandler',
			SupplementTaskHandler::class
		];

		return $provider;
	}

}
