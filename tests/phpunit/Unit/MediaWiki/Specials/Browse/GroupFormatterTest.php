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

	protected function setUp() {
		parent::setUp();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			GroupFormatter::class,
			new GroupFormatter( $this->propertySpecificationLookup )
		);
	}

	public function testFindGroupMembership() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_CATEGORY ) ) );

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup
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

	public function testFindGroupMembershipWhereShowGroupIsDisabled() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getPropertyGroup' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_CATEGORY ) ) );

		$instance = new GroupFormatter(
			$this->propertySpecificationLookup
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
			$this->propertySpecificationLookup
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
