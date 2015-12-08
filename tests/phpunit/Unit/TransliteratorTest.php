<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Transliterator;

/**
 * @covers \Onoi\Tesa\Transliterator
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class TransliteratorTest extends \PHPUnit_Framework_TestCase {

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

		$provider[] = array(
			'Foo',
			'unknownFlag',
			'Foo',
		);

		$provider[] = array(
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
			Transliterator::NONE,
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
		);

		$provider[] = array(
			'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž',
			Transliterator::DIACRITICS,
			'AAAAAEAaaaaaeaOOOOOOEOoooooeoEEEEeeeeðCcÐIIIIiiiiUUUUEuuuueNnSsYyyZz'
		);

		$provider[] = array(
			'ỆᶍǍᶆṔƚÉ áéíóúýčďěňřšťžů',
			Transliterator::DIACRITICS,
			'ExAmPlE aeiouycdenrstzu'
		);

		$provider[] = array(
			'àáâãäå',
			Transliterator::DIACRITICS,
			'aaaaaea'
		);

		$provider[] = array(
			'èéêë',
			Transliterator::DIACRITICS,
			'eeee'
		);

		$provider[] = array(
			'òóôõö',
			Transliterator::DIACRITICS,
			'oooooe'
		);

		$provider[] = array(
			'ùúûü',
			Transliterator::DIACRITICS,
			'uuuue'
		);

		$provider[] = array(
			'ç',
			Transliterator::DIACRITICS,
			'c'
		);

		$provider[] = array(
			'æ',
			Transliterator::DIACRITICS,
			'ae'
		);

		$provider[] = array(
			'ñ',
			Transliterator::DIACRITICS,
			'n'
		);

		$provider[] = array(
			'œ',
			Transliterator::DIACRITICS,
			'oe'
		);

		$provider[] = array(
			'ýÿ',
			Transliterator::DIACRITICS,
			'yy'
		);

		$provider[] = array(
			'ß',
			Transliterator::DIACRITICS,
			'ss'
		);

		$provider[] = array(
			'Vilʹândimaa',
			Transliterator::DIACRITICS,
			'Vilʹandimaa'
		);

		$provider[] = array(
			'Ελληνική Δημοκρατία',
			Transliterator::GREEK,
			'Ellīnikī́ Dīmokratía'
		);

		$provider[] = array(
			'Ελληνική Δημοκρατία',
			Transliterator::DIACRITICS | Transliterator::GREEK,
			'Ellinikí Dimokratia'
		);

		$provider[] = array(
			'Γκ γκ γξ Ει ει Ηυ Μπ μπ',
			Transliterator::GREEK,
			'Gk gk gx Ei ei Īy Mp mp'
		);

		$provider[] = array(
			'Μετατροπή του ελληνικού αλφαβήτου με λατινικούς χαρακτήρες',
			Transliterator::GREEK,
			'Metatropī́ tou ellīnikoú alfavī́tou me latinikoús charaktī́res',
		);

		$provider[] = array(
			'Ελληνικός Οργανισμός Τυποποίησης',
			Transliterator::GREEK,
			'Ellīnikós Organismós Typopoíīsīs',
		);

		return $provider;
	}

}
