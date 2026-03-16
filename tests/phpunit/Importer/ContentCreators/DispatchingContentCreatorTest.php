<?php

namespace SMW\Tests\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentCreator;
use SMW\Importer\ContentCreators\DispatchingContentCreator;
use SMW\Importer\ImportContents;

/**
 * @covers \SMW\Importer\ContentCreators\DispatchingContentCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DispatchingContentCreatorTest extends TestCase {

	private $wikiImporter;
	private $messageReporter;

	protected function setUp(): void {
		parent::setUp();

		$importStreamSource = $this->getMockBuilder( '\ImportStreamSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DispatchingContentCreator::class,
			new DispatchingContentCreator( [] )
		);
	}

	public function testCanCreateContentsFor() {
		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( ContentCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->with( $importContents )
			->willReturn( true );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$this->assertTrue(
			$instance->canCreateContentsFor( $importContents )
		);
	}

	public function testDoCreateFrom() {
		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( ContentCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->willReturn( true );

		$contentCreator->expects( $this->any() )
			->method( 'create' )
			->with( $importContents )
			->willReturn( true );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$this->assertTrue(
			$instance->create( $importContents )
		);
	}

	public function testDoCreateFromOnNonMatchableCreatorThrowsException() {
		$importContents = new ImportContents();
		$importContents->setContentType( 'Foo' );

		$contentCreator = $this->getMockBuilder( ContentCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$contentCreator->expects( $this->any() )
			->method( 'canCreateContentsFor' )
			->willReturn( false );

		$contentCreator->expects( $this->never() )
			->method( 'create' );

		$instance = new DispatchingContentCreator(
			[
				$contentCreator
			]
		);

		$this->expectException( 'RuntimeException' );
		$instance->create( $importContents );
	}

}
