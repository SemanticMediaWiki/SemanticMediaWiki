<?php

namespace SMW\Tests\Query;

use SMW\Query\DebugOutputFormatter;

/**
 * @covers \SMW\Query\DebugOutputFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DebugOutputFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testFormatDebugOutputWithoutQuery() {

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->formatOutputFor( 'foo', array(), null )
		);
	}

	public function testFormatDebugOutputWithQuery() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$query->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->formatOutputFor( 'foo', array(), $query )
		);
	}

}
