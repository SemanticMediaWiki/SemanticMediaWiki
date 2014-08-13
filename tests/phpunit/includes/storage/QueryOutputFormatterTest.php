<?php

namespace SMW\Tests;

use SMW\QueryOutputFormatter;

use SMWQuery as Query;

/**
 * @covers \SMW\QueryOutputFormatter
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryOutputFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testFormatDebugOutputWithoutQuery() {

		$instance = new QueryOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->formatDebugOutput( 'foo', array(), null )
		);
	}

	public function testFormatDebugOutputWithQuery() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->formatDebugOutput( 'foo', array(), new Query( $description ) )
		);
	}

}
