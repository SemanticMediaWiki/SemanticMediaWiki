<?php

namespace SMW\Tests;

use ParserOutput;
use Onoi\Cache\Cache;
use SMWQuery;
use SMW\DIWikiPage;
use SMW\EntityCache;
use SMW\NamespaceExaminer;
use SMW\PostProcHandler;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\FieldChangeOp;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\Tests\PHPUnitCompat;
use SMW\DependencyValidator;
use Title;
use WebRequest;

/**
 * @covers \SMW\PostProcHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PostProcHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $parserOutput;
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->parserOutput = $this->createMock( ParserOutput::class );

		$this->cache = $this->createMock( Cache::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PostProcHandler::class,
			new PostProcHandler( $this->parserOutput, $this->cache )
		);
	}

	public function testGetHtmlOnCookie() {
		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) )
			->will( $this->returnValue( [ 'Bar' => true ] ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$title = $this->createMock( Title::class );

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->createMock( WebRequest::class );

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0##" data-ref="[&quot;Bar&quot;]"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	public function testGetHtml_CheckQuery() {
		$this->cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( true ) );

		$this->parserOutput->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) ],
				[ $this->equalTo( PostProcHandler::POST_EDIT_CHECK ) ]
			)
			->willReturnOnConsecutiveCalls(
				[ 'Bar' => true ],
				[ 'Foobar' ]
			);

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->setOptions(
			[
				'check-query' => true
			]
		);

		$title = $this->createMock( Title::class );

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->createMock( WebRequest::class );

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0##" data-ref="[&quot;Bar&quot;]" data-query="[&quot;Foobar&quot;]"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	public function testGetHtml_DifferentExtensionData() {
		// inverse testing - Mocking the data to ensure that the html has DifferentExtensionData
		$this->cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( true ) );

		$this->parserOutput->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) ],
				[ $this->equalTo( PostProcHandler::POST_EDIT_CHECK ) ]
			)
			->willReturnOnConsecutiveCalls(
				[ 'TestValue' => true ],
            	[] 
 );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->setOptions(
			[
				'check-query' => true
			]
		);

		$title = $this->createMock( Title::class );

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->createMock( WebRequest::class );

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		// Check that the returned HTML does not contain the expected data attributes - inverse testing
		$this->assertNotContains(
			'<div class="smw-postproc" data-subject="Foo#0##" data-ref="[&quot;Bar&quot;]" data-query="[&quot;Foobar&quot;]"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	public function testRunJobs() {
		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->setOptions(
			[
				'run-jobs' => [ 'fooJob' => 2 ]
			]
		);

		$title = $this->createMock( Title::class );

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->createMock( WebRequest::class );

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0##" data-jobs="{&quot;fooJob&quot;:2}"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	public function testPurgePageOnQueryDependency() {
		$this->parserOutput->expects( $this->any() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) )
			->will( $this->returnValue( [ 'Bar' ] ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->setOptions(
			[
				'run-jobs' => [ 'fooJob' => 2 ],
				'purge-page' => [ 'on-outdated-query-dependency' => true ]
			]
		);

		$title = $this->createMock( Title::class );

		$dependencyLinksValidator = $this->createMock( DependencyLinksValidator::class );
		$namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$entityCache = $this->createMock( EntityCache::class );
		$dependencyValidator = new DependencyValidator(
			$namespaceExaminer,
			$dependencyLinksValidator,
			$entityCache
		);
		$dependencyValidator->markTitle( $title );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$webRequest = $this->createMock( WebRequest::class );

		$this->assertContains(
			'<div class="smw-postproc page-purge" data-subject="#0##" data-title="Foo" data-msg="smw-purge-update-dependencies" data-forcelinkupdate="1"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	/**
	 * @dataProvider validPropertyKey
	 */
	public function testGetHtmlOnCookieAndValidChangeDiff( $key ) {
		$fieldChangeOp = $this->createMock( FieldChangeOp::class );

		$fieldChangeOp->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( 42 ) );

		$tableChangeOp = $this->createMock( TableChangeOp::class );

		$tableChangeOp->expects( $this->any() )
			->method( 'getFieldChangeOps' )
			->will( $this->returnValue( [ $fieldChangeOp ] ) );

		$changeDiff = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[ $tableChangeOp ],
			[],
			[ $key => 42 ]
		);

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $changeDiff->serialize() ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) )
			->will( $this->returnValue( [ 'Bar' ] ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$title = $this->createMock( Title::class );

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$webRequest = $this->createMock( WebRequest::class );

		$webRequest->expects( $this->once() )
			->method( 'getCookie' )
			->will( $this->returnValue( 'FakeCookie' ) );

		$this->assertContains(
			'<div class="smw-postproc" data-subject="Foo#0##" data-ref="[0]"></div>',
			$instance->getHtml( $title, $webRequest )
		);
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testAddUpdate( $gExtensionData, $sExtensionData, $query ) {
		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) )
			->will( $this->returnValue( $gExtensionData ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_UPDATE ) )
			->will( $this->returnValue( $sExtensionData ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->addUpdate( $query );
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testAddCheck( $gExtensionData, $sExtensionData, $query ) {
		$this->parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_CHECK ) )
			->will( $this->returnValue( $gExtensionData ) );

		$this->parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with( $this->equalTo( PostProcHandler::POST_EDIT_CHECK ) )
			->will( $this->returnValue( $sExtensionData ) );

		$instance = new PostProcHandler(
			$this->parserOutput,
			$this->cache
		);

		$instance->setOptions(
			[
				'check-query' => true
			]
		);

		$instance->addCheck( $query );
	}

	public function queryProvider() {
		$query = $this->createMock( SMWQuery::class );

		$query->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( [ 'Foo' ] ) );

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

	public function validPropertyKey() {
		yield [
			'Foo'
		];

		yield [
			'_INST'
		];

		yield [
			'_ASK'
		];
	}

}
