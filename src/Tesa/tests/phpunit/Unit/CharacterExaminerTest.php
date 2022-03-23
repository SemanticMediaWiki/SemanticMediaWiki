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
			CharacterExaminer::contains( CharacterExaminer::HANGUL, '한국어 텍스트의 예' )
		);

		$this->assertFalse(
			CharacterExaminer::contains( CharacterExaminer::HAN, '한국어 텍스트의 예' )
		);
	}

	public function testToContainJapaneseCharacters() {

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::LATIN, '脳のIQテスト' )
		);

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::HIRAGANA_KATAKANA, '脳のIQテスト' )
		);

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::HAN, '脳のIQテスト' )
		);
	}

	public function testToContainChineseCharacters() {

		$this->assertFalse(
			CharacterExaminer::contains( CharacterExaminer::LATIN, '才可以过关' )
		);

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::CJK_UNIFIED, '才可以过关' )
		);

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::HAN, '才可以过关' )
		);
	}

	public function testToContainCyrillic() {

		$this->assertFalse(
			CharacterExaminer::contains( CharacterExaminer::LATIN, 'Привет' )
		);

		$this->assertTrue(
			CharacterExaminer::contains( CharacterExaminer::CYRILLIC, 'Привет' )
		);
	}

	public function testToContainUnknownCharacters() {
		$this->assertFalse(
			CharacterExaminer::contains( 'Foo', '鿩' )
		);
	}

}
