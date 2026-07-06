<?php

namespace SMW\Tests\Unit\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Concept;
use SMW\DataItems\WikiPage;
use SMW\Export\ExpData;
use SMW\Exporter\ConceptMapper;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Query\DescriptionFactory;

/**
 * @covers \SMW\Exporter\ConceptMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class ConceptMapperTest extends TestCase {

	private $descriptionFactory;

	protected function setUp(): void {
		$this->descriptionFactory = new DescriptionFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptMapper::class,
			new ConceptMapper()
		);
	}

	public function testIsMapperFor() {
		$dataItem = $this->getMockBuilder( Concept::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptMapper();

		$this->assertTrue(
			$instance->isMapperFor( $dataItem )
		);
	}

	public function testGetExpDataForSingleClassDescription() {
		$instance = new ConceptMapper();

		$exact = false;

		$description = $this->descriptionFactory->newClassDescription(
			WikiPage::newFromText( 'Foo', NS_CATEGORY )
		);

		$element = new ExpNsResource(
			'type',
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
			'rdf'
		);

		$result = $instance->newExpDataFromDescription(
			$description,
			$exact
		);

		$this->assertInstanceOf(
			ExpData::class,
			$result
		);

		$this->assertEquals(
			[
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' => $element
			],
			$result->getProperties()
		);
	}

	public function testGetExpDataForMultipleClassDescriptions() {
		$instance = new ConceptMapper();

		$exact = false;

		$description = $this->descriptionFactory->newClassDescription(
			WikiPage::newFromText( 'Foo', NS_CATEGORY )
		);

		$description->addDescription(
			$this->descriptionFactory->newClassDescription(
				WikiPage::newFromText( 'Bar', NS_CATEGORY )
			)
		);

		$elementType = new ExpNsResource(
			'type',
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
			'rdf'
		);

		$elementUnionOf = new ExpNsResource(
			'unionOf',
			'http://www.w3.org/2002/07/owl#',
			'owl'
		);

		$result = $instance->newExpDataFromDescription(
			$description,
			$exact
		);

		$this->assertEquals(
			[
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' => $elementType,
				'http://www.w3.org/2002/07/owl#unionOf'           => $elementUnionOf
			],
			$result->getProperties()
		);
	}

	public function testGetExpDataForThingDescription() {
		$instance = new ConceptMapper();

		$exact = false;

		$description = $this->descriptionFactory->newThingDescription();

		$result = $instance->newExpDataFromDescription(
			$description,
			$exact
		);

		$this->assertFalse(
			$result
		);
	}

}
