<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ResultFetcherTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
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
