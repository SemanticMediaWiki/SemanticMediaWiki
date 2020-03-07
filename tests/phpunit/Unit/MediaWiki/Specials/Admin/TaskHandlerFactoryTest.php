<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\TaskHandlerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TaskHandlerFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $hookDispatcher;
	private $store;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
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

		$user = $this->getMockBuilder( '\User' )
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
			'\SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry',
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
			'\SMW\MediaWiki\Specials\Admin\Maintenance\TableSchemaTaskHandler'
		];

		$provider[] = [
			'newSupportListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\SupportListTaskHandler'
		];

		$provider[] = [
			'newConfigurationListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler'
		];

		$provider[] = [
			'newOperationalStatisticsListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler'
		];

		$provider[] = [
			'newEntityLookupTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler'
		];

		$provider[] = [
			'newDataRefreshJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler'
		];

		$provider[] = [
			'newDisposeJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Maintenance\DisposeJobTaskHandler'
		];

		$provider[] = [
			'newPropertyStatsRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Maintenance\PropertyStatsRebuildJobTaskHandler'
		];

		$provider[] = [
			'newFulltextSearchTableRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler'
		];

		$provider[] = [
			'newDeprecationNoticeTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler'
		];

		$provider[] = [
			'newAlertsTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\AlertsTaskHandler'
		];

		$provider[] = [
			'newDuplicateLookupTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\Supplement\DuplicateLookupTaskHandler'
		];

		$provider[] = [
			'newMaintenanceTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler'
		];

		$provider[] = [
			'newSupplementTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\SupplementTaskHandler'
		];

		return $provider;
	}

}
