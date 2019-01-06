<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\DataValueFactory;
use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\SerializerFactory;
use SMW\Subobject;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\MwApiFactory;
use SMW\Tests\Utils\SemanticDataFactory;

/**
 * @group semantic-mediawiki-integration
 * @group medium
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
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	public function testResultDataForSingleSemanticDataValueAssignment() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByText( __METHOD__, 'Bar' )
		);

		$this->getStore()->updateData( $semanticData );

		$resultData = $this->newBrowseBySubject( __METHOD__ )->getResultData();

		$this->assertInternalType(
			'array',
			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	public function testResultDataFoSubobjectExtendedSemanticData() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByText( __METHOD__, 'Bar' )
		);

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newDataValueByText( __METHOD__, 'Bam' )
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
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertInternalType(
			'array',
			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	private function newBrowseBySubject( $subject, $asRawMode = false ) {

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( [ 'subject' => $subject ] ),
			'browsebysubject'
		);

		// Went away with 1.26/1.27
		if ( function_exists( 'setRawMode' ) && $asRawMode ) {
			$instance->getMain()->getResult()->setRawMode();
		}

		$instance->execute();

		// MW 1.25
		return method_exists( $instance, 'getResult' ) ? $instance->getResult() : $instance;
	}

}
