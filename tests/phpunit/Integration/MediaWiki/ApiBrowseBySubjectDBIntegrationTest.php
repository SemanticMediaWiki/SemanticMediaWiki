<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\MwApiFactory;
use SMW\Tests\Utils\SemanticDataFactory;

use SMW\MediaWiki\Api\BrowseBySubject;

use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DataValueFactory;
use SMW\Subobject;
use SMW\SerializerFactory;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ApiBrowseBySubjectDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $apiFactory;
	private $dataValueFactory;
	private $serializerFactory;
	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->serializerFactory = new SerializerFactory();
		$this->semanticDataFactory = new SemanticDataFactory();
	}

	public function testResultDataForEmptySemanticData() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$this->getStore()->updateData( $semanticData );

		$resultData = $this->newBrowseBySubject( __METHOD__ )->getResultData();

		$this->assertInternalType(
			'array',
			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	public function testResultDataForSingleSemanticDataValueAssignment() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyValue( __METHOD__ , 'Bar' )
		);

		$this->getStore()->updateData( $semanticData );

		$resultData = $this->newBrowseBySubject( __METHOD__ )->getResultData();

		$this->assertInternalType(
			'array',
			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	public function testResultDataFoSubobjectExtendedSemanticData() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyValue( __METHOD__ , 'Bar' )
		);

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( __METHOD__ , 'Bam' )
		);

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticData );

		$resultData = $this->newBrowseBySubject( __METHOD__ )->getResultData();

		$this->assertInternalType(
			'array',
			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	private function newBrowseBySubject( $subject, $asRawMode = false ) {

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( array( 'subject' => $subject ) ),
			'browsebysubject'
		);

		if ( $asRawMode ) {
			$instance->getMain()->getResult()->setRawMode();
		}

		$instance->execute();

		return $instance;
	}

}
