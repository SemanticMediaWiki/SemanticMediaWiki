<?php

namespace SMW\Tests;

use SMW\DeferredRequestDispatchManager;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DeferredRequestDispatchManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class DeferredRequestDispatchManagerTest extends \PHPUnit_Framework_TestCase {

	private $spyLogger;
	private $httpRequest;
	private $jobFactory;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->spyLogger = $testEnvironment->getUtilityFactory()->newSpyLogger();

		$this->httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\SocketRequest' )
			->disableOriginalConstructor()
			->getMock();

		$job = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->any() )
			->method( 'newByType' )
			->will( $this->returnValue( $job ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DeferredRequestDispatchManager',
			new DeferredRequestDispatchManager( $this->httpRequest, $this->jobFactory )
		);
	}

	/**
	 * @dataProvider nullTitleProvider
	 */
	public function testDispatchOnNullTitle( $method ) {

		$this->httpRequest->expects( $this->never() )
			->method( 'ping' );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();

		$instance->isEnabledHttpDeferredRequest( true );
		$instance->isEnabledJobQueue( false );

		call_user_func_array( array( $instance, $method ), array( null, array() ) );
	}

	/**
	 * @dataProvider dispatchableJobProvider
	 */
	public function testDispatchJobFor( $type, $deferredJobRequestState, $parameters = array() ) {

		$this->httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();

		$instance->isEnabledHttpDeferredRequest( $deferredJobRequestState );
		$instance->isEnabledJobQueue( false );
		$instance->setLogger( $this->spyLogger );

		$this->assertTrue(
			$instance->dispatchJobRequestWith( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
		);
	}

	public function testDispatchParserCachePurgeJob() {

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();

		$instance->isEnabledHttpDeferredRequest( true );
		$instance->isEnabledJobQueue( false );

		$parameters = array( 'idlist' => '1|2' );
		$title = DIWikiPage::newFromText( __METHOD__ )->getTitle();

		$this->assertTrue(
			$instance->dispatchParserCachePurgeJobWith( $title, $parameters )
		);
	}

	public function testDispatchFulltextSearchTableUpdateJob() {

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();

		$instance->isEnabledHttpDeferredRequest( true );
		$instance->isEnabledJobQueue( false );

		$parameters = array();
		$title = DIWikiPage::newFromText( __METHOD__ )->getTitle();

		$this->assertTrue(
			$instance->dispatchFulltextSearchTableUpdateJobWith( $title, $parameters )
		);
	}

	public function testEnabledHttpDeferredRequestOn_HTTP_DEFERRED_SYNC_JOB() {

		$this->httpRequest->expects( $this->never() )
			->method( 'ping' );

		$job = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$job->expects( $this->once() )
			->method( 'run' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newByType' )
			->will( $this->returnValue( $job ) );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$jobFactory
		);

		$instance->reset();
		$instance->isEnabledHttpDeferredRequest( SMW_HTTP_DEFERRED_SYNC_JOB );

		$parameters = array();
		$title = DIWikiPage::newFromText( __METHOD__ )->getTitle();

		$this->assertTrue(
			$instance->dispatchFulltextSearchTableUpdateJobWith( $title, $parameters )
		);
	}

	public function testEnabledHttpDeferredRequestOn_SMW_HTTP_DEFERRED_ASYNC() {

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();
		$instance->isEnabledHttpDeferredRequest( SMW_HTTP_DEFERRED_ASYNC );
		$instance->isEnabledJobQueue( false );

		$parameters = array();
		$title = DIWikiPage::newFromText( __METHOD__ )->getTitle();

		$this->assertTrue(
			$instance->dispatchFulltextSearchTableUpdateJobWith( $title, $parameters )
		);
	}

	/**
	 * @dataProvider preliminaryCheckProvider
	 */
	public function testPreliminaryCheckForType( $type, $parameters = array() ) {

		$this->httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager(
			$this->httpRequest,
			$this->jobFactory
		);

		$instance->reset();

		$instance->isEnabledJobQueue( false );

		$this->assertNull(
			$instance->dispatchJobRequestWith( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
		);
	}

	public function dispatchableJobProvider() {

		$provider[] = array(
			'SMW\UpdateJob',
			false
		);

		$provider[] = array(
			'SMW\UpdateJob',
			true
		);

		$provider[] = array(
			'SMW\TempChangeOpPurgeJob',
			true
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			false,
			array( 'idlist' => '1|2' )
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			true,
			array( 'idlist' => '1|2' )
		);

		return $provider;
	}

	public function nullTitleProvider() {

		$provider[] = array(
			'dispatchParserCachePurgeJobWith'
		);

		$provider[] = array(
			'dispatchFulltextSearchTableUpdateJobWith'
		);

		$provider[] = array(
			'dispatchTempChangeOpPurgeJobWith'
		);

		return $provider;
	}

	public function preliminaryCheckProvider() {

		$provider[] = array(
			'UnknownJob'
		);

		return $provider;
	}

}
