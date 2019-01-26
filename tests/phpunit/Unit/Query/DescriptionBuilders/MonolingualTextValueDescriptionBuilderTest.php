<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use SMW\DataValueFactory;
use SMW\DataValues\MonolingualTextValue;
use SMW\Query\DescriptionBuilders\MonolingualTextValueDescriptionBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\DescriptionBuilders\MonolingualTextValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueDescriptionBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MonolingualTextValueDescriptionBuilder::class,
			new MonolingualTextValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForTimeValue() {

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new MonolingualTextValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	public function testNonStringThrowsException() {

		$recordValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MonolingualTextValueDescriptionBuilder();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->newDescription( $recordValue, [] );
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testNewDescription( $value, $decription, $queryString, $dvFeatures ) {

		$monolingualTextValue = DataValueFactory::getInStance()->newDataValueByType(
			MonolingualTextValue::TYPE_ID
		);

		$monolingualTextValue->setOption( 'smwgDVFeatures', $dvFeatures );

		$instance = new MonolingualTextValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $monolingualTextValue, $value )
		);

		$this->assertEquals(
			$queryString,
			$instance->newDescription( $monolingualTextValue, $value )->getQueryString()
		);
	}

	public function valueProvider() {

		#0
		$provider[] = [
			'Jan;1970',
			'\SMW\Query\Language\Conjunction',
			'[[Text::Jan;1970]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		];

		#1
		$provider[] = [
			'Jan;1970',
			'\SMW\Query\Language\SomeProperty',
			'[[Text::Jan;1970]]',
			SMW_DV_NONE
		];

		#2
		$provider[] = [
			'Jan@en',
			'\SMW\Query\Language\Conjunction',
			'[[Text::Jan]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		#3
		$provider[] = [
			'@en',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		#4
		$provider[] = [
			'@EN',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		#5
		$provider[] = [
			'@~zh*',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::~zh*]]',
			SMW_DV_MLTV_LCODE
		];

		#6
		$provider[] = [
			'?',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		];

		#7
		$provider[] = [
			'?',
			'\SMW\Query\Language\SomeProperty',
			'[[Text::+]]',
			SMW_DV_NONE
		];

		#8
		$provider[] = [
			'',
			'\SMW\Query\Language\ThingDescription',
			'',
			SMW_DV_MLTV_LCODE
		];

		return $provider;
	}

}
