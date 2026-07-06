<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\MediaWiki\Specials\FacetedSearch\Exception\ProfileSourceDefinitionConflictException;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Exception\ProfileSourceDefinitionConflictException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ProfileSourceDefinitionConflictExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new ProfileSourceDefinitionConflictException( 'Foo', 'a', 'b' );

		$this->assertInstanceof(
			ProfileSourceDefinitionConflictException::class,
			$instance
		);

		$this->assertInstanceof(
			RuntimeException::class,
			$instance
		);
	}

}
