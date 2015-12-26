<?php

namespace SMW\Tests;

use SMW\SchemaReader;

/**
 * @covers \SMW\SchemaReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaReaderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SchemaReader',
			new SchemaReader( $mediaWikiNsContentReader )
		);
	}

	public function testRead() {

		$expected = array(
			'bar' => array( 'abc' )
		);

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'foo' ) )
			->will( $this->returnValue( json_encode( $expected ) ) );

		$instance = new SchemaReader( $mediaWikiNsContentReader );
		$instance->registerSchema( 'foo' );

		$this->assertInternalType(
			'array',
			$instance->read( 'BAR' )
		);

		// Cached therefore mocked read is called only once
		$this->assertInternalType(
			'array',
			$instance->read( ' bAr ' )
		);
	}

	public function testTryToReadInvalidJsonThrowsRuntimeException() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'foo' ) )
			->will( $this->returnValue( 'InvalidJSON' ) );

		$instance = new SchemaReader( $mediaWikiNsContentReader );
		$instance->clear();
		$instance->registerSchema( 'foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->read( 'bar' );
	}

}
