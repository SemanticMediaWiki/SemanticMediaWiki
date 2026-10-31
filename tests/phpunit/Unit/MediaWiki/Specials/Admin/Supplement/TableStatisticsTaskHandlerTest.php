<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\TableStatisticsTaskHandler;
use SMW\Tests\TestEnvironment;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\TableStatisticsTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $outputFormatter;
	private BagOStuff $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new HashBagOStuff();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableStatisticsTaskHandler::class,
			new TableStatisticsTaskHandler( $this->outputFormatter, $this->cache )
		);
	}

	public function testGetHtml() {
		$instance = new TableStatisticsTaskHandler(
			$this->outputFormatter,
			$this->cache
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testHandleRequest() {
		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new TableStatisticsTaskHandler(
			$this->outputFormatter,
			$this->cache
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

	/**
	 * @dataProvider cachedStatisticsProvider
	 */
	public function testMarksTheReportTabWhenStatisticsComeFromTheCache( bool $isCached ) {
		if ( $isCached ) {
			$this->cache->set( Task::makeCacheKey( TableStatisticsTask::CACHE_KEY ), [ 'smw_object_ids' => [] ] );
		}

		$html = '';

		$this->outputFormatter->expects( $this->any() )
			->method( 'addHtml' )
			->willReturnCallback( static function ( $text ) use ( &$html ) {
				$html .= $text;
			} );

		$instance = new TableStatisticsTaskHandler(
			$this->outputFormatter,
			$this->cache
		);

		$instance->handleRequest( $this->createMock( WebRequest::class ) );

		$this->assertSame( $isCached, str_contains( $html, ' cached' ) );
	}

	public function cachedStatisticsProvider(): iterable {
		yield 'cached' => [ true ];
		yield 'not cached' => [ false ];
	}

}
