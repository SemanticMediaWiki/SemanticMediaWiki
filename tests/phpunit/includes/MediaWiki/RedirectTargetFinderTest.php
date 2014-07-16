<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\RedirectTargetFinder;

use Title;

/**
 * @covers \SMW\MediaWiki\RedirectTargetFinder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
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
	public function testFindTargetFromText( $text, $expectedHasTarget, $expectedGetTarget ) {

		$instance = new RedirectTargetFinder();
		$instance->findTarget( $text );

		$this->assertEquals( $expectedHasTarget, $instance->hasTarget() );
		$this->assertEquals( $expectedGetTarget, $instance->getTarget() );
	}

	public function redirectTextProvider() {

		$provider[] = array( '#REDIRECT [[:Lala]]', true, Title::newFromText( 'Lala' ) );
		$provider[] = array( '#REDIRECT [[Lala]]',  true, Title::newFromText( 'Lala' ) );
		$provider[] = array( '[[:Lala]]',           false, null );

		return $provider;
	}

}
