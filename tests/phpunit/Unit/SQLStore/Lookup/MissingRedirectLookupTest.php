<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\Lookup\MissingRedirectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class MissingRedirectLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MissingRedirectLookup::class,
			new MissingRedirectLookup( $this->store )
		);
	}

	public function testFindMissingRedirects() {
		$queryBuilder = $this->createMockSelectQueryBuilder( [] );

		$queryBuilder->expects( $this->once() )
			->method( 'select' )
			->with( [ 'page_id', 'page_title', 'page_namespace' ] )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'from' )
			->with( 'page' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'leftJoin' )
			->with(
				'smw_fpt_redi',
				null,
				[ 's_title=page_title', 's_namespace=page_namespace' ]
			)
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'where' )
			->with( [
				'page_is_redirect' => 1,
				'page_namespace' => [ NS_MAIN, SMW_NS_PROPERTY ],
				's_title IS NULL'
			] )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'orderBy' )
			->with( 'page_namespace,page_title' )
			->willReturnSelf();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new MissingRedirectLookup(
			$this->store
		);

		$instance->setNamespaceMatrix(
			[
				NS_MAIN => true,
				NS_HELP => false,
				SMW_NS_PROPERTY => true
			]
		);

		$instance->findMissingRedirects();
	}

}
