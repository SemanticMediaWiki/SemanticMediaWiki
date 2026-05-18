<?php

namespace SMW\Tests\Unit\Elastic;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Hooks;
use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\EntityCache;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;

/**
 * @covers \SMW\Elastic\Hooks
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class HooksTest extends TestCase {

	private $elasticFactory;
	private EntityCache $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->elasticFactory = $this->getMockBuilder( ElasticFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Hooks::class,
			new Hooks( $this->elasticFactory, $this->entityCache )
		);
	}

	public function testGetHandlers() {
		$instance = new Hooks(
			$this->elasticFactory,
			$this->entityCache
		);

		$this->assertIsArray(

			$instance->getHandlers()
		);
	}

	public function testOnRegisterTaskHandlers() {
		$infoTaskHandler = $this->getMockBuilder( ElasticClientTaskHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->once() )
			->method( 'newInfoTaskHandler' )
			->willReturn( $infoTaskHandler );

		$taskHandlerRegistry = $this->getMockBuilder( TaskHandlerRegistry::class )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new Hooks(
			$this->elasticFactory,
			$this->entityCache
		);

		$instance->onRegisterTaskHandlers( $taskHandlerRegistry, $store, $outputFormatter, $user );
	}

	public function testOnRegisterEntityExaminerDeferrableIndicatorProviders() {
		$indicatorProviders = [];

		$replicationCheck = $this->getMockBuilder( ReplicationCheck::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->once() )
			->method( 'newReplicationCheck' )
			->willReturn( $replicationCheck );

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $config );

		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new Hooks(
			$this->elasticFactory,
			$this->entityCache
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
