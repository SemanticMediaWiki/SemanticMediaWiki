<?php

namespace SMW\Tests\Utils;

use SMW\Utils\CharExaminer;

/**
 * @covers \SMW\Utils\CharExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CharExaminerTest extends \PHPUnit_Framework_TestCase {

	public function testToContainKoreanCharacters() {
		$this->assertTrue(
			CharExaminer::contains( CharExaminer::HANGUL, '한국어 텍스트의 예' )
		);

		$this->assertFalse(
			CharExaminer::contains( CharExaminer::HAN, '한국어 텍스트의 예' )
		);
	}

	public function testToContainJapaneseCharacters() {

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::LATIN, '脳のIQテスト' )
		);

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::HIRAGANA_KATAKANA, '脳のIQテスト' )
		);

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::HAN, '脳のIQテスト' )
		);
	}

	public function testToContainChineseCharacters() {

		$this->assertFalse(
			CharExaminer::contains( CharExaminer::LATIN, '才可以过关' )
		);

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::CJK_UNIFIED, '才可以过关' )
		);

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::HAN, '才可以过关' )
		);
	}

	public function testToContainCyrillic() {

		$this->assertFalse(
			CharExaminer::contains( CharExaminer::LATIN, 'Привет' )
		);

		$this->assertTrue(
			CharExaminer::contains( CharExaminer::CYRILLIC, 'Привет' )
		);
	}

	public function testToContainUnknownCharacters() {
		$this->assertFalse(
			CharExaminer::contains( 'Foo', '鿩' )
		);
	}

}
