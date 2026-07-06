<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Cache\LinkBatch as MwLinkBatch;
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

	public function testAdd_NoPage() {
		$linkBatch = $this->getMockBuilder( MwLinkBatch::class )
			->disableOriginalConstructor()
			->getMock();

		$linkBatch->expects( $this->never() )
			->method( 'add' );

		$linkBatch->expects( $this->never() )
			->method( 'execute' );

		$instance = new LinkBatch( $linkBatch );
		$instance->add( new Blob( 'Foo' ) );
		$instance->execute();
	}

	public function testAdd_PageButRefuseFirstUnderscore() {
		$linkBatch = $this->getMockBuilder( MwLinkBatch::class )
			->disableOriginalConstructor()
			->getMock();

		$linkBatch->expects( $this->never() )
			->method( 'add' );

		$linkBatch->expects( $this->never() )
			->method( 'execute' );

		$instance = new LinkBatch( $linkBatch );
		$instance->add( WikiPage::newFromText( '_ASK' ) );
		$instance->execute();
	}

	public function testExecute() {
		$subject = WikiPage::newFromText( 'Foo' );

		$linkBatch = $this->getMockBuilder( MwLinkBatch::class )
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
