<?php

namespace SMW\Tests\Property;

use PHPUnit\Framework\TestCase;
use SMW\Property\DeclarationExaminer;
use SMW\Property\DeclarationExaminerFactory;
use SMW\Property\DeclarationExaminerMsgBuilder;
use SMW\Store;

/**
 * @covers \SMW\Property\DeclarationExaminerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DeclarationExaminerFactory::class,
			new DeclarationExaminerFactory()
		);
	}

	public function testCanConstructDeclarationExaminerMsgBuilder() {
		$instance = new DeclarationExaminerFactory();

		$this->assertInstanceOf(
			DeclarationExaminerMsgBuilder::class,
			$instance->newDeclarationExaminerMsgBuilder()
		);
	}

	public function testCanConstructDeclarationExaminer() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DeclarationExaminerFactory();

		$this->assertInstanceOf(
			DeclarationExaminer::class,
			$instance->newDeclarationExaminer( $store )
		);
	}

}
