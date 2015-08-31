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
	public function testDispatchFor( $type, $dispatchableAsyncUsageState ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new AsyncJobDispatchManager( $httpRequest );
		$instance->setDispatchableAsyncUsageState( $dispatchableAsyncUsageState );

		$this->assertTrue(
			$instance->dispatchJobFor( $type , DIWikiPage::newFromText( __METHOD__ )->getTitle() )
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
			'UnknownJob',
			false
		);

		$provider[] = array(
			'UnknownJob',
			true
		);

		return $provider;
	}

}
