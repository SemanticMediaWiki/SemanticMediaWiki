<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ValueFilterFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateEngine;

	protected function setUp(): void {
		parent::setUp();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueFilterFactory::class,
			new ValueFilterFactory( $this->templateEngine )
		);
	}

	public function testCanConstructListValueFilter() {
		$instance = new ValueFilterFactory(
			$this->templateEngine
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\ListValueFilter',
			$instance->newListValueFilter( [] )
		);
	}

	public function testCanConstructCheckboxValueFilter() {
		$instance = new ValueFilterFactory(
			$this->templateEngine
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter',
			$instance->newCheckboxValueFilter( [] )
		);
	}

	public function testCanConstructCheckboxRangeGroupValueFilter() {
		$compartmentIterator = $this->getMockBuilder( '\SMW\Schema\CompartmentIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueFilterFactory(
			$this->templateEngine
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxRangeGroupValueFilter',
			$instance->newCheckboxRangeGroupValueFilter( $compartmentIterator, [] )
		);
	}

	public function testCanConstructRangeValueFilter() {
		$compartmentIterator = $this->getMockBuilder( '\SMW\Schema\CompartmentIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueFilterFactory(
			$this->templateEngine
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\RangeValueFilter',
			$instance->newRangeValueFilter( $compartmentIterator, [] )
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {
		// Available class methods to be tested
		$classMethods = get_class_methods( ValueFilterFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep( '/^testCanConstruct/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testCanConstruct', 'new', $testMethods )
		);

		foreach ( $classMethods as $name ) {

			if ( substr( $name, 0, 3 ) !== 'new' ) {
				continue;
			}

			$this->assertArrayHasKey( $name, $testMethods );
		}
	}

}
