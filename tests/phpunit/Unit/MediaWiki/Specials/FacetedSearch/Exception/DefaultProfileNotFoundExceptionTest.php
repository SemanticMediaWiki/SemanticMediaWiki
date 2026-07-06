<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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
class DefaultProfileNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new DefaultProfileNotFoundException();

		$this->assertInstanceof(
			DefaultProfileNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			RuntimeException::class,
			$instance
		);
	}

}
