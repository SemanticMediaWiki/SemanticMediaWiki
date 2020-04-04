<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SiteLanguageChangeException;

/**
 * @covers \SMW\Exception\SiteLanguageChangeException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SiteLanguageChangeExceptionTest extends \PHPUnit_Framework_TestCase {

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
