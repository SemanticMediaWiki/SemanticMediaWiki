<?php

namespace SMW\Tests;

use SMW\PropertyAliasFinder;

/**
 * @covers \SMW\PropertyAliasFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PropertyAliasFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$languageIndependentPropertyLabels = array();

		$this->assertInstanceOf(
			'\SMW\PropertyAliasFinder',
			new PropertyAliasFinder()
		);
	}

	public function testFindPropertyAliasById() {

		$propertyAliases = array( 'Bar' => '_Foo' );

		$instance = new PropertyAliasFinder(
			$propertyAliases
		);

		$this->assertEquals(
			$propertyAliases,
			$instance->getKnownPropertyAliases()
		);

		$this->assertEquals(
			'Bar',
			$instance->findPropertyAliasById( '_Foo' )
		);
	}

	public function testFindPropertyIdByAlias() {

		$canonicalPropertyAliases = array( 'Bar' => '_Foo' );

		$instance = new PropertyAliasFinder(
			array(),
			$canonicalPropertyAliases
		);

		$this->assertEquals(
			'_Foo',
			$instance->findPropertyIdByAlias( 'Bar' )
		);
	}

	public function testRegisterAliasByFixedLabel() {

		$instance = new PropertyAliasFinder();
		$instance->registerAliasByFixedLabel( '_Foo', 'Bar' );

		$this->assertEquals(
			'_Foo',
			$instance->findPropertyIdByAlias( 'Bar' )
		);
	}

}
