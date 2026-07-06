<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class TreeBuilderTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TreeBuilder::class,
			new TreeBuilder( $this->store )
		);
	}

}
