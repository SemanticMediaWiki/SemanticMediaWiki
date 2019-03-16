<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataItemFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\SchemaPropertyAnnotator;
use SMW\Schema\SchemaDefinition;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\SchemaPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $testEnvironment->newValidatorFactory()->newSemanticDataValidator();
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			new SchemaDefinition( 'foo', [] )
		);

		$this->assertInstanceOf(
			SchemaPropertyAnnotator::class,
			$instance
		);
	}

	public function testAddAnnotation() {

		$def = [
			SchemaDefinition::SCHEMA_TYPE => 'bar',
			SchemaDefinition::SCHEMA_DESCRIPTION => '...',
			SchemaDefinition::SCHEMA_TAG => [ 'foobar' ],
		];

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new SchemaPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			new SchemaDefinition( 'foo', $def )
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 4,
			'propertyKeys'   => [ '_SCHEMA_TYPE', '_SCHEMA_DEF', '_SCHEMA_DESC', '_SCHEMA_TAG' ],
			'propertyValues' => [ 'bar', '...', 'foobar', '{"type":"bar","description":"...","tags":["foobar"]}' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

}
