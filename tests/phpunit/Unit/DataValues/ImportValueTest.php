<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ImportValue;

/**
 * @covers \SMW\DataValues\ImportValue
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ImportValue',
			new ImportValue( '__imp' )
		);

		// FIXME Legacy naming remove in 3.x
		$this->assertInstanceOf(
			'\SMWImportValue',
			new ImportValue( '__imp' )
		);
	}

	public function testErrorForInvalidUserValue() {

		$instance = new ImportValue( '__imp' );
		$instance->setUserValue( 'FooBar' );

		$this->assertEquals(
			'FooBar',
			$instance->getWikiValue()
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}
