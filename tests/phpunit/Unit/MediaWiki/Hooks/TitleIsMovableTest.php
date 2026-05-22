<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
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
class TitleIsMovableTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TitleIsMovable::class,
			new TitleIsMovable()
		);
	}

	public function testPredefinedPropertyPageIsNotMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Modification date', SMW_NS_PROPERTY );
		$isMovable = true;

		$instance = new TitleIsMovable();

		$this->assertTrue(
			$instance->onTitleIsMovable( $title, $isMovable )
		);

		$this->assertFalse(
			$isMovable
		);
	}

	public function testUserdefinedPropertyPageIsMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', SMW_NS_PROPERTY );
		$isMovable = true;

		$instance = new TitleIsMovable();

		$this->assertTrue(
			$instance->onTitleIsMovable( $title, $isMovable )
		);

		$this->assertTrue(
			$isMovable
		);
	}

	public function testNonPropertyPageIsAlwaysMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', NS_MAIN );
		$isMovable = true;

		$instance = new TitleIsMovable();

		$this->assertTrue(
			$instance->onTitleIsMovable( $title, $isMovable )
		);

		$this->assertTrue(
			$isMovable
		);
	}

	public function testRulePageIsAlwaysNotMovable() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo', SMW_NS_SCHEMA );
		$isMovable = true;

		$instance = new TitleIsMovable();

		$this->assertTrue(
			$instance->onTitleIsMovable( $title, $isMovable )
		);

		$this->assertFalse(
			$isMovable
		);
	}

}
