<?php

namespace SMW\Tests\Exporter;

use SMW\DIWikiPage;
use SMW\Exporter\ConceptMapper;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Query\DescriptionFactory;

/**
 * @covers \SMW\Exporter\ConceptMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ConceptMapperTest extends \PHPUnit_Framework_TestCase {

	private $descriptionFactory;

	protected function setUp() {
		$this->descriptionFactory = new DescriptionFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConceptMapper::class,
			new ConceptMapper()
		);
	}

	public function testIsMapperFor() {

		$dataItem = $this->getMockBuilder( '\SMWDIConcept' )
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
			DIWikiPage::newFromText( 'Foo', NS_CATEGORY )
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
			'\SMWExpData',
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
			DIWikiPage::newFromText( 'Foo', NS_CATEGORY )
		);

		$description->addDescription(
			$this->descriptionFactory->newClassDescription(
				DIWikiPage::newFromText( 'Bar', NS_CATEGORY )
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
