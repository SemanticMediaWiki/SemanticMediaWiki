<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PageUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\PageUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class PageUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $connection;
	private $spyLogger;

	protected function setUp() {
		parent::setup();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageUpdater',
			 new PageUpdater()
		);
	}

	public function testCanUpdate() {

		$instance = new PageUpdater();

		$this->assertInternalType(
			'boolean',
			 $instance->canUpdate()
		);
	}

	/**
	 * @dataProvider purgeMethodProvider
	 */
	public function testPurge( $purgeMethod, $titleMethod ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( $titleMethod );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		call_user_func( [ $instance, $purgeMethod ] );
	}

	public function testDisablePurgeHtmlCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->never() )
			->method( 'touchLinks' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->isHtmlCacheUpdate( false );
		$instance->doPurgeHtmlCache();
	}

	public function testFilterDuplicatePages() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->exactly( 2 ) )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );
		$instance->addPage( $title );

		$instance->doPurgeParserCache();
	}

	/**
	 * @dataProvider purgeMethodProvider
	 */
	public function testPurgeOnTransactionIdle( $purgeMethod, $titleMethod ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( $titleMethod );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();

		call_user_func( [ $instance, $purgeMethod ] );

		$instance->doUpdate();
	}

	/**
	 * @dataProvider purgeMethodProvider
	 */
	public function testPurgeWillNotWaitOnTransactionIdleForMissingConnection(  $purgeMethod, $titleMethod ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method(  $titleMethod );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();

		call_user_func( [ $instance, $purgeMethod ] );

		$instance->doUpdate();
	}

	/**
	 * @dataProvider purgeMethodProvider
	 */
	public function testPurgeWillNotWaitOnTransactionIdleWhenCommandLineIsTrue( $purgeMethod, $titleMethod ) {

		$this->connection->expects( $this->never() )
			->method( 'onTransactionIdle' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( $titleMethod );

		$instance = new PageUpdater( $this->connection );
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();

		call_user_func( [ $instance, $purgeMethod ] );

		$instance->doUpdate();
	}

	public function testAddNullPage() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new PageUpdater();
		$instance->addPage( null );
	}

	/**
	 * @dataProvider purgeMethodProvider
	 */
	public function testPushPendingWaitableUpdate( $purgeMethod, $titleMethod ) {

		$transactionalCallableUpdate = $this->getMockBuilder( '\SMW\MediaWiki\Deferred\TransactionalCallableUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$transactionalCallableUpdate->expects( $this->once() )
			->method( 'pushUpdate' );

		$transactionalCallableUpdate->expects( $this->once() )
			->method( 'setFingerprint' )
			->with( $this->equalTo( 'Foobar' ) );

		$transactionalCallableUpdate->expects( $this->once() )
			->method( 'waitOnTransactionIdle' );

		$transactionalCallableUpdate->expects( $this->once() )
			->method( 'markAsPending' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new PageUpdater(
			$this->connection,
			$transactionalCallableUpdate
		);

		$instance->addPage( $title );
		$instance->setFingerprint( 'Foobar' );

		$instance->markAsPending();
		$instance->waitOnTransactionIdle();

		call_user_func( [ $instance, $purgeMethod ] );

		$instance->pushUpdate();
	}

	public function testPurgeCacheAsPoolPurge() {

		$row = new \stdClass;
		$row->page_id = 42;

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->once() )
			->method( 'update' );

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback ); }
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->never() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater( $this->connection );
		$instance->setLogger( $this->spyLogger );
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeParserCacheAsPool();
	}

	public function purgeMethodProvider() {

		$provider[] = [
			'doPurgeParserCache',
			'invalidateCache'
		];


		$provider[] = [
			'doPurgeHtmlCache',
			'touchLinks'
		];

		$provider[] = [
			'doPurgeWebCache',
			'purgeSquid'
		];

		return $provider;
	}

}
