<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\NamespaceFilter;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use SMW\Schema\CompartmentIterator;

/**
 * @covers \SMW\Schema\Filters\NamespaceFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class NamespaceFilterTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NamespaceFilter::class,
			new NamespaceFilter( null )
		);
	}

	public function testGetName() {
		$instance = new NamespaceFilter( null );

		$this->assertEquals(
			'namespace',
			$instance->getName()
		);
	}

	public function testIfCondition() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	'if.namespace' );

		$instance = new NamespaceFilter( null );
		$instance->filter( $compartment );
	}

	public function testNoCondition_FilterNotRequired() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	'if.namespace' );

		$instance = new NamespaceFilter( NS_MAIN );
		$instance->addOption( NamespaceFilter::FILTER_CONDITION_NOT_REQUIRED, true );

		$instance->filter( $compartment );

		$this->assertEquals(
			[ $compartment ],
			$instance->getMatches()
		);
	}

	/**
	 * @dataProvider namespaceFilterProvider
	 */
	public function testHasMatches_Compartment( $ns, $compartment, $expected ) {
		$instance = new NamespaceFilter(
			$ns
		);

		$instance->filter(
			new Compartment( $compartment )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	/**
	 * @dataProvider namespaceFilterProvider
	 */
	public function testHasMatches_Rule( $ns, $compartment, $expected ) {
		$instance = new NamespaceFilter(
			$ns
		);

		$rule = new Rule(
			$compartment
		);

		$instance->filter( $rule );

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);

		$this->assertEquals(
			$expected ? 1 : 0,
			$rule->filterScore
		);
	}

	/**
	 * @dataProvider namespaceFilterProvider
	 */
	public function testHasMatches_CompartmentIterator( $ns, $compartment, $expected ) {
		$instance = new NamespaceFilter(
			$ns
		);

		$instance->filter(
			new CompartmentIterator( [ $compartment ] )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	public function namespaceFilterProvider() {
		yield 'oneOf.1: single one_of' => [
			NS_MAIN,
			[
				'if' => [
					'namespace' => 'NS_MAIN'
				]
			],
			true
		];

		yield 'oneOf.2: single one_of no_match' => [
			NS_HELP,
			[
				'if' => [
					'namespace' => 'NS_MAIN'
				]
			],
			false
		];

		yield 'oneOf.3: multiple one_of' => [
			NS_HELP,
			[
				'if' => [
					'namespace' => [ 'NS_MAIN', 'NS_HELP' ]
				]
			],
			true
		];

		yield 'oneOf.4: multiple one_of no_match' => [
			NS_FILE,
			[
				'if' => [
					'namespace' => [ 'NS_MAIN', 'NS_HELP' ]
				]
			],
			false
		];

		yield 'oneOf.5: multiple one_of match, mixed with integer' => [
			NS_FILE,
			[
				'if' => [
					'namespace' => [ 'NS_MAIN', NS_FILE ]
				]
			],
			true
		];
	}

}
