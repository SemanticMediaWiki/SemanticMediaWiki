<?php

namespace SMW\TestsImporter\ContentCreators;

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

	private $importServicesFactory;
	private $wikiImporter;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$importStreamSource = $this->getMockBuilder( '\ImportStreamSource' )
			->disableOriginalConstructor()
			->getMock();

		$this->wikiImporter = $this->getMockBuilder( '\WikiImporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->importServicesFactory = $this->getMockBuilder( '\SMW\Services\ImportServicesFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->importServicesFactory->expects( $this->any() )
			->method( 'newImportStreamSource' )
			->will( $this->returnValue( $importStreamSource ) );

		$this->importServicesFactory->expects( $this->any() )
			->method( 'newWikiImporter' )
			->will( $this->returnValue( $this->wikiImporter ) );

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ContentCreators\XmlContentCreator',
			new XmlContentCreator( $this->importServicesFactory )
		);
	}

	public function testCanCreateContentsFor() {

		$instance = new XmlContentCreator(
			$this->importServicesFactory
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
			$this->importServicesFactory
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$importContents = new ImportContents();
		$importContents->setContentType( ImportContents::CONTENT_XML );
		$importContents->setContentsFile( 'Foo' );

		$instance->doCreateFrom( $importContents );
	}

}
