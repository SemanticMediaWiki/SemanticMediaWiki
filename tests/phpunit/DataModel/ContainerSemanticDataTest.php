<?php

namespace SMW\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\Exception\DataItemException;

/**
 * @covers \SMW\DataModel\ContainerSemanticData
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ContainerSemanticDataTest extends TestCase {

	private $dataItemFactory;

	protected function setUp(): void {
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

		$this->expectException( DataItemException::class );
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
