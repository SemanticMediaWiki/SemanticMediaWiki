<?php

namespace SMW\Tests;

use SMW\AsyncJobDispatchManager;
use SMW\DIWikiPage;

/**
 * @covers \SMW\AsyncJobDispatchManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class AsyncJobDispatchManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\AsyncJobDispatchManager',
			new AsyncJobDispatchManager( $httpRequest )
		);
	}

	/**
	 * @dataProvider dispatchableJobProvider
	 */
	public function testDispatchFor( $type, $dispatchableAsyncUsageState, $parameters = array() ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new AsyncJobDispatchManager( $httpRequest );
		$instance->reset();
		$instance->setDispatchableAsyncUsageState( $dispatchableAsyncUsageState );

		$this->assertTrue(
			$instance->dispatchJobFor( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
		);
	}

	/**
	 * @dataProvider preliminaryCheckProvider
	 */
	public function testPreliminaryCheckForType( $type, $parameters = array() ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new AsyncJobDispatchManager( $httpRequest );
		$instance->reset();

		$this->assertNull(
			$instance->dispatchJobFor( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
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

	public function preliminaryCheckProvider() {

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			array()
		);


		$provider[] = array(
			'UnknownJob'
		);

		return $provider;
	}

}
