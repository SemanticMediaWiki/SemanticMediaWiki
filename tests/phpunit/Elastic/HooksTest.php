<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\Hooks;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Hooks
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class HooksTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $elasticFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->elasticFactory = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'EntityCache', $entityCache );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Hooks::class,
			new Hooks( $this->elasticFactory )
		);
	}

	public function testGetHandlers() {
		$instance = new Hooks(
			$this->elasticFactory
		);

		$this->assertIsArray(

			$instance->getHandlers()
		);
	}

	public function testOnRegisterTaskHandlers() {
		$infoTaskHandler = $this->getMockBuilder( '\SMW\Elastic\Admin\ElasticClientTaskHandler' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->once() )
			->method( 'newInfoTaskHandler' )
			->willReturn( $infoTaskHandler );

		$taskHandlerRegistry = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new Hooks(
			$this->elasticFactory
		);

		$instance->onRegisterTaskHandlers( $taskHandlerRegistry, $store, $outputFormatter, $user );
	}

	public function testOnRegisterEntityExaminerDeferrableIndicatorProviders() {
		$indicatorProviders = [];

		$replicationCheck = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\ReplicationCheck' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->once() )
			->method( 'newReplicationCheck' )
			->willReturn( $replicationCheck );

		$config = $this->getMockBuilder( '\SMW\Elastic\Config' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $config );

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new Hooks(
			$this->elasticFactory
		);

		$instance->onRegisterEntityExaminerDeferrableIndicatorProviders( $store, $indicatorProviders );

		$this->assertNotEmpty(
			$indicatorProviders
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {
		// Available class methods to be tested
		$classMethods = get_class_methods( Hooks::class );

		// Match all "testOn" to define the expected set of methods
		$testMethods = preg_grep( '/^testOn/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testOn', 'on', $testMethods )
		);

		foreach ( $classMethods as $name ) {

			if ( substr( $name, 0, 2 ) !== 'on' ) {
				continue;
			}

			$this->assertArrayHasKey(
				$name,
				$testMethods,
				"Failed to find a test for the `$name` hook listener!"
			);
		}
	}

}
