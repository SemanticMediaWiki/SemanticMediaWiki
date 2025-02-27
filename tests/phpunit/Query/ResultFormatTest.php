<?php

namespace SMW\Tests\Query;

use SMW\Query\ResultFormat;

/**
 * @covers \SMW\Query\ResultFormat
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ResultFormatTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultFormat::class,
			new ResultFormat( 'type', 'foo' )
		);
	}

	public function testResolveFormatAliases() {
		foreach ( $GLOBALS['smwgResultAliases'] as $mainFormat => $aliases ) {
			foreach ( $aliases as $alias ) {
				$this->assertTrue(
					ResultFormat::resolveFormatAliases( $alias )
				);
			}
		}
	}

}
