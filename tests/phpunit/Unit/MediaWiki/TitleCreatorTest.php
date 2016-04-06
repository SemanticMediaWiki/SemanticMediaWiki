<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\TitleCreator;

/**
 * @covers \SMW\MediaWiki\TitleCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class TitleCreatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			 new TitleCreator()
		);
	}

	public function testCreateTitleFromText() {

		$instance = new TitleCreator();

		$this->assertInstanceOf(
			'\Title',
			 $instance->createFromText( __METHOD__ )
		);
	}

}
