<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ResultFetcherTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultFetcher::class,
			new ResultFetcher( $this->store )
		);
	}

}
