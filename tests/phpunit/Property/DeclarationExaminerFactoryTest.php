<?php

namespace SMW\Tests\Property;

use SMW\Property\DeclarationExaminerFactory;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DeclarationExaminerFactory::class,
			new DeclarationExaminerFactory()
		);
	}

	public function testCanConstructDeclarationExaminerMsgBuilder() {

		$instance = new DeclarationExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\Property\DeclarationExaminerMsgBuilder',
			$instance->newDeclarationExaminerMsgBuilder()
		);
	}

	public function testCanConstructDeclarationExaminer() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DeclarationExaminerFactory();

		$this->assertInstanceOf(
			'\SMW\Property\DeclarationExaminer',
			$instance->newDeclarationExaminer( $store )
		);
	}

}
