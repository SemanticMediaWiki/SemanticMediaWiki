<?php

namespace SMW\Tests\Query\Language;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ValueDescription;

use SMW\DIWikiPage;
use SMW\Localizer;

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

		$this->assertEquals( $expected['descriptions'], $instance->getDescriptions() );

		$this->assertEquals( $expected['queryString'], $instance->getQueryString() );
		$this->assertEquals( $expected['queryStringAsValue'], $instance->getQueryString( true ) );

		$this->assertEquals( $expected['isSingleton'], $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( $expected['size'], $instance->getSize() );
		$this->assertEquals( $expected['depth'], $instance->getDepth() );
		$this->assertEquals( $expected['queryFeatures'], $instance->getQueryFeatures() );
	}

	public function conjunctionProvider() {

		$nsHelp = Localizer::getInstance()->getNamespaceTextById( NS_HELP );

		$descriptions = array(
			new NamespaceDescription( NS_MAIN ),
			new NamespaceDescription( NS_HELP )
		);

		$provider[] = array(
			$descriptions,
			array(
				'descriptions'  => $descriptions,
				'queryString' => "[[:+]] [[{$nsHelp}:+]]",
				'queryStringAsValue' => " <q>[[:+]] [[{$nsHelp}:+]]</q> ",
				'isSingleton' => false,
				'queryFeatures' => 24,
				'size'  => 2,
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
				'queryString' => '[[:Foo]] [[:Bar]] [[:Yim]]',
				'queryStringAsValue' => ' <q>[[:Foo]] [[:Bar]] [[:Yim]]</q> ',
				'isSingleton' => true,
				'queryFeatures' => 16,
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

		$instance = new Conjunction( $descriptions );

		$maxsize  = 1;
		$maxDepth = 1;
		$log      = array();

		$this->assertEquals(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
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
