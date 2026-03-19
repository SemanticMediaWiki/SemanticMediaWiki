<?php

namespace SMW\Tests\Query;

use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use SMW\Query\Deferred;
use SMW\Query\Query;

/**
 * @covers \SMW\Query\Deferred
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredTest extends TestCase {

	public function testRegisterResourceModules() {
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'addModuleStyles' );

		$parserOutput->expects( $this->once() )
			->method( 'addModules' );

		Deferred::registerResources( $parserOutput );
	}

	public function testBuildHTML() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertStringContainsString(
			'smw-deferred-query',
			Deferred::buildHTML( $query )
		);
	}

}
