<?php

namespace SMW\Tests\Unit\MediaWiki;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\LinkBatch;

/**
 * @covers \SMW\MediaWiki\LinkBatch
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class LinkBatchTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			LinkBatch::class,
			 new LinkBatch()
		);
	}

	public function testCanConstructSingleton() {
		$instance = LinkBatch::singleton();

		$this->assertSame(
			$instance,
			LinkBatch::singleton()
		);

		$instance->reset();
	}

	public function testAdd_NoPage() {
		$instance = new LinkBatch();
		$instance->add( new Blob( 'Foo' ) );

		$this->assertFalse(
			$instance->has( new Blob( 'Foo' ) )
		);
	}

	public function testAdd_PageButRefuseFirstUnderscore() {
		$subject = WikiPage::newFromText( '_ASK' );

		$instance = new LinkBatch();
		$instance->add( $subject );

		$this->assertFalse(
			$instance->has( $subject )
		);
	}

	public function testExecute() {
		$subject = WikiPage::newFromText( 'Foo' );

		$linkBatch = $this->getMockBuilder( '\LinkBatch' )
			->disableOriginalConstructor()
			->getMock();

		$linkBatch->expects( $this->exactly( 3 ) )
			->method( 'add' );

		$linkBatch->expects( $this->once() )
			->method( 'execute' );

		$instance = new LinkBatch( $linkBatch );

		$instance->addFromList(
			[
				WikiPage::newFromText( 'Foo/1' ),
				WikiPage::newFromText( 'Foo/2' ),
				$subject
			]
		);

		$instance->add( $subject );

		$instance->execute();
	}

}
