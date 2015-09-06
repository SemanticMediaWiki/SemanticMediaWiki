<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\RedirectTargetFinder;

use Title;

/**
 * @covers \SMW\MediaWiki\RedirectTargetFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.0
 *
 * @author mwjames
 */
class RedirectTargetFinderTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertEquals(
			$expectedGetTarget,
			$instance->getRedirectTarget()
		);
	}

	/**
	 * @dataProvider redirectTextProvider
	 */
	public function testInjectedRedirectTargetOverridesTextFinder( $text ) {

		$directRedirectTarget = Title::newFromText( 'Foo' );

		$instance = new RedirectTargetFinder();
		$instance->setRedirectTarget( $directRedirectTarget );
		$instance->findRedirectTargetFromText( $text );

		$this->assertTrue(
			$instance->hasRedirectTarget()
		);

		$this->assertEquals(
			$directRedirectTarget,
			$instance->getRedirectTarget()
		);
	}

	public function redirectTextProvider() {

		$provider[] = array( '#REDIRECT [[:Lala]]', true, Title::newFromText( 'Lala' ) );
		$provider[] = array( '#REDIRECT [[Lala]]',  true, Title::newFromText( 'Lala' ) );
		$provider[] = array( '[[:Lala]]',           false, null );

		return $provider;
	}

}
