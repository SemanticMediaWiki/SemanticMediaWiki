<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class OptionsBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $profile;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OptionsBuilder::class,
			new OptionsBuilder( $this->profile )
		);
	}

}
