<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxRangeGroupValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\ListValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\RangeValueFilter;
use SMW\Schema\CompartmentIterator;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ValueFilterFactoryTest extends TestCase {

	private $templateParser;

	protected function setUp(): void {
		parent::setUp();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueFilterFactory::class,
			new ValueFilterFactory( $this->templateParser )
		);
	}

	public function testCanConstructListValueFilter() {
		$instance = new ValueFilterFactory(
			$this->templateParser
		);

		$this->assertInstanceOf(
			ListValueFilter::class,
			$instance->newListValueFilter( [] )
		);
	}

	public function testCanConstructCheckboxValueFilter() {
		$instance = new ValueFilterFactory(
			$this->templateParser
		);

		$this->assertInstanceOf(
			CheckboxValueFilter::class,
			$instance->newCheckboxValueFilter( [] )
		);
	}

	public function testCanConstructCheckboxRangeGroupValueFilter() {
		$compartmentIterator = $this->getMockBuilder( CompartmentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueFilterFactory(
			$this->templateParser
		);

		$this->assertInstanceOf(
			CheckboxRangeGroupValueFilter::class,
			$instance->newCheckboxRangeGroupValueFilter( $compartmentIterator, [] )
		);
	}

	public function testCanConstructRangeValueFilter() {
		$compartmentIterator = $this->getMockBuilder( CompartmentIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueFilterFactory(
			$this->templateParser
		);

		$this->assertInstanceOf(
			RangeValueFilter::class,
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
