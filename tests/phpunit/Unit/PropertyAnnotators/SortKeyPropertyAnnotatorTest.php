<?php

namespace SMW\Tests\PropertyAnnotators;

use SMW\DataItemFactory;
use SMW\PropertyAnnotators\NullPropertyAnnotator;
use SMW\PropertyAnnotators\SortKeyPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\PropertyAnnotators\SortKeyPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SortKeyPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'Foo'
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\SortKeyPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider defaultSortDataProvider
	 */
	public function testAddAnnotation( array $parameters, array $expected ) {

		$semanticData = $this->semanticDataFactory->setTitle( $parameters['title'] )->newEmptySemanticData();

		$instance = new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['sort']
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testDontOverrideAnnotationIfAlreadyAvailable() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( '_SKEY' ),
			$this->dataItemFactory->newDIBlob( 'FOO' )
		);

		$instance = new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'bar'
		);

		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => '_SKEY',
			'propertyValues' => array( 'FOO' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function defaultSortDataProvider() {

		$provider = array();

		// Sort entry
		$provider[] = array(
			array(
				'title' => 'Foo',
				'sort'  => 'Lala'
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( 'Lala' ),
			)
		);

		// Empty
		$provider[] = array(
			array(
				'title' => 'Bar',
				'sort'  => ''
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( 'Bar' ),
			)
		);

		return $provider;
	}

}
