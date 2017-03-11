<?php

namespace SMW\TestsImporter;

use SMW\Importer\ContentsImporter;
use SMW\Importer\ImportContentsIterator;
use SMW\Importer\JsonImportContentsIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Importer\ImportContents;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\ContentsImporter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsImporterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $importContentsIterator;
	private $jsonImportContentsFileDirReader;
	private $pageCreator;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$this->jsonImportContentsFileDirReader = $this->getMockBuilder( JsonImportContentsFileDirReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->importContentsIterator = $this->getMockBuilder( ImportContentsIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ContentsImporter',
			new ContentsImporter( $this->importContentsIterator, $this->pageCreator )
		);
	}

	public function testDoImport() {

		$importContents = new ImportContents();

		$importContents->setName( 'Foo' );
		$importContents->setVersion( 1 );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( array( 'Foo' => array( $importContents ) ) ) );

		$this->pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $page ) );

		$instance = new ContentsImporter(
			new JsonImportContentsIterator( $this->jsonImportContentsFileDirReader ),
			$this->pageCreator
		);

		$instance->setMessageReporter( $this->messageReporter );
		$instance->setReqVersion( 1 );

		$instance->doImport();
	}

	public function testDoImportWithError() {

		$importContents = new ImportContents();

		$importContents->addError( 'Bar' );
		$importContents->setVersion( 1 );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( array( 'Foo' => array( $importContents ) ) ) );

		$this->pageCreator->expects( $this->never() )
			->method( 'createPage' );

		$instance = new ContentsImporter(
			new JsonImportContentsIterator( $this->jsonImportContentsFileDirReader ),
			$this->pageCreator
		);

		$instance->setMessageReporter( $this->messageReporter );
		$instance->setReqVersion( 1 );

		$instance->doImport();
	}

}
