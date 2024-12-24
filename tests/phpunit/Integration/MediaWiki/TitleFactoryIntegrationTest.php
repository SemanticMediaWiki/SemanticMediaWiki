<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\MediaWiki\TitleFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @covers \SMW\MediaWiki\TitleFactory
 * @group SMW
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @license GNU GPL v2+
 * @since 4.1.2
 *
 * @author octfx
 */
class TitleFactoryIntegrationTest extends SMWIntegrationTestCase {
	use PHPUnitCompat;

	public function testNewFromIDs() {
		$title = Title::makeTitle( NS_MAIN, 'FooTitle' );

		$page = UtilityFactory::getInstance()->newPageCreator()->createPage( $title );

		$instance = new TitleFactory();
		$input = [ $page->getPage()->getId() ];

		$out = $instance->newFromIDs( $input );

		$this->assertCount( 1, $out );
		$this->assertIsArray( $out );
		$this->assertInstanceOf( Title::class, $out[0] );
		$this->assertEquals( $title->getId(), $out[0]->getId() );
	}
}
