<?php

namespace SMW\TestsImporter;

use SMW\Importer\Importer;
use SMW\Importer\ContentIterator;
use SMW\Importer\JsoncontentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Importer\ImportContents;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\Importer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImporterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $contentIterator;
	private $jsonImportContentsFileDirReader;
	private $contentCreator;
	private $messageReporter;

	protected function setUp() {
		parent::setUp();

		$this->jsonImportContentsFileDirReader = $this->getMockBuilder( JsonImportContentsFileDirReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->contentIterator = $this->getMockBuilder( ContentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->contentCreator = $this->getMockBuilder( '\SMW\Importer\ContentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\Importer',
			new Importer( $this->contentIterator, $this->contentCreator )
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
			->method( 'getContentList' )
			->will( $this->returnValue( array( 'Foo' => array( $importContents ) ) ) );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$this->contentCreator->expects( $this->atLeastOnce() )
			->method( 'doCreateFrom' );

		$instance = new Importer(
			new JsoncontentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
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
			->method( 'getContentList' )
			->will( $this->returnValue( array( 'Foo' => array( $importContents ) ) ) );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( array( 'Error' ) ) );

		$this->contentCreator->expects( $this->never() )
			->method( 'doCreateFrom' );

		$instance = new Importer(
			new JsoncontentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
		);

		$instance->setMessageReporter( $this->messageReporter );
		$instance->setReqVersion( 1 );

		$instance->doImport();
	}

}
