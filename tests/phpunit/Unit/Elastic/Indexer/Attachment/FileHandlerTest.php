<?php

namespace SMW\Tests\Unit\Elastic\Indexer\Attachment;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use RepoGroup;
use RuntimeException;
use SMW\Elastic\Indexer\Attachment\FileHandler;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\FileHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FileHandlerTest extends TestCase {

	private $repoGroup;

	protected function setUp(): void {
		$this->repoGroup = $this->getMockBuilder( RepoGroup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileHandler::class,
			new FileHandler( $this->repoGroup )
		);
	}

	public function testFindFileByTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->repoGroup->expects( $this->once() )
			->method( 'findFile' );

		$instance = new FileHandler(
			$this->repoGroup
		);

		$instance->findFileByTitle( $title );
	}

	public function testBase64FromURI() {
		$url = 'http://example.org/Foo.txt';

		$instance = new FileHandler(
			$this->repoGroup
		);

		$instance->setReadCallback( static function ( $read_url ) use( $url ) {
			if ( $read_url !== $url ) {
				throw new RuntimeException( "Invalid read URL!" );
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
			$this->repoGroup
		);

		$this->assertEquals(
			'Foo',
			$instance->format( 'Foo' )
		);
	}

	public function testFormat_base64() {
		$instance = new FileHandler(
			$this->repoGroup
		);

		$this->assertEquals(
			base64_encode( 'Foo' ),
			$instance->format( 'Foo', FileHandler::FORMAT_BASE64 )
		);
	}

}
