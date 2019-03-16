<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DIProperty;
use SMW\Property\Annotators\CategoryPropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Property\Annotators\SortKeyPropertyAnnotator;
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
			$parameters['settings']['showHiddenCategories']
		);

		$categoryPropertyAnnotator->useCategoryInstance(
			$parameters['settings']['categoriesAsInstances']
		);

		$categoryPropertyAnnotator->useCategoryHierarchy(
			$parameters['settings']['categoryHierarchy']
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

		$provider = [];

		// #0
		$provider[] = [
			[
				'modificationDate' => 1272508903,
				'categories' => [ 'Foo', 'Bar' ],
				'sortkey'    => 'Lala',
				'settings'   => [
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => true,
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MODIFICATION_DATE ]
				]
			],
			[
				'propertyCount'  => 3,
				'propertyKeys'   => [ '_INST', '_MDAT', '_SKEY' ],
				'propertyValues' => [ 'Foo',  'Bar', '2010-04-29T02:41:43', 'Lala' ],
			]
		];

		return $provider;
	}

}
