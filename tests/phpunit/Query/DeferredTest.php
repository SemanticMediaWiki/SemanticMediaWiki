<?php

namespace SMW\Tests\Query;

use SMW\Query\Deferred;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\Deferred
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
