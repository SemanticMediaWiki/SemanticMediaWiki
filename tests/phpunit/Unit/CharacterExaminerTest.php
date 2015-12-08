<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\CharacterExaminer;

/**
 * @covers \Onoi\Tesa\CharacterExaminer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CharacterExaminerTest extends \PHPUnit_Framework_TestCase {

	public function testToContainKoreanCharacters() {
		$this->assertTrue(
			CharacterExaminer::contains( ONOI_TESA_CHAR_EXAMINER_HANGUL, '한국어 텍스트의 예' )
		);
	}

	public function testToContainJapaneseCharacters() {
		$this->assertTrue(
			CharacterExaminer::contains( ONOI_TESA_CHAR_EXAMINER_HIRAGANA_KATAKANA, 'IQテスト' )
		);
	}

	public function testToContainChineseCharacters() {
		$this->assertTrue(
			CharacterExaminer::contains( ONOI_TESA_CHAR_EXAMINER_CJK_UNIFIED, '才可以过关' )
		);
	}

	public function testToContainUnknownCharacters() {
		$this->assertFalse(
			CharacterExaminer::contains( 'Foo', '鿩' )
		);
	}

}
