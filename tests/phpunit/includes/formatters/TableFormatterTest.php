<?php

namespace SMW\Test;

use SMW\Tests\Util\UtilityFactory;
use SMW\TableFormatter;

use ReflectionClass;

/**
 * Tests for the TableFormatter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\TableFormatter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class TableFormatterTest extends SemanticMediaWikiTestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\TableFormatter';
	}

	/**
	 * Helper method that returns a TableFormatter object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return TableFormatter
	 */
	private function getInstance( $htmlContext = false ) {
		return new TableFormatter( $htmlContext );
	}

	/**
	 * @test TableFormatter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test TableFormatter::addHeaderItem
	 * @test TableFormatter::getHeaderItems
	 *
	 * @since 1.9
	 */
	public function testAddHeaderItem() {
		$instance = $this->getInstance();
		$instance->addHeaderItem( 'span', 'lala' );

		$this->stringValidator->assertThatStringContains(
			'<span>lala</span>',
			$instance->getHeaderItems()
		);
	}

	/**
	 * @test TableFormatter::addTableHeader
	 * @test TableFormatter::getTableHeader
	 *
	 * @since 1.9
	 */
	public function testAddTableHeader() {

		$instance = $this->getInstance();
		$instance->addTableHeader( 'lala' );
		$instance->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableHeader' );
		$method->setAccessible( true );

		$this->stringValidator->assertThatStringContains(
			'<th>lala</th>',
			$method->invoke( $instance )
		);

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'lila' );
		$instance->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableHeader' );
		$method->setAccessible( true );

		$this->stringValidator->assertThatStringContains(
			'<thead><th>lila</th></thead>',
			$method->invoke( $instance )
		);
	}

	/**
	 * @test TableFormatter::addTableRow
	 * @test TableFormatter::addTableCell
	 *
	 * @since 1.9
	 */
	public function testAddTableRow() {

		$instance = $this->getInstance();
		$instance->addTableCell( 'lala', array( 'class' => 'foo' ) )
			->addTableRow()
			->addTableCell( 'lula' )
			->addTableRow()
			->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableRows' );
		$method->setAccessible( true );

		$this->stringValidator->assertThatStringContains(
			'<tr class="row-odd"><td class="foo">lala</td></tr><tr class="row-even"><td>lula</td></tr>',
			$method->invoke( $instance )
		);

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableCell( 'lila' )->addTableRow()->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableRows' );
		$method->setAccessible( true );

		$this->stringValidator->assertThatStringContains(
			'<tbody><tr class="row-odd"><td>lila</td></tr></tbody>',
			$method->invoke( $instance )
		);
	}

	/**
	 * @test TableFormatter::getTable
	 *
	 * @since 1.9
	 */
	public function testStandardTable() {

		$instance = $this->getInstance();

		// Row + cell
		$instance->addTableCell( 'lala', array( 'rel' => 'tuuu' ) )
			->addTableRow( array( 'class' => 'foo' ) );

		$this->stringValidator->assertThatStringContains(
			'<table><tr class="foo row-odd"><td rel="tuuu">lala</td></tr></table>',
			$instance->getTable()
		);

		// Head + row + cell
		$instance = $this->getInstance();
		$instance->addTableHeader( 'lula' )
			->addTableCell( 'lala' )
			->addTableRow();

		$this->stringValidator->assertThatStringContains(
			'<table><th>lula</th><tr class="row-odd"><td>lala</td></tr></table>',
			$instance->getTable()
		);

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'lula' )
			->addTableCell( 'lala' )
			->addTableRow();

		$this->stringValidator->assertThatStringContains(
			'<table><thead><th>lula</th></thead><tbody><tr class="row-odd"><td>lala</td></tr></tbody></table>',
			$instance->getTable()
		);
	}

	/**
	 * @test TableFormatter::getTable
	 *
	 * @since 1.9
	 */
	public function testEmptyTable() {
		$instance = $this->getInstance();
		$instance->addTableCell()->addTableRow();
		$this->assertEmpty( $instance->getTable() );
	}

	/**
	 * @test TableFormatter::getTable
	 * @test TableFormatter::transpose
	 *
	 * @since 1.9
	 */
	public function testTransposedTable() {

		$instance = $this->getInstance();

		// We need a dedicated header definition to support a table transpose
		$instance->addTableHeader( 'Foo' )->addTableHeader( 'Bar' )
			->addTableCell( 'lala', array( 'class' => 'foo' ) )
			->addTableRow()
			->addTableCell( 'lula', array( 'rel' => 'tuuu' ) )->addTableCell( 'lila' )
			->addTableRow();

		$this->stringValidator->assertThatStringContains(
			'<table><tr class="row-odd"><th>Foo</th><td class="foo">lala</td><td rel="tuuu">lula</td></tr><tr class="row-even"><th>Bar</th><td></td><td>lila</td></tr></table>',
			$instance->transpose( true )->getTable()
		);

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'Foo' )->addTableHeader( 'Bar' )
			->addTableCell( 'lala', array( 'class' => 'foo' ) )
			->addTableRow()
			->addTableCell( 'lula' )->addTableCell( 'lila' )
			->addTableRow();

		$this->stringValidator->assertThatStringContains(
			'<table><thead></thead><tbody><tr class="row-odd"><th>Foo</th><td class="foo">lala</td><td>lula</td></tr><tr class="row-even"><th>Bar</th><td></td><td>lila</td></tr></tbody></table>',
			$instance->transpose( true )->getTable()
		);
	}

}
