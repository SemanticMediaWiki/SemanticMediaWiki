<?php

namespace SMW\Tests\Query;

use SMW\Query\QueryLink as QueryLink;

/**
 * @covers SMW\Query\QueryLink
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryLinkTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\QueryLink',
			new QueryLink()
		);
	}

	public function testGet() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getExtraPrintouts' )
			->will( $this->returnValue( array() ) );

		$this->assertInstanceOf(
			'SMWInfolink',
			QueryLink::get( $query )
		);
	}

}
