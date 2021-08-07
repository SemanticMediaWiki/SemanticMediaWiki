<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\ParserCachePurgeJob;

/**
 * @covers \SMW\MediaWiki\Jobs\ParserCachePurgeJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ParserCachePurgeJobTest extends \PHPUnit_Framework_TestCase {

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

		$updateParserCacheCallback = function( $parameters ) use( $action ) {
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
			->will( $this->returnValue( $title ) );

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
			->will( $this->returnValue( $page ) );

		$instance->run();
	}

}
