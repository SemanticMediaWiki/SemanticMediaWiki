<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\ParserCachePurgeJob;

/**
 * @covers \SMW\MediaWiki\Jobs\ParserCachePurgeJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ParserCachePurgeJobTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParserCachePurgeJob::class,
			new ParserCachePurgeJob( $title )
		);
	}

	public function testRun() {
		$action = 'Foo';

		$updateParserCacheCallback = static function ( $parameters ) use( $action ) {
			return $parameters['causeAction'] === $action;
		};

		$parameters = [
			'action' => $action
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$page->expects( $this->once() )
			->method( 'doPurge' );

		if ( method_exists( $page, 'updateParserCache' ) ) {
			$page->expects( $this->once() )
				->method( 'updateParserCache' )
				->with( $this->callback( $updateParserCacheCallback ) );
		}

		$instance = $this->getMockBuilder( ParserCachePurgeJob::class )
			->setConstructorArgs( [ $title, $parameters ] )
			->setMethods( [ 'newWikiPage' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newWikiPage' )
			->willReturn( $page );

		$instance->run();
	}

}
