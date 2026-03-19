<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;

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
		$container = $this->getMockBuilder( Container::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			NullProfileAnnotator::class,
			new NullProfileAnnotator( $container )
		);
	}

	public function testMethodAccess() {
		$subject = new WikiPage( __METHOD__, NS_MAIN, '', '_QUERYadcb944aa33b2c972470b73964c547c0' );

		$container = new Container(
			new ContainerSemanticData( $subject	)
		);

		$instance = new NullProfileAnnotator(
			$container
		);

		$instance->addAnnotation();

		$this->assertInstanceOf(
			Property::class,
			$instance->getProperty()
		);

		$this->assertInstanceOf(
			Container::class,
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
