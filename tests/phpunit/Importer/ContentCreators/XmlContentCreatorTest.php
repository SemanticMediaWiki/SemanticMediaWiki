<?php

namespace SMW\Tests\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ImportContents;
use SMW\Services\ImporterServiceFactory;

/**
 * @covers \SMW\Importer\ContentCreators\XmlContentCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class XmlContentCreatorTest extends TestCase {

	private $importerServiceFactory;
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

		$this->importerServiceFactory = $this->getMockBuilder( ImporterServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->importerServiceFactory->expects( $this->any() )
			->method( 'newImportStreamSource' )
			->willReturn( $importStreamSource );

		$this->importerServiceFactory->expects( $this->any() )
			->method( 'newWikiImporter' )
			->willReturn( $this->wikiImporter );

		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			XmlContentCreator::class,
			new XmlContentCreator( $this->importerServiceFactory )
		);
	}

	public function testCanCreateContentsFor() {
		$instance = new XmlContentCreator(
			$this->importerServiceFactory
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_XML );

		$this->assertTrue(
			$instance->canCreateContentsFor( $importContents )
		);
	}

	public function testDoCreateFrom() {
		$this->wikiImporter->expects( $this->atLeastOnce() )
			->method( 'doImport' );

		$instance = new XmlContentCreator(
			$this->importerServiceFactory
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_XML );
		$importContents->setContentsFile( 'Foo' );

		$instance->create( $importContents );
	}

}
