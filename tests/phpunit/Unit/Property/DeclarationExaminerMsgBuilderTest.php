<?php

namespace SMW\Tests\Property;

use SMW\Property\DeclarationExaminerMsgBuilder;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminerMsgBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerMsgBuilderTest extends \PHPUnit_Framework_TestCase {

	private $declarationExaminer;

	protected function setUp() {
		parent::setUp();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DeclarationExaminerMsgBuilder::class,
			new DeclarationExaminerMsgBuilder()
		);
	}

	/**
	 * @dataProvider messageProvider
	 */
	public function testBuildHTML( $messages, $expected ) {

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->will( $this->returnValue( $messages ) );

		$instance = new DeclarationExaminerMsgBuilder();

		$this->assertEquals(
			$expected,
			$instance->buildHTML( $this->declarationExaminer )
		);
	}

	public function messageProvider() {

		if ( version_compare( MW_VERSION, '1.28', '<' ) ) {
			yield [
				[ [ 'plain', 'foo' ] ],
				'<div id="foo" class="plainlinks foo">&lt;foo&gt;</div>'
			];

			// ranking
			yield [
				[ [ 'plain', 'bar' ], [ 'error', 'foo' ] ],
				'<div id="foo" class="plainlinks foo smw-callout smw-callout-error">&lt;foo&gt;</div><div id="bar" class="plainlinks bar">&lt;bar&gt;</div>'
			];

			//_list
			yield [
				[ [ 'error', '_msgkey' => 'foo', '_list' => [ 'a', 'b' ] ] ],
				'<div id="foo" class="plainlinks foo smw-callout smw-callout-error">&lt;foo&gt;<ul><li>a</li><li>b</li></ul></div>'
			];

			// _merge
			yield [
				[ [ 'error', '_merge' => [ 'a', 'b' ] ] ],
				'<div id="a-b" class="plainlinks a-b smw-callout smw-callout-error">&lt;a&gt;&nbsp;&lt;b&gt;</div>'
			];

			// Unknown type
			yield [
				[ [ '__foobar__', 'foo' ] ],
				''
			];
		} else {
			yield [
				[ [ 'plain', 'foo' ] ],
				'<div id="foo" class="plainlinks foo">⧼foo⧽</div>'
			];

			// ranking
			yield [
				[ [ 'plain', 'bar' ], [ 'error', 'foo' ] ],
				'<div id="foo" class="plainlinks foo smw-callout smw-callout-error">⧼foo⧽</div><div id="bar" class="plainlinks bar">⧼bar⧽</div>'
			];

			//_list
			yield [
				[ [ 'error', '_msgkey' => 'foo', '_list' => [ 'a', 'b' ] ] ],
				'<div id="foo" class="plainlinks foo smw-callout smw-callout-error">⧼foo⧽<ul><li>a</li><li>b</li></ul></div>'
			];

			// _merge
			yield [
				[ [ 'error', '_merge' => [ 'a', 'b' ] ] ],
				'<div id="a-b" class="plainlinks a-b smw-callout smw-callout-error">⧼a⧽&nbsp;⧼b⧽</div>'
			];

			// Unknown type
			yield [
				[ [ '__foobar__', 'foo' ] ],
				''
			];
		}
	}

}
