<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\CJKSimpleCharacterRegExTokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer\CJKSimpleCharacterRegExTokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CJKSimpleCharacterRegExTokenizerTest extends \PHPUnit_Framework_TestCase {

	public function testUnknownOption() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\CJKSimpleCharacterRegExTokenizer',
			new CJKSimpleCharacterRegExTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $expected ) {

		$instance = new CJKSimpleCharacterRegExTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);

		$this->assertFalse(
			$instance->isWordTokenizer()
		);
	}

	public function testTokenizeWithEnabledExemptionList() {

		$string = '《红色中华》报改名为《新中华报》的同时，在';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $this->equalTo( $string ) )
			->will( $this->returnValue( array( $string ) ) );

		$instance = new CJKSimpleCharacterRegExTokenizer( $tokenizer );

		$instance->setOption(
			CJKSimpleCharacterRegExTokenizer::REGEX_EXEMPTION,
			array( '《', '》', '，')
		);

		$this->assertEquals(
			array( '《红色中华》报改名', '《新中华报》', '同', '，' ),
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			'《红色中华》报改名为《新中华报》的同时，在延安更名为新华通讯社。但是当时，新华社和《新中华报》还是同一个机构。',
			array( '红色中华', '报改名', '新中华报', '同', '延安更名', '新华通讯社', '新华社', '新中华报', '同一', '机构' )
		);

		$provider[] = array(
			'江泽民在北京人民大会堂会见参加全国法院工作会议和全国法院系统打击经济犯罪先进集体表彰大会代表时要求大家要充分认识打击经济犯罪的艰巨性和长期性',
			array( '江泽民', '北京人民大会堂会见参加全国法院工作会议', '全国法院系统打击经济犯罪先进集体表彰大会代表', '求大家', '充分认识打击经济犯罪', '艰巨性', '长期性' )
		);

		return $provider;
	}

}
