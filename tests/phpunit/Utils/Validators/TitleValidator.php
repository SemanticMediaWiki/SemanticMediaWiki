<?php

namespace SMW\Tests\Utils\Validators;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class TitleValidator extends \PHPUnit\Framework\Assert {

	/**
	 * @since 2.1
	 */
	public function assertThatTitleIsNotKnown( $titles ) {
		$this->assertTitleExists( false, $titles );
	}

	/**
	 * @since 2.1
	 */
	public function assertThatTitleIsKnown( $titles ) {
		$this->assertTitleExists( true, $titles );
	}

	private function assertTitleExists( $isExpected, $titles ) {
		if ( !is_array( $titles ) ) {
			$titles = [ $titles ];
		}

		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		foreach ( $titles as $title ) {

			if ( !$title instanceof Title && is_string( $title ) ) {
				$title = $titleFactory->newFromText( $title );
			}

			$this->assertEquals( $isExpected, $title->exists(), $title->getPrefixedText() );
		}
	}

}
