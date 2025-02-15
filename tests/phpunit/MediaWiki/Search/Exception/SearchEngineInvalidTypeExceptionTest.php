<?php

namespace SMW\Tests\MediaWiki\Search\Exception;

use SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException;

/**
 * @covers \SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SearchEngineInvalidTypeExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SearchEngineInvalidTypeException( 'Foo' );

		$this->assertInstanceof(
			SearchEngineInvalidTypeException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
