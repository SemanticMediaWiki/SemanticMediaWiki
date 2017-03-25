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
			'\SMW\MediaWiki\Specials\Admin\TaskHandlerFactory',
			new TaskHandlerFactory( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
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
			call_user_func( array( $instance, $method ) )
		);
	}

	public function methodProvider() {

		$provider[] = array(
			'newTableSchemaTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\TableSchemaTaskHandler'
		);

		$provider[] = array(
			'newSupportListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\SupportListTaskHandler'
		);

		$provider[] = array(
			'newConfigurationListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\ConfigurationListTaskHandler'
		);

		$provider[] = array(
			'newOperationalStatisticsListTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\OperationalStatisticsListTaskHandler'
		);

		$provider[] = array(
			'newIdTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\IdTaskHandler'
		);

		$provider[] = array(
			'newDataRefreshJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DataRefreshJobTaskHandler'
		);

		$provider[] = array(
			'newDisposeJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DisposeJobTaskHandler'
		);

		$provider[] = array(
			'newPropertyStatsRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\PropertyStatsRebuildJobTaskHandler'
		);

		$provider[] = array(
			'newFulltextSearchTableRebuildJobTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\FulltextSearchTableRebuildJobTaskHandler'
		);

		$provider[] = array(
			'newDeprecationNoticeTaskHandler',
			'\SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler'
		);

		return $provider;
	}

}
