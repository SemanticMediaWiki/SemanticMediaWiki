<?php

namespace SMW\Tests\Annotator;

use SMW\Tests\Utils\Validators\SemanticDataValidator;
use SMW\Tests\Utils\SemanticDataFactory;

use SMW\Annotator\PredefinedPropertyAnnotator;
use SMW\Annotator\CategoryPropertyAnnotator;
use SMW\Annotator\SortkeyPropertyAnnotator;
use SMW\Annotator\NullPropertyAnnotator;
use SMW\DIProperty;
use SMW\Settings;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\Annotator\PredefinedPropertyAnnotator
 * @covers \SMW\Annotator\CategoryPropertyAnnotator
 * @covers \SMW\Annotator\SortkeyPropertyAnnotator
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ChainablePropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = new SemanticDataFactory();
		$this->semanticDataValidator = new SemanticDataValidator();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
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

		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $parameters['settings'] )
		);

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['categories']
		);

		$instance = new SortKeyPropertyAnnotator(
			$instance,
			$parameters['sortkey']
		);

		$instance = new PredefinedPropertyAnnotator(
			$instance,
			$pageInfoProvider
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
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
