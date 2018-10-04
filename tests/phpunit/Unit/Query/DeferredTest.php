<?php

namespace SMW\Tests\Query;

use SMW\Query\Deferred;

/**
 * @covers \SMW\Query\Deferred
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredTest extends \PHPUnit_Framework_TestCase {

	public function testRegisterResourceModules() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'addModuleStyles' );

		$parserOutput->expects( $this->once() )
			->method( 'addModules' );

		Deferred::registerResources( $parserOutput );
	}

	public function testBuildHTML() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertContains(
			'smw-deferred-query',
			Deferred::buildHTML( $query )
		);
	}

}
