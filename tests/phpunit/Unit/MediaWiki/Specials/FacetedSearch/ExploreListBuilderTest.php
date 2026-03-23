<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExploreListBuilderTest extends TestCase {

	private $profile;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
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
