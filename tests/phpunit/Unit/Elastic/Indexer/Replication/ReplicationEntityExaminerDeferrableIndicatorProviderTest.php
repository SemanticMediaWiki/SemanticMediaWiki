<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ReplicationEntityExaminerDeferrableIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $entityCache;
	private $replicationCheck;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->replicationCheck = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\ReplicationCheck' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ReplicationEntityExaminerDeferrableIndicatorProvider::class,
			new ReplicationEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache, $this->replicationCheck )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			new ReplicationEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache, $this->replicationCheck )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider',
			new ReplicationEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache, $this->replicationCheck )
		);
	}

	public function testGetName() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testIsSeverityType() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'bool',
			$instance->isSeverityType( 'foo' )
		);
	}

	public function testGetIndicators() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testIsDeferredMode() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'bool',
			$instance->isDeferredMode()
		);
	}

	public function testGetModules() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicators_NoCheck() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( false );

		$this->assertFalse(
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicators_CheckOnMaintenanceLock() {

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->never() )
			->method( 'fetch' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( true );

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicators_FromCache_NoCheck() {

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->with(	$this->stringContains( 'smw:entity:b94628b92d22cd315ccf7abb5b1df3c0' ) )
			->will( $this->returnValue( \SMW\Elastic\Indexer\Replication\ReplicationCheck::TYPE_SUCCESS ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( true );

		$this->assertFalse(
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicators_NoCache_Check() {

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( true );

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);

		$this->assertEquals(
			[ 'id' => 'smw-entity-examiner-deferred-elastic-replication' ],
			$instance->getIndicators()
		);
	}

	public function testHasIndicators_NoCache_DeferredCheck() {

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( true );
		$instance->setDeferredMode( true );

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);

		$indicators = $instance->getIndicators();

		$this->assertArrayHasKey(
			'id',
			$indicators
		);

		$this->assertArrayHasKey(
			'content',
			$indicators
		);
	}

	public function testHasIndicators_NoCache_DeferredCheck_ErrorSeverity() {

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->replicationCheck->expects( $this->any() )
			->method( 'checkReplication' )
			->with(	$this->equalTo( DIWikiPage::newFromText( '_MDAT', SMW_NS_PROPERTY ) ) )
			->will( $this->returnValue( '' ) );

		$this->replicationCheck->expects( $this->once() )
			->method( 'getSeverityType' )
			->will( $this->returnValue( \SMW\Elastic\Indexer\Replication\ReplicationCheck::SEVERITY_TYPE_ERROR ) );

		$subject = DIWikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->replicationCheck
		);

		$instance->canCheckReplication( true );
		$instance->setDeferredMode( true );

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);

		$indicators = $instance->getIndicators();

		$this->assertTrue(
			$instance->isSeverityType( \SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider::SEVERITY_ERROR )
		);

		$this->assertArrayHasKey(
			'id',
			$indicators
		);

		$this->assertArrayHasKey(
			'content',
			$indicators
		);
	}

}
