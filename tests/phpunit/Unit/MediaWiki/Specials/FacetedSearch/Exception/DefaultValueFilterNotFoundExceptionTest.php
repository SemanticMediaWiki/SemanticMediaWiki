<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Exception;

use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultValueFilterNotFoundException;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultValueFilterNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultValueFilterNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new DefaultValueFilterNotFoundException( 'Foo' );

		$this->assertInstanceof(
			DefaultValueFilterNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
