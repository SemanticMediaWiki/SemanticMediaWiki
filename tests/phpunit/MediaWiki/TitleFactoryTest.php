<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\TitleFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\TitleFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class TitleFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TitleFactory::class,
			 new TitleFactory()
		);
	}

	public function testCreateTitleFromText() {
		$instance = new TitleFactory();

		$this->assertInstanceOf(
			'\Title',
			 $instance->newFromText( __METHOD__ )
		);
	}

	public function testNewFromID() {
		$instance = new TitleFactory();
		$title = $instance->newFromID( 9999999 );

		$this->assertTrue(
			$title === null || $title instanceof \Title
		);
	}

	public function testNewFromIDs() {
		$instance = new TitleFactory();

		$this->assertIsArray(

			$instance->newFromIDs( [ 9999999 ] )
		);
	}

	public function testNewFromIDsEmpty() {
		$instance = new TitleFactory();
		$input = [];

		$out = $instance->newFromIDs( $input );

		$this->assertCount( 0, $out );

		$this->assertIsArray(

			$out
		);
	}

	public function testMakeTitleSafe() {
		$instance = new TitleFactory();

		$this->assertInstanceOf(
			'\Title',
			$instance->makeTitleSafe( NS_MAIN, 'Foo' )
		);
	}

}
