<?php

namespace SMW\Tests;

use SMW\DIWikiPage;
use SMW\InMemoryPoolCache;
use SMW\Store;

/**
 * @covers \SMW\Store
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class StoreTest extends \PHPUnit_Framework_TestCase {

	public function testGetRedirectTargetFromInMemoryCache() {

		$inMemoryPoolCache = InMemoryPoolCache::getInstance();

		$instance = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$wikipage = new DIWikiPage( 'Foo', NS_MAIN );
		$expected = new DIWikiPage( 'Bar', NS_MAIN );

		$inMemoryPoolCache->getPoolCacheFor( 'store.redirectTarget.lookup' )->save(
			$wikipage->getHash(),
			$expected
		);

		$this->assertEquals(
			$expected,
			$instance->getRedirectTarget( $wikipage )
		);

		$inMemoryPoolCache->resetPoolCacheFor( 'store.redirectTarget.lookup' );
	}

}
