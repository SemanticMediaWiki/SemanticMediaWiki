<?php

namespace SMW\Tests\Unit\MediaWiki\Search\Exception;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException;

/**
 * @covers \SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SearchDatabaseInvalidTypeExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new SearchDatabaseInvalidTypeException( 'Foo' );

		$this->assertInstanceof(
			SearchDatabaseInvalidTypeException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
