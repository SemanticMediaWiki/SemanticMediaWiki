<?php

namespace SMW\Tests\SQLStore\TableBuildExaminerFactory;

use SMW\SQLStore\TableBuilder\TableBuildExaminerFactory;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableBuildExaminerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableBuildExaminerFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructEntityCollation() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\EntityCollation',
			$instance->newEntityCollation( $store )
		);
	}

	public function testCanConstructCountMapField() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\CountMapField',
			$instance->newCountMapField( $store )
		);
	}

	public function testCanConstructHashField() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\HashField',
			$instance->newHashField( $store )
		);
	}

	public function testCanConstructFixedProperties() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\FixedProperties',
			$instance->newFixedProperties( $store )
		);
	}

	public function testCanConstructTouchedField() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\TouchedField',
			$instance->newTouchedField( $store )
		);
	}

	public function testCanConstructIdBorder() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\IdBorder',
			$instance->newIdBorder( $store )
		);
	}

	public function testCanConstructPredefinedProperties() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties',
			$instance->newPredefinedProperties( $store )
		);
	}

}
