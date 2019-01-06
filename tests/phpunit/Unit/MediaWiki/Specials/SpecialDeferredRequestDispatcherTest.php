<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SpecialDeferredRequestDispatcherTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $stringValidator;
	private $spyLogger;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$store->setOption( 'smwgSemanticsEnabled', true );
		$store->setOption( 'smwgAutoRefreshSubject', true );

		$store->setLogger( $this->spyLogger );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher',
			new SpecialDeferredRequestDispatcher()
		);
	}

	public function testGetTargetURL() {

		$this->assertContains(
			':DeferredRequestDispatcher',
			SpecialDeferredRequestDispatcher::getTargetURL()
		);
	}

	public function testgetRequestToken() {

		$this->assertInternalType(
			'string',
			SpecialDeferredRequestDispatcher::getRequestToken( 'Foo' )
		);

		$this->assertNotSame(
			SpecialDeferredRequestDispatcher::getRequestToken( 'Bar' ),
			SpecialDeferredRequestDispatcher::getRequestToken( 'Foo' )
		);
	}

	public function testValidPostAsyncUpdateJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$timestamp =  time();

		$parameters = json_encode( [
			'async-job' => [ 'type' => 'SMW\UpdateJob', 'title' => 'Foo' ],
			'timestamp' => $timestamp,
			'requestToken' => SpecialDeferredRequestDispatcher::getRequestToken( $timestamp ),
		] );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( [ 'parameters' => $parameters ], true )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testValidPostAsyncParserCachePurgeJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$store->setLogger( $this->spyLogger );

		$this->applicationFactory->registerObject( 'Store', $store );

		$timestamp = time();

		$parameters = json_encode( [
			'async-job' => [ 'type' => 'SMW\ParserCachePurgeJob', 'title' => 'Foo' ],
			'timestamp' => $timestamp,
			'requestToken' => SpecialDeferredRequestDispatcher::getRequestToken( $timestamp ),
			'idlist' => [ 1, 2 ]
		] );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( [ 'parameters' => $parameters ], true )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testInvalidPostRequestToken() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$timestamp =  time();

		$parameters = json_encode( [
			'timestamp' => $timestamp,
			'requestToken' => SpecialDeferredRequestDispatcher::getRequestToken( 'Foo' )
		] );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( [ 'parameters' => $parameters ], true )
		);

		$this->assertNull(
			$instance->execute( '' )
		);
	}

	public function testGetRequestForAsyncJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$request = [];

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( $request, false )
		);

		$this->assertNull(
			$instance->execute( '' )
		);
	}

}
