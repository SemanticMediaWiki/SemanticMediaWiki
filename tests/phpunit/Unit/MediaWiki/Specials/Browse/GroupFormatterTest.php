<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\GroupFormatter;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\GroupFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class GroupFormatterTest extends \PHPUnit_Framework_TestCase {

	private $propertySpecificationLookup;
	private $schemaFinder;

	protected function setUp() {
		parent::setUp();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			GroupFormatter::class,
			new GroupFormatter( $this->propertySpecificationLookup, $this->schemaFinder )
		);
	}

	public function testFindGroupMembership() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_CATEGORY ) ) );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->will( $this->returnValue( [] ) );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->will( $this->returnValue( $schemaList ) );

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup,
			$this->schemaFinder
		);

		$properties = [
			new DIProperty( 'Foo' )
		];

		$instance->findGroupMembership( $properties );

		$this->assertTrue(
			$instance->hasGroups()
		);

		$this->assertTrue(
			$instance->isLastGroup( 'Bar' )
		);

		$this->assertArrayHasKey(
			'Bar',
			$properties
		);

		$this->assertContains(
			'<span class="group-link">',
			$instance->getGroupLink( 'Bar' )
		);
	}

	public function testFindGroupMembershipFromSchema() {

		$data = [
			'Foo_schema' => [ 'property_list' => [ 'Foo' ] ]
		];

		$schemaDefinition = $this->getMockBuilder( '\SMW\Schema\SchemaDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$schemaDefinition->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo schema' ) );

		$schemaDefinition->expects( $this->any() )
			->method( 'get' )
			->with( $this->equalTo( 'groups') )
			->will( $this->returnValue( $data ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->will( $this->returnValue( null ) );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->will( $this->returnValue( [ $schemaDefinition ] ) );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->will( $this->returnValue( $schemaList ) );

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup,
			$this->schemaFinder
		);

		$properties = [
			new DIProperty( 'Foo' )
		];

		$instance->findGroupMembership( $properties );

		$this->assertTrue(
			$instance->hasGroups()
		);

		$this->assertTrue(
			$instance->isLastGroup( 'Foo schema' )
		);

		$this->assertArrayHasKey(
			'Foo schema',
			$properties
		);

		$this->assertContains(
			'<span class="group-link">',
			$instance->getGroupLink( 'Foo schema' )
		);
	}

	public function testFindGroupMembershipWhereShowGroupIsDisabled() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_CATEGORY ) ) );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->will( $this->returnValue( [] ) );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->will( $this->returnValue( $schemaList ) );

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup,
			$this->schemaFinder
		);

		$instance->showGroup( false );

		$properties = [
			new DIProperty( 'Foo' )
		];

		$instance->findGroupMembership( $properties );

		$this->assertFalse(
			$instance->hasGroups()
		);

		$this->assertFalse(
			$instance->isLastGroup( 'Bar' )
		);
	}

	public function testGetMessageClassLink() {

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup,
			$this->schemaFinder
		);

		$di = new DIWikiPage( 'Foo bar', NS_CATEGORY );

		$this->assertContains(
			'smw-property-group-label-foo-bar',
			$instance->getMessageClassLink( GroupFormatter::MESSAGE_GROUP_LABEL, $di )
		);

		$this->assertContains(
			'smw-property-group-description-foo-bar',
			$instance->getMessageClassLink( GroupFormatter::MESSAGE_GROUP_DESCRIPTION, $di )
		);
	}

}
