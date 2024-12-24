<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\GroupFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\GroupFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class GroupFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $propertySpecificationLookup;
	private $schemaFinder;

	protected function setUp(): void {
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
			->willReturn( new DIWikiPage( 'Bar', NS_CATEGORY ) );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->willReturn( [] );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->willReturn( $schemaList );

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
			[ 'properties' => [ 'Foo' ], 'group_name' => 'Foo schema' ]
		];

		$schemaDefinition = $this->getMockBuilder( '\SMW\Schema\SchemaDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$schemaDefinition->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'Foo schema' );

		$schemaDefinition->expects( $this->any() )
			->method( 'get' )
			->with( 'groups' )
			->willReturn( $data );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->willReturn( null );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->willReturn( [ $schemaDefinition ] );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->willReturn( $schemaList );

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
			->willReturn( new DIWikiPage( 'Bar', NS_CATEGORY ) );

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'getList' )
			->willReturn( [] );

		$this->schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->willReturn( $schemaList );

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
