<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Browse;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Specials\Browse\GroupFormatter;
use SMW\Property\SpecificationLookup;
use SMW\Schema\SchemaDefinition;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\GroupFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class GroupFormatterTest extends TestCase {

	private $propertySpecificationLookup;
	private $schemaFinder;

	protected function setUp(): void {
		parent::setUp();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder = $this->getMockBuilder( SchemaFinder::class )
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
			->willReturn( new WikiPage( 'Bar', NS_CATEGORY ) );

		$schemaList = $this->getMockBuilder( SchemaList::class )
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
			new Property( 'Foo' )
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

		$this->assertStringContainsString(
			'<span class="group-link">',
			$instance->getGroupLink( 'Bar' )
		);
	}

	public function testFindGroupMembershipFromSchema() {
		$data = [
			[ 'properties' => [ 'Foo' ], 'group_name' => 'Foo schema' ]
		];

		$schemaDefinition = $this->getMockBuilder( SchemaDefinition::class )
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

		$schemaList = $this->getMockBuilder( SchemaList::class )
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
			new Property( 'Foo' )
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

		$this->assertStringContainsString(
			'<span class="group-link">',
			$instance->getGroupLink( 'Foo schema' )
		);
	}

	public function testFindGroupMembershipWhereShowGroupIsDisabled() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->willReturn( new WikiPage( 'Bar', NS_CATEGORY ) );

		$schemaList = $this->getMockBuilder( SchemaList::class )
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
			new Property( 'Foo' )
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

		$di = new WikiPage( 'Foo bar', NS_CATEGORY );

		$this->assertStringContainsString(
			'smw-property-group-label-foo-bar',
			$instance->getMessageClassLink( GroupFormatter::MESSAGE_GROUP_LABEL, $di )
		);

		$this->assertStringContainsString(
			'smw-property-group-description-foo-bar',
			$instance->getMessageClassLink( GroupFormatter::MESSAGE_GROUP_DESCRIPTION, $di )
		);
	}

}
