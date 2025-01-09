<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\GenericRegExTokenizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Tokenizer\GenericRegExTokenizer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class GenericRegExTokenizerTest extends TestCase {

	public function testUnknownOption() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\GenericRegExTokenizer',
			new GenericRegExTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $expected ) {
		$instance = new GenericRegExTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithEnabledExemptionList() {
		$string = "It's a test string (that has no);";

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $string )
			->willReturn( [ $string ] );

		$instance = new GenericRegExTokenizer( $tokenizer );

		$instance->setOption(
			GenericRegExTokenizer::REGEX_EXEMPTION,
			[ '\(', '\)', "'", ';' ]
		);

		$this->assertEquals(
			[ "It's", 'a', 'test', 'string', '(that', 'has', 'no);' ],
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {
		$provider[] = [
			"It's a test string (that has no);deep meaning except0",
			[ 'It', 's', 'a', 'test', 'string', 'that', 'has', 'no', 'deep', 'meaning', 'except', '0' ]
		];

		$provider[] = [
			"Привет, мир! Меня зовут д'Артаньян %) цуацуа123123",
			[ 'Привет', 'мир', 'Меня', 'зовут', 'д', "Артаньян", 'цуацуа', '123123' ]
		];

		$provider[] = [
			"[[Действует на возбудителей]] Brucella spp., Legionella pneumophila, Salmonella typhi,(за исключением остальных рифампицинов) не отмечено. ...

Фармакокинетика[править | править вики-текст]
Рифампицин хорошо всасывается из желудочно-кишечного тракта.",
			[
				'Действует', 'на', 'возбудителей', 'Brucella', 'spp', 'Legionella', 'pneumophila', 'Salmonella', 'typhi', 'за', 'исключением', 'остальных', 'рифампицинов',
				'не', 'отмечено', 'Фармакокинетика', 'править', 'править', 'вики', 'текст', 'Рифампицин', 'хорошо', 'всасывается', 'из', 'желудочно', 'кишечного', 'тракта'
			]
		];

		return $provider;
	}

}
