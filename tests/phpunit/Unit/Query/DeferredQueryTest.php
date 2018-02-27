<?php

namespace SMW\Tests\Query;

use SMW\Query\DeferredQuery;

/**
 * @covers \SMW\Query\DeferredQuery
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredQueryTest extends \PHPUnit_Framework_TestCase {

	public function testRegisterResourceModules() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'addModuleStyles' );

		$parserOutput->expects( $this->once() )
			->method( 'addModules' );

		DeferredQuery::registerResourceModules( $parserOutput );
	}

	public function testGetHtml() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertContains(
			'smw-deferred-query',
			DeferredQuery::getHtml( $query )
		);
	}

}
