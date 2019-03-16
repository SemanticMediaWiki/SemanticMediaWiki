<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataItemFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\SortKeyPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Property\Annotators\SortKeyPropertyAnnotator
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
			'\SMW\Property\Annotators\SortKeyPropertyAnnotator',
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

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => '_SKEY',
			'propertyValues' => [ 'FOO' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function defaultSortDataProvider() {

		$provider = [];

		// Sort entry
		$provider[] = [
			[
				'title' => 'Foo',
				'sort'  => 'Lala'
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => [ 'Lala' ],
			]
		];

		// Empty
		$provider[] = [
			[
				'title' => 'Bar',
				'sort'  => ''
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => [ 'Bar' ],
			]
		];

		return $provider;
	}

}
