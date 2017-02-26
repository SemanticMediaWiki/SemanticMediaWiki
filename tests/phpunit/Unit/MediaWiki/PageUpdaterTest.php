<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PageUpdater;

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

	protected function setUp() {
		parent::setup();

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

	public function testPurgeParserCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeParserCache();
	}

	public function testPurgeHtmlCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'touchLinks' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeHtmlCache();
	}

	public function testPurgeWebCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'purgeSquid' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeWebCache();
	}

	public function testFilterDuplicatePages() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->exactly( 2 ) )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );
		$instance->addPage( $title );

		$instance->doPurgeParserCache();
	}

	public function testPurgeParserCacheOnTransactionIdle() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			}
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater( $this->connection );
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeParserCache();
	}

	public function testPurgeParserCacheWillNotWaitOnTransactionIdleWithMissingConnection() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeParserCache();
	}

	public function testPurgeParserCacheWillNotWaitOnTransactionIdleWhenCommandLineModeIsActive() {

		$this->connection->expects( $this->never() )
			->method( 'onTransactionIdle' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater( $this->connection );
		$instance->addPage( $title );
		$instance->isCommandLineMode( true );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeParserCache();
	}

	public function testPurgeHtmlCacheOnTransactionIdle() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			}
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'touchLinks' );

		$instance = new PageUpdater( $this->connection );
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeHtmlCache();
	}

	public function testPurgeWebCacheOnTransactionIdle() {

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			}
			) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'purgeSquid' );

		$instance = new PageUpdater( $this->connection );
		$instance->addPage( $title );

		$instance->waitOnTransactionIdle();
		$instance->doPurgeWebCache();
	}

	public function testAddNullPage() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new PageUpdater();
		$instance->addPage( null );
	}

}
