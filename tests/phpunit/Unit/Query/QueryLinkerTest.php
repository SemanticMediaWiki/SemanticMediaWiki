<?php

namespace SMW\Tests\Query;

use SMW\Query\QueryLinker;

/**
 * @covers SMW\Query\QueryLinker
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryLinkerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\QueryLinker',
			new QueryLinker()
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
			QueryLinker::get( $query )
		);
	}

}
