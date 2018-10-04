<?php

namespace SMW\Tests\Query\Language;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

/**
 * @covers \SMW\Query\Language\Conjunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConjunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			new Conjunction()
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			new \SMWConjunction()
		);
	}

	/**
	 * @dataProvider conjunctionProvider
	 */
	public function testCommonMethods( $descriptions, $expected ) {

		$instance = new Conjunction( $descriptions );

		$this->assertEquals(
			$expected['descriptions'],
			$instance->getDescriptions()
		);

		$this->assertEquals(
			$expected['queryString'],
			$instance->getQueryString()
		);

		$this->assertEquals(
			$expected['queryStringAsValue'],
			$instance->getQueryString( true )
		);

		$this->assertEquals(
			$expected['isSingleton'],
			$instance->isSingleton()
		);

		$this->assertEquals(
			[],
			$instance->getPrintRequests()
		);

		$this->assertEquals(
			$expected['size'],
			$instance->getSize()
		);

		$this->assertEquals(
			$expected['depth'],
			$instance->getDepth()
		);

		$this->assertEquals(
			$expected['queryFeatures'],
			$instance->getQueryFeatures()
		);
	}

	/**
	 * @dataProvider comparativeHashProvider
	 */
	public function testGetFingerprint( $descriptions, $compareTo, $expected ) {

		$instance = new Conjunction(
			$descriptions
		);

		$this->assertEquals(
			$expected,
			$instance->getFingerprint() === $compareTo->getFingerprint()
		);
	}

	public function conjunctionProvider() {

		$nsHelp = Localizer::getInstance()->getNamespaceTextById( NS_HELP );

		$descriptions = [
			'N:cfcd208495d565ef66e7dff9f98764da' => new NamespaceDescription( NS_MAIN ),
			'N:c20ad4d76fe97759aa27a0c99bff6710' => new NamespaceDescription( NS_HELP )
		];

		$provider[] = [
			$descriptions,
			[
				'descriptions'  => $descriptions,
				'queryString' => "[[:+]] [[{$nsHelp}:+]]",
				'queryStringAsValue' => " <q>[[:+]] [[{$nsHelp}:+]]</q> ",
				'isSingleton' => false,
				'queryFeatures' => 24,
				'size'  => 2,
				'depth' => 0
			]
		];

		$valueDescriptionFoo = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) );
		$valueDescriptionBar = new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) );
		$valueDescriptionYim = new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) );

		$descriptions = [
			$valueDescriptionFoo,
			new Conjunction( [
				$valueDescriptionBar,
				$valueDescriptionYim
			] )
		];

		$description = [
			'V:903e513c13559ffaa66a23270a2922ff' => $valueDescriptionFoo,
			'V:246b70c7cb6a9fe4613cad14405b682f' => $valueDescriptionBar,
			'V:a3f71a427c6f9533ea1f093ff47bf958' => $valueDescriptionYim
		];

		$provider[] = [
			$descriptions,
			[
				'descriptions'  => $description,
				'queryString' => '[[:Foo]] [[:Bar]] [[:Yim]]',
				'queryStringAsValue' => ' <q>[[:Foo]] [[:Bar]] [[:Yim]]</q> ',
				'isSingleton' => true,
				'queryFeatures' => 16,
				'size'  => 3,
				'depth' => 0
			]
		];

		return $provider;
	}


	public function testPrune() {

		$valueDescriptionFoo = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) );
		$valueDescriptionBar = new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) );

		$descriptions = [
			$valueDescriptionFoo,
			$valueDescriptionBar,
		];

		$instance = new Conjunction( $descriptions );

		$maxsize  = 1;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			$valueDescriptionFoo,
			$instance->prune( $maxsize, $maxDepth, $log )
		);

		$maxsize  = 0;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			new ThingDescription(),
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

	public function comparativeHashProvider() {

		$descriptions = [
			new NamespaceDescription( NS_MAIN ),
			new NamespaceDescription( NS_HELP )
		];

		$conjunction = new Conjunction(
			$descriptions
		);

		$provider[] = [
			$descriptions,
			$conjunction,
			true
		];

		// Different order, same hash
		$descriptions = [
			new NamespaceDescription( NS_HELP ),
			new NamespaceDescription( NS_MAIN ) // Changed position
		];

		$conjunction = new Conjunction(
			$descriptions
		);

		$provider[] = [
			$descriptions,
			$conjunction,
			true
		];

		// ThingDescription is neglected
		$conjunction = new Conjunction(
			$descriptions
		);

		$conjunction->addDescription(
			new ThingDescription()
		);

		$provider[] = [
			$descriptions,
			$conjunction,
			true
		];

		// Adds description === different signature === different hash
		$conjunction = new Conjunction(
			$descriptions
		);

		$conjunction->addDescription(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) )
		);

		$provider[] = [
			$descriptions,
			$conjunction,
			false
		];

		return $provider;
	}

}
