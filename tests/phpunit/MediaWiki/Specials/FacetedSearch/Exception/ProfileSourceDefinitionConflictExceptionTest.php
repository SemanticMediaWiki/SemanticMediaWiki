<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Exception;

use SMW\MediaWiki\Specials\FacetedSearch\Exception\ProfileSourceDefinitionConflictException;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Exception\ProfileSourceDefinitionConflictException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ProfileSourceDefinitionConflictExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new ProfileSourceDefinitionConflictException( 'Foo', 'a', 'b' );

		$this->assertInstanceof(
			ProfileSourceDefinitionConflictException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
