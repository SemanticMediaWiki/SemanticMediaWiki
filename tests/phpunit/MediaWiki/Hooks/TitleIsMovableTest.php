<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use SMW\MediaWiki\Hooks\TitleIsMovable;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleIsMovable
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class TitleIsMovableTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$title = $this->getMockBuilder( '\MediaWiki\Title\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\TitleIsMovable',
			new TitleIsMovable( $title )
		);
	}

	public function testPredefinedPropertyPageIsNotMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Modification date', SMW_NS_PROPERTY );
		$isMovable = true;

		$instance = new TitleIsMovable( $title );

		$this->assertTrue(
			$instance->process( $isMovable )
		);

		$this->assertFalse(
			$isMovable
		);
	}

	public function testUserdefinedPropertyPageIsMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', SMW_NS_PROPERTY );
		$isMovable = true;

		$instance = new TitleIsMovable( $title );

		$this->assertTrue(
			$instance->process( $isMovable )
		);

		$this->assertTrue(
			$isMovable
		);
	}

	public function testNonPropertyPageIsAlwaysMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', NS_MAIN );
		$isMovable = true;

		$instance = new TitleIsMovable( $title );

		$this->assertTrue(
			$instance->process( $isMovable )
		);

		$this->assertTrue(
			$isMovable
		);
	}

	public function testRulePageIsAlwaysNotMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', SMW_NS_SCHEMA );
		$isMovable = true;

		$instance = new TitleIsMovable( $title );

		$this->assertTrue(
			$instance->process( $isMovable )
		);

		$this->assertFalse(
			$isMovable
		);
	}

}
