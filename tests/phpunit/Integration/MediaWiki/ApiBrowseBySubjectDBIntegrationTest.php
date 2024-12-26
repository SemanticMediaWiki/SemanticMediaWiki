<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\DataValueFactory;
use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\SerializerFactory;
use SMW\Subobject;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\MwApiFactory;
use SMW\Tests\Utils\SemanticDataFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ApiBrowseBySubjectDBIntegrationTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $apiFactory;
	private $dataValueFactory;
	private $serializerFactory;
	private $semanticDataFactory;

	protected function setUp(): void {
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

		$this->assertIsArray(

			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertIsArray(

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

		$this->assertIsArray(

			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertIsArray(

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

		$this->assertIsArray(

			$resultData
		);

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->serializerFactory->getDeserializerFor( $resultData['query'] )->deserialize( $resultData['query'] )
		);

		$this->assertIsArray(

			$this->newBrowseBySubject( __METHOD__, true )->getResultData()
		);
	}

	private function newBrowseBySubject( $subject, $asRawMode = false ) {
		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( [ 'subject' => $subject ] ),
			'browsebysubject'
		);

		$instance->execute();

		return $instance->getResult();
	}

}
