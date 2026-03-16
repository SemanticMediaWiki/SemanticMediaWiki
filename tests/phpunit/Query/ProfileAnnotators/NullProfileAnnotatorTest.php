<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataModel\ContainerSemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\NullProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileAnnotatorTest extends TestCase {

	public function testCanConstruct() {
		$container = $this->getMockBuilder( '\SMWDIContainer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			NullProfileAnnotator::class,
			new NullProfileAnnotator( $container )
		);
	}

	public function testMethodAccess() {
		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', '_QUERYadcb944aa33b2c972470b73964c547c0' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$instance = new NullProfileAnnotator(
			$container
		);

		$instance->addAnnotation();

		$this->assertInstanceOf(
			DIProperty::class,
			$instance->getProperty()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getContainer()
		);

		$this->assertInstanceOf(
			ContainerSemanticData::class,
			$instance->getSemanticData()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

}
