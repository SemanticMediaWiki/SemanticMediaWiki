<?php

namespace SMW\Tests\DataModel;

use SMW\DataItemFactory;
use SMW\DataModel\ContainerSemanticData;
use SMW\SemanticData;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataModel\ContainerSemanticData
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContainerSemanticDataTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ContainerSemanticData::class,
			new ContainerSemanticData( $this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN ) )
		);
	}

	public function testMakeAnonymousContainer() {

		$instance = ContainerSemanticData::makeAnonymousContainer();
		$instance->skipAnonymousCheck();

		$this->assertInstanceOf(
			ContainerSemanticData::class,
			$instance
		);

		$this->assertTrue(
			$instance->hasAnonymousSubject()
		);
	}

	public function testGetSubjectOnAnonymousContainerWithoutSkipThrowsException() {

		$instance = ContainerSemanticData::makeAnonymousContainer();

		$this->setExpectedException( '\SMW\Exception\DataItemException' );
		$instance->getSubject();
	}

	public function testCopyDataFrom() {

		$subject = $this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN );

		$semanticData = new SemanticData(
			$subject
		);

		$instance = ContainerSemanticData::makeAnonymousContainer( true, true );

		$this->assertNotEquals(
			$subject,
			$instance->getSubject()
		);

		$instance->copyDataFrom( $semanticData );

		$this->assertEquals(
			$subject,
			$instance->getSubject()
		);
	}

}
