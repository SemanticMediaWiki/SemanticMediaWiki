<?php

namespace SMW\Tests\Unit\Query\DescriptionBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DataValueFactory;
use SMW\DataValues\MonolingualTextValue;
use SMW\Query\DescriptionBuilders\MonolingualTextValueDescriptionBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\Query\DescriptionBuilders\MonolingualTextValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueDescriptionBuilderTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MonolingualTextValueDescriptionBuilder::class,
			new MonolingualTextValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForTimeValue() {
		$dataValue = $this->getMockBuilder( MonolingualTextValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new MonolingualTextValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	public function testNonStringThrowsException() {
		$recordValue = $this->getMockBuilder( MonolingualTextValue::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MonolingualTextValueDescriptionBuilder();

		$this->expectException( 'InvalidArgumentException' );
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
		# 0
		$provider[] = [
			'Jan;1970',
			Conjunction::class,
			'[[Text::Jan;1970]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		];

		# 1
		$provider[] = [
			'Jan;1970',
			SomeProperty::class,
			'[[Text::Jan;1970]]',
			SMW_DV_NONE
		];

		# 2
		$provider[] = [
			'Jan@en',
			Conjunction::class,
			'[[Text::Jan]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		# 3
		$provider[] = [
			'@en',
			Conjunction::class,
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		# 4
		$provider[] = [
			'@EN',
			Conjunction::class,
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		];

		# 5
		$provider[] = [
			'@~zh*',
			Conjunction::class,
			'[[Text::+]] [[Language code::~zh*]]',
			SMW_DV_MLTV_LCODE
		];

		# 6
		$provider[] = [
			'?',
			Conjunction::class,
			'[[Text::+]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		];

		# 7
		$provider[] = [
			'?',
			SomeProperty::class,
			'[[Text::+]]',
			SMW_DV_NONE
		];

		# 8
		$provider[] = [
			'',
			ThingDescription::class,
			'',
			SMW_DV_MLTV_LCODE
		];

		return $provider;
	}

}
