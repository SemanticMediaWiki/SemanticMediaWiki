<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Transliterator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Transliterator
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class TransliteratorTest extends TestCase {

	/**
	 * @dataProvider characterProvider
	 */
	public function testTransliteration( $input, $flag, $expected ) {
		$this->assertEquals(
			$expected,
			Transliterator::transliterate( $input, $flag )
		);
	}

	public function testTransliterationWithoutOptionFlag() {
		$this->assertEquals(
			'aaaaaea',
			Transliterator::transliterate( 'àáâãäå' )
		);
	}

	public function characterProvider() {
		$provider[] = [
			'Foo',
			'unknownFlag',
			'Foo',
		];

		$provider[] = [
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
			Transliterator::NONE,
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
		];

		$provider[] = [
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
			Transliterator::DIACRITICS,
			'AAAAAEAaaaaaeaOOOOOOEOoooooeoEEEEeeeeðCcÐIIIIiiiiUUUUEuuuueNnSsYyyZz'
		];

		$provider[] = [
			'ỆᶍǍᶆṔƚÉ áéíóúýčďěňřšťžů',
			Transliterator::DIACRITICS,
			'ExAmPlE aeiouycdenrstzu'
		];

		$provider[] = [
			'àáâãäå',
			Transliterator::DIACRITICS,
			'aaaaaea'
		];

		$provider[] = [
			'èéêë',
			Transliterator::DIACRITICS,
			'eeee'
		];

		$provider[] = [
			'òóôõö',
			Transliterator::DIACRITICS,
			'oooooe'
		];

		$provider[] = [
			'ùúûü',
			Transliterator::DIACRITICS,
			'uuuue'
		];

		$provider[] = [
			'ç',
			Transliterator::DIACRITICS,
			'c'
		];

		$provider[] = [
			'æ',
			Transliterator::DIACRITICS,
			'ae'
		];

		$provider[] = [
			'ñ',
			Transliterator::DIACRITICS,
			'n'
		];

		$provider[] = [
			'œ',
			Transliterator::DIACRITICS,
			'oe'
		];

		$provider[] = [
			'ýÿ',
			Transliterator::DIACRITICS,
			'yy'
		];

		$provider[] = [
			'ß',
			Transliterator::DIACRITICS,
			'ss'
		];

		$provider[] = [
			'Vilʹândimaa',
			Transliterator::DIACRITICS,
			'Vilʹandimaa'
		];

		$provider[] = [
			'Ελληνική Δημοκρατία',
			Transliterator::GREEK,
			'Ellīnikī́ Dīmokratía'
		];

		$provider[] = [
			'Ελληνική Δημοκρατία',
			Transliterator::DIACRITICS | Transliterator::GREEK,
			'Ellinikí Dimokratia'
		];

		$provider[] = [
			'Γκ γκ γξ Ει ει Ηυ Μπ μπ',
			Transliterator::GREEK,
			'Gk gk gx Ei ei Īy Mp mp'
		];

		$provider[] = [
			'Μετατροπή του ελληνικού αλφαβήτου με λατινικούς χαρακτήρες',
			Transliterator::GREEK,
			'Metatropī́ tou ellīnikoú alfavī́tou me latinikoús charaktī́res',
		];

		$provider[] = [
			'Ελληνικός Οργανισμός Τυποποίησης',
			Transliterator::GREEK,
			'Ellīnikós Organismós Typopoíīsīs',
		];

		return $provider;
	}

}
