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

		$this->assertEquals( $expected['descriptions'], $instance->getDescriptions() );

		$this->assertEquals( $expected['queryString'], $instance->getQueryString() );
		$this->assertEquals( $expected['queryStringAsValue'], $instance->getQueryString( true ) );

		$this->assertEquals( $expected['isSingleton'], $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( $expected['size'], $instance->getSize() );
		$this->assertEquals( $expected['depth'], $instance->getDepth() );
		$this->assertEquals( $expected['queryFeatures'], $instance->getQueryFeatures() );
	}

	public function disjunctionProvider() {

		$nsHelp = Localizer::getInstance()->getNamespaceTextById( NS_HELP );

		$descriptions = array(
			new NamespaceDescription( NS_MAIN ),
			new NamespaceDescription( NS_HELP )
		);

		$provider[] = array(
			$descriptions,
			array(
				'descriptions'  => $descriptions,
				'queryString' => " <q>[[:+]] OR [[{$nsHelp}:+]]</q> ",
				'queryStringAsValue' => " <q>[[:+]]</q> || <q>[[{$nsHelp}:+]]</q> ",
				'isSingleton' => false,
				'queryFeatures' => 40,
				'size'  => 2,
				'depth' => 0
			)
		);

		$descriptions = array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new Disjunction( array(
				new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
				new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
			) )
		);

		$expectedDescriptions = array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
			new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
		);

		$provider[] = array(
			$descriptions,
			array(
				'descriptions'  => $expectedDescriptions,
				'queryString' => ' <q>[[:Foo]] OR [[:Bar]] OR [[:Yim]]</q> ',
				'queryStringAsValue' => 'Foo||Bar||Yim',
				'isSingleton' => false,
				'queryFeatures' => 32,
				'size'  => 3,
				'depth' => 0
			)
		);

		$descriptions = array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new Conjunction( array(
				new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
				new ValueDescription( new DIWikiPage( 'Yim', NS_MAIN ) )
			) )
		);

		$provider[] = array(
			$descriptions,
			array(
				'descriptions'  => $descriptions,
				'queryString' => ' <q>[[:Foo]] OR [[:Bar]] [[:Yim]]</q> ',
				'queryStringAsValue' => 'Foo|| <q>[[:Bar]] [[:Yim]]</q> ',
				'isSingleton' => false,
				'queryFeatures' => 48,
				'size'  => 3,
				'depth' => 0
			)
		);

		return $provider;
	}

	public function testPrune() {

		$descriptions = array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) ),
		);

		$instance = new Disjunction( $descriptions );

		$maxsize  = 2;
		$maxDepth = 1;
		$log      = array();

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);

		$maxsize  = 0;
		$maxDepth = 1;
		$log      = array();

		$this->assertEquals(
			new ThingDescription(),
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

}
