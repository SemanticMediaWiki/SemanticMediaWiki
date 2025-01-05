<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SiteLanguageChangeException;

/**
 * @covers \SMW\Exception\SiteLanguageChangeException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class SiteLanguageChangeExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SiteLanguageChangeException( 'Foo', 'Bar' );

		$this->assertInstanceof(
			SiteLanguageChangeException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
