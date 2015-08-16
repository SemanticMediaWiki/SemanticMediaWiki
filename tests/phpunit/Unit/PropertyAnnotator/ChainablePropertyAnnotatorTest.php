<?php

namespace SMW\Tests\PropertyAnnotator;

use SMW\Tests\Utils\UtilityFactory;
use SMW\PropertyAnnotator\PredefinedPropertyAnnotator;
use SMW\PropertyAnnotator\CategoryPropertyAnnotator;
use SMW\PropertyAnnotator\SortkeyPropertyAnnotator;
use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\DIProperty;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ChainablePropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	/**
	 * @dataProvider annotationDataProvider
	 */
	public function testChainableDecoratorAnnotation( array $parameters, array $expected ) {

		$pageInfoProvider = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider->expects( $this->atLeastOnce() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( $parameters['modificationDate'] ) );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$categoryPropertyAnnotator = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['categories']
		);

		$categoryPropertyAnnotator->setShowHiddenCategoriesState(
			$parameters['settings']['smwgShowHiddenCategories']
		);

		$categoryPropertyAnnotator->setCategoryInstanceUsageState(
			$parameters['settings']['smwgCategoriesAsInstances']
		);

		$categoryPropertyAnnotator->setCategoryHierarchyUsageState(
			$parameters['settings']['smwgUseCategoryHierarchy']
		);

		$sortKeyPropertyAnnotator = new SortKeyPropertyAnnotator(
			$categoryPropertyAnnotator,
			$parameters['sortkey']
		);

		$predefinedPropertyAnnotator = new PredefinedPropertyAnnotator(
			$sortKeyPropertyAnnotator,
			$pageInfoProvider
		);

		$predefinedPropertyAnnotator->setPredefinedPropertyList(
			$parameters['settings']['smwgPageSpecialProperties']
		);

		$predefinedPropertyAnnotator->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$predefinedPropertyAnnotator->getSemanticData()
		);
	}

	public function annotationDataProvider() {

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'modificationDate' => 1272508903,
				'categories' => array( 'Foo', 'Bar' ),
				'sortkey'    => 'Lala',
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => true,
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				)
			),
			array(
				'propertyCount'  => 3,
				'propertyKeys'   => array( '_INST', '_MDAT', '_SKEY' ),
				'propertyValues' => array( 'Foo',  'Bar', '2010-04-29T02:41:43', 'Lala' ),
			)
		);

		return $provider;
	}

}
