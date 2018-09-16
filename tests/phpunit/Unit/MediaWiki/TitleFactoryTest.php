<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\TitleFactory;

/**
 * @covers \SMW\MediaWiki\TitleFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class TitleFactoryTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertInternalType(
			'array',
			$instance->newFromIDs( [ 9999999 ] )
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
