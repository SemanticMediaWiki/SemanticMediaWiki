<?php

namespace SMW\Tests\Utils\Validators;

use Title;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class TitleValidator extends \PHPUnit_Framework_Assert {

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
			$titles = array( $titles );
		}

		foreach ( $titles as $title ) {

			if ( !$title instanceof Title && is_string( $title ) ) {
				$title = Title::newFromText( $title );
			}

			$this->assertEquals( $isExpected, $title->exists() );
		}
	}

}
