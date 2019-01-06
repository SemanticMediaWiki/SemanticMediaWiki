<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\Tests\TestEnvironment;

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

	private $testEnvironment;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

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

	public function testGetTaskHandlerList() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$adminFeatures = '';

		$instance = new TaskHandlerFactory(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$this->assertInternalType(
			'array',
			$instance->getTaskHandlerList( $user, $adminFeatures )
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
			'\SMW\MediaWiki\Specials\Admin\TableSchemaTaskHandler'
		];

		$provider[] = [
			'newSupportListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\SupportListTaskHandler'
		];

		$provider[] = [
			'newConfigurationListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\ConfigurationListTaskHandler'
		];

		$provider[] = [
			'newOperationalStatisticsListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\OperationalStatisticsListTaskHandler'
		];

		$provider[] = [
			'newEntityLookupTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\EntityLookupTaskHandler'
		];

		$provider[] = [
			'newDataRefreshJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DataRefreshJobTaskHandler'
		];

		$provider[] = [
			'newDisposeJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DisposeJobTaskHandler'
		];

		$provider[] = [
			'newPropertyStatsRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\PropertyStatsRebuildJobTaskHandler'
		];

		$provider[] = [
			'newFulltextSearchTableRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\FulltextSearchTableRebuildJobTaskHandler'
		];

		$provider[] = [
			'newDeprecationNoticeTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler'
		];

		$provider[] = [
			'newDuplicateLookupTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DuplicateLookupTaskHandler'
		];

		return $provider;
	}

}
