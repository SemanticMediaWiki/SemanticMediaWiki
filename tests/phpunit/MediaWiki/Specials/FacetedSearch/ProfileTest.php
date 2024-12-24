<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Profile
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ProfileTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Profile::class,
			new Profile( $this->schemaFactory, 'foo' )
		);
	}

	public function testGetProfileName() {
		$instance = new Profile(
			$this->schemaFactory,
			'foo'
		);

		$this->assertEquals(
			'foo',
			$instance->getProfileName()
		);
	}

	public function testGetProfileCount() {
		$profile = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$profile->expects( $this->any() )
			->method( 'get' )
			->willReturn( 'default' );

		$iteratorMockBuilder = new IteratorMockBuilder();
		$iteratorMockBuilder->setClass( '\SMW\Schema\CompartmentIterator' );
		$iteratorMockBuilder->with( [ [ $profile ] ] );

		$compartmentIterator = $iteratorMockBuilder->getMockForIterator();

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'newCompartmentIteratorByKey' )
			->willReturn( $compartmentIterator );

		$schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->willReturn( $schemaList );

		$this->schemaFactory->expects( $this->any() )
			->method( 'newSchemaFinder' )
			->willReturn( $schemaFinder );

		$instance = new Profile(
			$this->schemaFactory,
			'foo'
		);

		$this->assertSame(
			1,
			$instance->getProfileCount()
		);
	}

}

