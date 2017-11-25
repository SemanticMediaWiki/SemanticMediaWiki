<?php

namespace SMW\Tests;

use SMW\PostProcHandler;

/**
 * @covers \SMW\PostProcHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PostProcHandlerTest extends \PHPUnit_Framework_TestCase {

	private $parserOutput;
	private $cache;

	protected function setUp() {
		parent::setUp();

		$this->parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PostProcHandler::class,
			new PostProcHandler( $this->parserOutput, $this->cache )
		);
	}

	public function testGetHtmlOnCookie() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( ':post' ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::PROC_POST_QUERYREF ) )
			->will( $this->returnValue( [ 'Bar' => true ] ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0#" data-ref="[&quot;Bar&quot;]"></div>',
			$instance->getHtml( $title,  $webRequest )
		);
	}

	public function testGetHtmlOnLinksUpdateJournalEntry() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->stringContains( ':post' ) )
			->will( $this->returnValue( false ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( ':post' ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::PROC_POST_QUERYREF ) )
			->will( $this->returnValue( [ 'Bar' => true ] ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0#" data-ref="[&quot;Bar&quot;]"></div>',
			$instance->getHtml( $title,  $webRequest )
		);
	}

	/**
	 * @dataProvider queryRefProvider
	 */
	public function testAddQueryRef( $gExtensionData, $sExtensionData, $query ) {

		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::PROC_POST_QUERYREF ) )
			->will( $this->returnValue( $gExtensionData ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with( $this->equalTo( PostProcHandler::PROC_POST_QUERYREF ) )
			->will( $this->returnValue( $sExtensionData ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->addQueryRef( $query );
	}

	public function queryRefProvider() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( array( 'Foo' ) ) );

		$provider[] =[
			null,
			[ 'Foo' => true ],
			$query
		];

		$provider[] =[
			[ 'Bar' => true ],
			[ 'Bar' => true, 'Foo' => true ],
			$query
		];

		return $provider;
	}

}
