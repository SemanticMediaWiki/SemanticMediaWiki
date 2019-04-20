<?php

namespace SMW\Tests\MediaWiki\Search\Exception;

use SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException;

/**
 * @covers \SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SearchDatabaseInvalidTypeExceptionTest extends \PHPUnit_Framework_TestCase {

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
