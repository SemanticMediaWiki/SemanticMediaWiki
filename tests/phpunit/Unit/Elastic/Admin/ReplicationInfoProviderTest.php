<?php

namespace SMW\Tests\Unit\Elastic\Admin;

use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Admin\ReplicationInfoProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Store;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\Elastic\Admin\ReplicationInfoProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ReplicationInfoProviderTest extends TestCase {

	private $outputFormatter;
	private $webRequest;
	private $replicationCheck;
	private BagOStuff $cache;
	private $store;

	protected function setUp(): void {
		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->replicationCheck = $this->getMockBuilder( ReplicationCheck::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new HashBagOStuff();

		$this->webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( new DummyClient() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ReplicationInfoProvider::class,
			new ReplicationInfoProvider( $this->outputFormatter, $this->replicationCheck, $this->cache )
		);
	}

	public function testGetTask() {
		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->replicationCheck,
			$this->cache
		);

		$this->assertEquals(
			'replication',
			$instance->getSupplementTask()
		);

		$this->assertEquals(
			'elastic/replication',
			$instance->getTask()
		);
	}

	public function testGetHtml() {
		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->replicationCheck,
			$this->cache
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function tesHandleRequest_NoFailures() {
		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	[ 'action' => 'elastic' ] );

		$this->replicationCheck->expects( $this->once() )
			->method( 'getReplicationFailures' )
			->willReturn( [] );

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->replicationCheck,
			$this->cache
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

	public function testHandleRequest_WithFailuresOnPages() {
		$ns = NS_FILE;

		$this->replicationCheck->expects( $this->once() )
			->method( 'getReplicationFailures' )
			->willReturn( [ 'Foo#0##', "Bar#$ns##" ] );

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->replicationCheck,
			$this->cache
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

	public function testShowsTheIngestErrorOfAFileThatFailedReplication() {
		$title = Title::newFromText( 'Foo.pdf', NS_FILE );

		$this->cache->set(
			FileIndexer::makeIngestResponseKey( $this->cache, $title ),
			[ 'error' => [ 'root_cause' => [ [ 'reason' => 'Unreadable attachment' ] ] ] ]
		);

		$this->replicationCheck->expects( $this->any() )
			->method( 'getReplicationFailures' )
			->willReturn( [ WikiPage::newFromTitle( $title )->getHash() ] );

		$html = '';

		$this->outputFormatter->expects( $this->any() )
			->method( 'addHTML' )
			->willReturnCallback( static function ( $text ) use ( &$html ) {
				$html .= $text;
			} );

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->replicationCheck,
			$this->cache
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );

		$this->assertStringContainsString( 'Unreadable attachment', $html );
	}

}
