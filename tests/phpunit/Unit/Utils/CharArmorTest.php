<?php

namespace SMW\Tests\Utils;

use SMW\Utils\CharArmor;

/**
 * @covers \SMW\Utils\CharArmor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CharArmorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invisibleControlCharactersProvider
	 */
	public function testRemoveControlChars( $withControlChar, $expected ) {

		$this->assertFalse(
			$expected === $withControlChar
		);

		$this->assertEquals(
			$expected,
			CharArmor::removeControlChars( $withControlChar )
		);
	}

	/**
	 * @dataProvider specialCharactersProvider
	 */
	public function testRemoveSpecialChars( $withSpecialChar, $expected ) {

		$this->assertEquals(
			$expected,
			CharArmor::removeSpecialChars( $withSpecialChar )
		);
	}

	public function invisibleControlCharactersProvider() {

		$provider[] = [
			'[[Left-To-Right Mark::"‎"]]',
			'[[Left-To-Right Mark::""]]'
		];

		$provider[] = [
			'[[Right-To-Left Mark::"‏"]]',
			'[[Right-To-Left Mark::""]]'
		];

		$provider[] = [
			'[[Zero-Width​Space::"​"]]',
			'[[Zero-WidthSpace::""]]'
		];

		$provider[] = [
			'[[Zero Width Non-Joiner::"‌"]]',
			'[[Zero Width Non-Joiner::""]]'
		];

		$provider[] = [
			'[[Zero Width Joiner::"‍"]]',
			'[[Zero Width Joiner::""]]'
		];

		return $provider;
	}

	public function specialCharactersProvider() {

		$provider[] = [
			'visible shy&shy;ness',
			'visible shyness'
		];

		$provider[] = [
			'leftToRight&lrm;Mark',
			'leftToRightMark'
		];

		$provider[] = [
			'[[Figure Space::" "]]',
			'[[Figure Space::" "]]'
		];

		$provider[] = [
			'[[En Quad::" "]]',
			'[[En Quad::" "]]'
		];

		$provider[] = [
			'[[Hair Space::" "]]',
			'[[Hair Space::" "]]'
		];

		return $provider;
	}

}
