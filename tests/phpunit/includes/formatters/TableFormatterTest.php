<?php

namespace SMW\Test;

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
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class TableFormatterTest extends SemanticMediaWikiTestCase {

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

		$matcher = array( 'tag' => 'span', 'content' => 'lala' );
		$this->assertTag( $matcher, $instance->getHeaderItems() );
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

		$matcher = array( 'tag' => 'th', 'content' => 'lala' );
		$this->assertTag( $matcher, $method->invoke( $instance ) );

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'lila' );
		$instance->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableHeader' );
		$method->setAccessible( true );

		$matcher = array(
			'tag' => 'thead',
			'child' => array(
				'tag' => 'th',
				'content' => 'lila'
			)
		);

		$this->assertTag( $matcher, $method->invoke( $instance ) );

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

		$matcher = array(
			'tag' => 'tr',
			'attributes' => array( 'class' => 'foo row-odd' ),
			'attributes' => array( 'class' => 'row-even' ),
			'descendant' => array(
				'tag' => 'td',
				'content' => 'lala'
			),
			'descendant' => array(
				'tag' => 'td',
				'content' => 'lula'
			)
		);

		$this->assertTag( $matcher, $method->invoke( $instance ) );

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableCell( 'lila' )->addTableRow()->getTable();

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getTableRows' );
		$method->setAccessible( true );

		$matcher = array(
			'tag' => 'tbody',
			'descendant' => array(
				'tag' => 'tr',
				'attributes' => array(
					'class' => 'row-odd'
				),
				'child' => array(
					'tag' => 'td',
					'content' => 'lila'
				),
			)
		);

		$this->assertTag( $matcher, $method->invoke( $instance ) );

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

		$matcher = array(
			'tag' => 'table',
			'descendant' => array(
				'tag' => 'tr',
				'attributes' => array( 'class' => 'foo row-odd' ),
				'child' => array(
					'tag' => 'td',
					'content' => 'lala',
					'attributes' => array( 'rel' => 'tuuu' )
				)
			)
		);

		$this->assertTag( $matcher, $instance->getTable() );

		// Head + row + cell
		$instance = $this->getInstance();
		$instance->addTableHeader( 'lula' )
			->addTableCell( 'lala' )
			->addTableRow();

		$matcher = array(
			'tag' => 'table',
			'descendant' => array(
				'tag' => 'th',
				'child' => array( 'tag' => 'td', 'content' => 'lula' ),
			),
			'descendant' => array(
				'tag' => 'tr',
				'attributes' => array( 'class' => 'row-odd' ),
				'child' => array( 'tag' => 'td', 'content' => 'lala' ),
			)
		);

		$this->assertTag( $matcher, $instance->getTable() );

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'lula' )
			->addTableCell( 'lala' )
			->addTableRow();

		// Doing a lazy check here ...
		$matcher = array(
			'tag' => 'table',
			'descendant' => array(
				'tag' => 'thead',
				'child' => array( 'content' => 'lula' )
			),
			'descendant' => array(
				'tag' => 'tbody',
				'child' => array( 'content' => 'lala' )
			),
		);

		$this->assertTag( $matcher, $instance->getTable() );
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

		$matcher = array(
			'tag' => 'tr',
			'attributes' => array( 'class' => 'row-odd' ),
			'child' => array(
				'tag' => 'th',
				'content' => 'Foo'
			),
			'descendant' => array(
				'tag' => 'td',
				'content' => 'lala',
				'attributes' => array( 'class' => 'foo' ),
			),
			'descendant' => array(
				'tag' => 'td',
				'content' => 'lula',
				'attributes' => array( 'rel' => 'tuuu' )
			),
			'tag' => 'tr',
			'attributes' => array( 'class' => 'row-even' ),
			'child' => array(
				'tag' => 'th',
				'content' => 'Bar'
			),
			'descendant' => array(
				'tag' => 'td',
				'content' => ''
			),
			'descendant' => array(
				'tag' => 'td',
				'content' => 'lila'
			),
		);

		$this->assertTag( $matcher, $instance->transpose( true )->getTable() );

		// HTML context
		$instance = $this->getInstance( true );
		$instance->addTableHeader( 'Foo' )->addTableHeader( 'Bar' )
			->addTableCell( 'lala', array( 'class' => 'foo' ) )
			->addTableRow()
			->addTableCell( 'lula' )->addTableCell( 'lila' )
			->addTableRow();

		$matcher = array(
			'tag' => 'thead',
			'tag' => 'tbody',
			'child' => array(
				'tag' => 'tr',
				'attributes' => array( 'class' => 'row-odd' ),
				'child' => array(
					'tag' => 'th',
					'content' => 'Foo'
				),
				'descendant' => array(
					'tag' => 'td',
					'content' => 'lala',
					'attributes' => array( 'class' => 'foo' ),
				),
				'descendant' => array(
					'tag' => 'td',
					'content' => 'lula'
				),
				'tag' => 'tr',
				'attributes' => array( 'class' => 'row-even' ),
				'child' => array(
					'tag' => 'th',
					'content' => 'Bar'
				),
				'descendant' => array(
					'tag' => 'td',
					'content' => ''
				),
				'descendant' => array(
					'tag' => 'td',
					'content' => 'lila'
				),
			)
		);

		$this->assertTag( $matcher, $instance->transpose( true )->getTable() );

	}
}
