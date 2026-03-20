<?php

namespace SMW\Tests\Unit\SQLStore\TableBuildExaminerFactory;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\CountMapField;
use SMW\SQLStore\TableBuilder\Examiner\EntityCollation;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\SQLStore\TableBuilder\TableBuildExaminerFactory;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableBuildExaminerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TableBuildExaminerFactoryTest extends TestCase {

	public function testCanConstructEntityCollation() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			EntityCollation::class,
			$instance->newEntityCollation( $store )
		);
	}

	public function testCanConstructCountMapField() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			CountMapField::class,
			$instance->newCountMapField( $store )
		);
	}

	public function testCanConstructHashField() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			HashField::class,
			$instance->newHashField( $store )
		);
	}

	public function testCanConstructFixedProperties() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			FixedProperties::class,
			$instance->newFixedProperties( $store )
		);
	}

	public function testCanConstructTouchedField() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			TouchedField::class,
			$instance->newTouchedField( $store )
		);
	}

	public function testCanConstructIdBorder() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			IdBorder::class,
			$instance->newIdBorder( $store )
		);
	}

	public function testCanConstructPredefinedProperties() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableBuildExaminerFactory();

		$this->assertInstanceOf(
			PredefinedProperties::class,
			$instance->newPredefinedProperties( $store )
		);
	}

}
