<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use SMW\MediaWiki\RedirectTargetFinder;

/**
 * @covers \SMW\MediaWiki\RedirectTargetFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.0
 *
 * @author mwjames
 */
class RedirectTargetFinderTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\RedirectTargetFinder',
			new RedirectTargetFinder()
		);
	}

	/**
	 * @dataProvider redirectTextProvider
	 */
	public function testFindRedirectTargetFromText( $text, $expectedHasTarget, $expectedGetTarget ) {
		$instance = new RedirectTargetFinder();
		$instance->findRedirectTargetFromText( $text );

		$this->assertEquals(
			$expectedHasTarget,
			$instance->hasRedirectTarget()
		);

		if ( $expectedGetTarget ) {
			$this->assertTrue( $instance->getRedirectTarget()->equals( $expectedGetTarget ) );
		} else {
			$this->assertNull( $instance->getRedirectTarget() );
		}
	}

	/**
	 * @dataProvider redirectTextProvider
	 */
	public function testInjectedRedirectTargetOverridesTextFinder( $text ) {
		$directRedirectTarget = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Foo' );

		$instance = new RedirectTargetFinder();
		$instance->setRedirectTarget( $directRedirectTarget );
		$instance->findRedirectTargetFromText( $text );

		$this->assertTrue(
			$instance->hasRedirectTarget()
		);

		if ( $directRedirectTarget ) {
			$this->assertTrue( $instance->getRedirectTarget()->equals( $directRedirectTarget ) );
		} else {
			$this->assertNull( $instance->getRedirectTarget() );
		}
	}

	public function redirectTextProvider() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$provider[] = [ '#REDIRECT [[:Lala]]', true, $titleFactory->newFromText( 'Lala' ) ];
		$provider[] = [ '#REDIRECT [[Lala]]', true, $titleFactory->newFromText( 'Lala' ) ];
		$provider[] = [ '[[:Lala]]', false, null ];

		return $provider;
	}

}
