<?php

namespace SMW\Tests\Elastic\Indexer\Attachment;

use SMW\Elastic\Indexer\Attachment\FileHandler;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\FileHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FileHandlerTest extends \PHPUnit_Framework_TestCase {

	private $fileRepoFinder;

	protected function setUp() : void {

		$this->fileRepoFinder = $this->getMockBuilder( '\SMW\MediaWiki\FileRepoFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FileHandler::class,
			new FileHandler( $this->fileRepoFinder )
		);
	}

	public function testFindFileByTitle() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileRepoFinder->expects( $this->once() )
			->method( 'findFile' );

		$instance = new FileHandler(
			$this->fileRepoFinder
		);

		$instance->findFileByTitle( $title );
	}

	public function testBase64FromURI() {

		$url = 'http://example.org/Foo.txt';

		$instance = new FileHandler(
			$this->fileRepoFinder
		);

		$instance->setReadCallback( function( $read_url ) use( $url ) {

			if ( $read_url !== $url ) {
				throw new \RuntimeException( "Invalid read URL!" );
			}

			return 'FooUrl';
		} );

		$this->assertEquals(
			'FooUrl',
			$instance->fetchContentFromURL( $url )
		);
	}

	public function testFormat() {

		$instance = new FileHandler(
			$this->fileRepoFinder
		);

		$this->assertEquals(
			'Foo',
			$instance->format( 'Foo' )
		);
	}

	public function testFormat_base64() {

		$instance = new FileHandler(
			$this->fileRepoFinder
		);

		$this->assertEquals(
			base64_encode( 'Foo' ),
			$instance->format( 'Foo', FileHandler::FORMAT_BASE64 )
		);
	}

}
