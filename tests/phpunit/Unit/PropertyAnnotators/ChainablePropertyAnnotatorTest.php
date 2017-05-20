<?php

namespace SMW\Tests\PropertyAnnotators;

use SMW\DIProperty;
use SMW\PropertyAnnotators\CategoryPropertyAnnotator;
use SMW\PropertyAnnotators\NullPropertyAnnotator;
use SMW\PropertyAnnotators\PredefinedPropertyAnnotator;
use SMW\PropertyAnnotators\SortKeyPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

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

		$categoryPropertyAnnotator->showHiddenCategories(
			$parameters['settings']['smwgShowHiddenCategories']
		);

		$categoryPropertyAnnotator->useCategoryInstance(
			$parameters['settings']['smwgCategoriesAsInstances']
		);

		$categoryPropertyAnnotator->useCategoryHierarchy(
			$parameters['settings']['smwgUseCategoryHierarchy']
		);

		$categoryPropertyAnnotator->useCategoryRedirect(
			false
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
