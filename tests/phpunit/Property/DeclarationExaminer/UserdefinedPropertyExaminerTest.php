<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use ExtensionRegistry;
use SMW\DataItemFactory;
use SMW\Property\DeclarationExaminer\UserdefinedPropertyExaminer;
use SMW\SemanticData;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminer\UserdefinedPropertyExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class UserdefinedPropertyExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $declarationExaminer;
	private $semanticData;
	private $store;
	private $propertyTableInfoFetcher;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$this->propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $this->propertyTableInfoFetcher );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UserdefinedPropertyExaminer::class,
			new UserdefinedPropertyExaminer( $this->declarationExaminer, $this->store )
		);
	}

	public function testIsFixedTable() {
		$this->propertyTableInfoFetcher->expects( $this->any() )
			->method( 'isFixedTableProperty' )
			->willReturn( true );

		$dataItemFactory = new DataItemFactory();

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'["info","smw-property-userdefined-fixedtable","Foo"]',
			$instance->getMessagesAsString()
		);
	}

	/**
	 * @dataProvider recordTypeProvider
	 */
	public function testRecordType_FieldList( $type, $name ) {
		$dataItemFactory = new DataItemFactory();

		$this->semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $dataItemFactory->newDIProperty( '_LIST' ) )
			->willReturn( [] );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyValueType( $type );

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-missing-fields","Foo","' . $name . '"]',
			$instance->getMessagesAsString()
		);
	}

	public function testIgnoreRecordType_MonolingualText() {
		$dataItemFactory = new DataItemFactory();

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyValueType( '_mlt_rec' );

		$instance->check(
			$property
		);

		$this->assertNotContains(
			'smw-property-req-violation-missing-fields',
			$instance->getMessagesAsString()
		);
	}

	/**
	 * @dataProvider recordTypeProvider
	 */
	public function testRecordType_MultipleFieldList( $type, $name ) {
		$dataItemFactory = new DataItemFactory();

		$this->semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $dataItemFactory->newDIProperty( '_LIST' ) )
			->willReturn(
				[
					$dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY ),
					$dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_PROPERTY )
				] );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyValueType( $type );

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-multiple-fields","Foo","' . $name . '"]',
			$instance->getMessagesAsString()
		);
	}

	public function testExternalIdentifier_MissingFormatter() {
		$dataItemFactory = new DataItemFactory();

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyValueType( '_eid' );

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-missing-formatter-uri","Foo"]',
			$instance->getMessagesAsString()
		);
	}

	public function testGeoType_MissingMapsExtension() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Maps' ) ) {
			$this->markTestSkipped( 'Skipping test because the Maps extension is installed!' );
		}

		$dataItemFactory = new DataItemFactory();

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyValueType( '_geo' );

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-missing-maps-extension","Foo"]',
			$instance->getMessagesAsString()
		);
	}

	public function testImportTypeDeclarationMismatch() {
		$dataItemFactory = new DataItemFactory();
		$imported_type = $dataItemFactory->newDIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_num' );
		$user_type = $dataItemFactory->newDIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_dat' );

		$this->semanticData->expects( $this->at( 0 ) )
			->method( 'hasProperty' )
			->with( $dataItemFactory->newDIProperty( '_IMPO' ) )
			->willReturn( true );

		$this->semanticData->expects( $this->any() )
			->method( 'getOption' )
			->with( \SMW\Property\Annotators\MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE )
			->willReturn( $imported_type );

		$this->semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $user_type ] );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$instance = new UserdefinedPropertyExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'["warning","smw-property-req-violation-import-type","Foo"]',
			$instance->getMessagesAsString()
		);
	}

	public function testCheckSubpropertyParentTypeMismatch_ForcedInheritance() {
		$declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$dataItemFactory = new DataItemFactory();

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$semanticData = new SemanticData(
			$property->getDIWikiPage()
		);

		$semanticData->setOption(
			\SMW\Property\Annotators\MandatoryTypePropertyAnnotator::ENFORCED_PARENTTYPE_INHERITANCE,
			$dataItemFactory->newDIWikiPage( 'Bar' )
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_SUBP' ),
			$dataItemFactory->newDIWikiPage( 'Parent' )
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_TYPE' ),
			$dataItemFactory->newDIProperty( 'Bar' )
		);

		$declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$instance = new UserdefinedPropertyExaminer(
			$declarationExaminer,
			$this->store
		);

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-forced-removal-annotated-type","Foo","Bar"]',
			$instance->getMessagesAsString()
		);
	}

	public function testCheckSubpropertyParentTypeMismatch() {
		$declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$dataItemFactory = new DataItemFactory();

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$semanticData = new SemanticData(
			$property->getDIWikiPage()
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_SUBP' ),
			$dataItemFactory->newDIWikiPage( 'Parent' )
		);

		$declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$instance = new UserdefinedPropertyExaminer(
			$declarationExaminer,
			$this->store
		);

		$instance->check(
			$property
		);

		$this->assertContains(
			'["warning","smw-property-req-violation-parent-type","Foo","Parent"]',
			$instance->getMessagesAsString()
		);
	}

	public function recordTypeProvider() {
		yield [ '_rec', 'Record' ];
		yield [ '_ref_rec', 'Reference' ];
	}

}
