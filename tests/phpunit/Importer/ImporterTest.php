<?php

namespace SMW\Tests\Importer;

use SMW\Importer\ContentIterator;
use SMW\Importer\ImportContents;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Importer\Importer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImporterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $testEnvironment;
	private $contentIterator;
	private $jsonImportContentsFileDirReader;
	private $contentCreator;
	private $messageReporter;

	protected function setUp(): void {
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

		$this->testEnvironment = new TestEnvironment();

		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\Importer\Importer',
			new Importer( $this->contentIterator, $this->contentCreator )
		);
	}

	public function testDisabled() {
		$instance = new Importer(
			new JsonContentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->isEnabled( false );

		$instance->runImport();

		$this->assertContains(
			'Import support was not enabled (or skipped), stopping the task',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testRunImport() {
		$importContents = new ImportContents();

		$importContents->setName( 'Foo' );
		$importContents->setVersion( 1 );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContentList' )
			->will( $this->returnValue( [ 'Foo' => [ $importContents ] ] ) );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->contentCreator->expects( $this->atLeastOnce() )
			->method( 'create' );

		$instance = new Importer(
			new JsonContentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setReqVersion( 1 );

		$instance->runImport();
	}

	public function testRunImportWithError() {
		$importContents = new ImportContents();

		$importContents->addError( 'Bar' );
		$importContents->setVersion( 1 );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContentList' )
			->will( $this->returnValue( [ 'Foo' => [ $importContents ] ] ) );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ 'Error' ] ) );

		$this->contentCreator->expects( $this->never() )
			->method( 'create' );

		$instance = new Importer(
			new JsonContentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setReqVersion( 1 );

		$instance->runImport();
	}

	public function testRunImportWithErrorDuringCreation() {
		$importContents = new ImportContents();
		$importContents->setVersion( 1 );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContentList' )
			->will( $this->returnValue( [ 'Foo' => [ $importContents ] ] ) );

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->contentCreator->expects( $this->once() )
			->method( 'create' )
			->with( $this->callback( function ( $importContents ) {
					$importContents->addError( 'BarError from create' );
					$importContents->addError( [ 'Foo1', 'Foo2' ] );
					return true;
				}
			) );

		$instance = new Importer(
			new JsonContentIterator( $this->jsonImportContentsFileDirReader ),
			$this->contentCreator
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setReqVersion( 1 );

		$instance->runImport();

		$this->assertContains(
			'BarError from create',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
