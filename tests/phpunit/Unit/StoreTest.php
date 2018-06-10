<?php

namespace SMW\Tests;

use SMW\DIWikiPage;

/**
 * @covers \SMW\Store
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class StoreTest extends \PHPUnit_Framework_TestCase {

	public function testGetRedirectTarget() {

		$wikipage = new DIWikiPage( 'Foo', NS_MAIN );
		$expected = new DIWikiPage( 'Bar', NS_MAIN );

		$instance = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$instance->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $expected ] ) );

		$this->assertEquals(
			$expected,
			$instance->getRedirectTarget( $wikipage )
		);
	}

}
