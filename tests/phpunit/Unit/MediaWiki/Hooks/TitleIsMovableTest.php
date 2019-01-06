<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\TitleIsMovable;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleIsMovable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class TitleIsMovableTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\TitleIsMovable',
			new TitleIsMovable( $title )
		);
	}

	public function testPredefinedPropertyPageIsNotMovable() {

		$title = Title::newFromText( 'Modification date', SMW_NS_PROPERTY );
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

		$title = Title::newFromText( 'Foo', SMW_NS_PROPERTY );
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

		$title = Title::newFromText( 'Foo', NS_MAIN );
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

		$title = Title::newFromText( 'Foo', SMW_NS_SCHEMA );
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
