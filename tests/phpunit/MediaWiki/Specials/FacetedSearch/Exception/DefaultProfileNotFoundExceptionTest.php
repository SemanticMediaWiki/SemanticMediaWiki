<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Exception;

use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultProfileNotFoundException;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultProfileNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultProfileNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new DefaultProfileNotFoundException();

		$this->assertInstanceof(
			DefaultProfileNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
