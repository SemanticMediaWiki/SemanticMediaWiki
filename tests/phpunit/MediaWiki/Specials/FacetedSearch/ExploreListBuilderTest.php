<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ExploreListBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $profile;

	protected function setUp() : void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExploreListBuilder::class,
			new ExploreListBuilder( $this->profile )
		);
	}

}

