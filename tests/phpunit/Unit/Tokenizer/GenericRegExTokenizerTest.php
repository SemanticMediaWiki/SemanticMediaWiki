<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\GenericRegExTokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer\GenericRegExTokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class GenericRegExTokenizerTest extends \PHPUnit_Framework_TestCase {

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
			->with( $this->equalTo( $string ) )
			->will( $this->returnValue( array( $string ) ) );

		$instance = new GenericRegExTokenizer( $tokenizer );

		$instance->setOption(
			GenericRegExTokenizer::REGEX_EXEMPTION,
			array( '\(', '\)', "'", ';')
		);

		$this->assertEquals(
			array( "It's", 'a', 'test', 'string', '(that', 'has', 'no);' ),
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			"It's a test string (that has no);deep meaning except0",
			array( 'It', 's', 'a', 'test', 'string', 'that', 'has', 'no', 'deep', 'meaning', 'except', '0' )
		);

		$provider[] = array(
			"Привет, мир! Меня зовут д'Артаньян %) цуацуа123123",
			array( 'Привет', 'мир', 'Меня', 'зовут', 'д', "Артаньян", 'цуацуа', '123123' )
		);

		$provider[] = array(
			"[[Действует на возбудителей]] Brucella spp., Legionella pneumophila, Salmonella typhi,(за исключением остальных рифампицинов) не отмечено. ...

Фармакокинетика[править | править вики-текст]
Рифампицин хорошо всасывается из желудочно-кишечного тракта.",
			array(
				'Действует', 'на', 'возбудителей', 'Brucella', 'spp', 'Legionella', 'pneumophila', 'Salmonella', 'typhi', 'за', 'исключением', 'остальных', 'рифампицинов',
				'не', 'отмечено', 'Фармакокинетика', 'править', 'править', 'вики', 'текст', 'Рифампицин', 'хорошо', 'всасывается', 'из', 'желудочно', 'кишечного', 'тракта'
			)
		);

		return $provider;
	}

}
