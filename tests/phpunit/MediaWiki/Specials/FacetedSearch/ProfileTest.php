<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\Schema\Compartment;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\SchemaFactory;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Profile
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ProfileTest extends TestCase {

	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
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
		$profile = $this->getMockBuilder( Compartment::class )
			->disableOriginalConstructor()
			->getMock();

		$profile->expects( $this->any() )
			->method( 'get' )
			->willReturn( 'default' );

		$iteratorMockBuilder = new IteratorMockBuilder();
		$iteratorMockBuilder->setClass( CompartmentIterator::class );
		$iteratorMockBuilder->with( [ [ $profile ] ] );

		$compartmentIterator = $iteratorMockBuilder->getMockForIterator();

		$schemaList = $this->getMockBuilder( SchemaList::class )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->any() )
			->method( 'newCompartmentIteratorByKey' )
			->willReturn( $compartmentIterator );

		$schemaFinder = $this->getMockBuilder( SchemaFinder::class )
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
