<?php

namespace SMW\Tests\Importer\ContentCreators;

use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ImportContents;

/**
 * @covers \SMW\Importer\ContentCreators\XmlContentCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class XmlContentCreatorTest extends \PHPUnit_Framework_TestCase {

	private $importerServiceFactory;
	private $wikiImporter;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		if ( !interface_exists( '\ImportSource' ) ) {
			$this->markTestSkipped( "ImportSource interface is unknown (MW 1.25-)" );
		}

		$importStreamSource = $this->getMockBuilder( '\ImportStreamSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->importerServiceFactory = $this->getMockBuilder( '\SMW\Services\ImporterServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->importerServiceFactory->expects( $this->any() )
			->method( 'newImportStreamSource' )
			->will( $this->returnValue( $importStreamSource ) );

		$this->importerServiceFactory->expects( $this->any() )
			->method( 'newWikiImporter' )
			->will( $this->returnValue( $this->wikiImporter ) );

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ContentCreators\XmlContentCreator',
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
