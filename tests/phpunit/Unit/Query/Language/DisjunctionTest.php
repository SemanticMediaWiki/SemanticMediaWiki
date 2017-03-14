<?php

namespace SMW\Tests\Query\Language;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

/**
 * @covers \SMW\Query\Language\Disjunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DisjunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			new Disjunction()
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			new \SMWDisjunction()
		);
	}

	/**
	 * @dataProvider disjunctionProvider
	 */
	public function testCommonMethods( $descriptions, $expected ) {

		$instance = new Disjunction( $descriptions );

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

		$instance = new Disjunction(
			$descriptions
		);

		$this->assertEquals(
			$expected,
			$instance->getFingerprint() === $compareTo->getFingerprint()
		);
	}

	public function disjunctionProvider() {

		$nsHelp = Localizer::getInstance()->getNamespaceTextById( NS_HELP );

		$descriptions = [
			'N:cfcd208495d565ef66e7dff9f98764da' => new NamespaceDescription( NS_MAIN ),
			'N:c20ad4d76fe97759aa27a0c99bff6710' => new NamespaceDescription( NS_HELP )
		];

		$provider[] = [
			$descriptions,
			[
				'descriptions'  => $descriptions,
				'queryString' => " <q>[[:+]] OR [[{$nsHelp}:+]]</q> ",
				'queryStringAsValue' => " <q>[[:+]]</q> || <q>[[{$nsHelp}:+]]</q> ",
				'isSingleton' => false,
				'queryFeatures' => 40,
				'size'  => 2,
				'depth' => 0
			]
		];

		$descriptions = [
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new Disjunction( [
				new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
				new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
			] )
		];

		$expectedDescriptions = [
			'V:03e5f313638479d132c1aeabd1eacc24' => new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			'V:26116b41f908d8ba2ce60d4f455c8d4d' => new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
			'V:f47714f302b181e713015c02c48cf86f' => new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
		];

		$provider[] = [
			$descriptions,
			[
				'descriptions'  => $expectedDescriptions,
				'queryString' => ' <q>[[:Foo]] OR [[:Bar]] OR [[:Yim]]</q> ',
				'queryStringAsValue' => 'Foo||Bar||Yim',
				'isSingleton' => false,
				'queryFeatures' => 32,
				'size'  => 3,
				'depth' => 0
			]
		];

		$descriptions = [
			'V:03e5f313638479d132c1aeabd1eacc24' => new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			'C:52a399e1faa619c79ecec246102125b8' => new Conjunction( [
				new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
				new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
			] )
		];

		$provider[] = [
			$descriptions,
			[
				'descriptions'  => $descriptions,
				'queryString' => ' <q>[[:Foo]] OR [[:Bar]] [[:Yim]]</q> ',
				'queryStringAsValue' => 'Foo|| <q>[[:Bar]] [[:Yim]]</q> ',
				'isSingleton' => false,
				'queryFeatures' => 48,
				'size'  => 3,
				'depth' => 0
			]
		];

		return $provider;
	}

	public function testPrune() {

		$descriptions = [
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
		];

		$instance = new Disjunction( $descriptions );

		$maxsize  = 2;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			$instance,
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

		$disjunction = new Disjunction(
			$descriptions
		);

		$provider[] = [
			$descriptions,
			$disjunction,
			true
		];

		// Different order, same hash
		$descriptions = [
			new NamespaceDescription( NS_HELP ),
			new NamespaceDescription( NS_MAIN ) // Changed position
		];

		$disjunction = new Disjunction(
			$descriptions
		);

		$provider[] = [
			$descriptions,
			$disjunction,
			true
		];

		// ThingDescription forces a different hash
		$disjunction = new Disjunction(
			$descriptions
		);

		$disjunction->addDescription(
			new ThingDescription()
		);

		$provider[] = [
			$descriptions,
			$disjunction,
			false
		];

		// Adds description === different signature === different hash
		$disjunction = new Disjunction(
			$descriptions
		);

		$disjunction->addDescription(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) )
		);

		$provider[] = [
			$descriptions,
			$disjunction,
			false
		];

		return $provider;
	}

}
