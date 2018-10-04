<?php

namespace SMW\Tests\Query\Language;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Query\Language\ClassDescription;
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
			'V:903e513c13559ffaa66a23270a2922ff' => new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			'V:246b70c7cb6a9fe4613cad14405b682f' => new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
			'V:a3f71a427c6f9533ea1f093ff47bf958' => new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
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
			'V:903e513c13559ffaa66a23270a2922ff' => new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			'C:d0da0541e2e099655342be3af203814e' => new Conjunction( [
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

	public function testVaryingHierarchyDepthCausesClassDescriptionToYieldDifferentFingerprint() {

		$descriptions = [
			new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) )
		];

		$instance = new Disjunction(
			$descriptions
		);

		$expected = $instance->getFingerprint();

		$descriptions = [
			new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) )
		];

		$instance = new Disjunction(
			$descriptions
		);

		$instance->setHierarchyDepth( 1 );

		$this->assertNotSame(
			$expected,
			$instance->getFingerprint()
		);
	}

}
