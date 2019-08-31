<?php

namespace SMW\Tests\MediaWiki\Template;

use SMW\MediaWiki\Template\TemplateExpander;
use SMW\MediaWiki\Template\Template;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Template\TemplateExpander
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class TemplateExpanderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $parser;

	protected function setUp() {
		parent::setUp();

		$this->parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TemplateExpander::class,
			 new TemplateExpander( $this->parser )
		);
	}

	public function testExpand() {

		$template = new Template( 'Foo' );

		$this->parser->expects( $this->once() )
			->method( 'preprocess' )
			->with( $this->equalTo( '{{Foo}}' ) );

		$instance = new TemplateExpander(
			$this->parser
		);

		$instance->expand( $template );
	}

	public function testExpandOnInvalidParserThrowsException() {

		$instance = new TemplateExpander(
			'Foo'
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->expand( '' );
	}

}
