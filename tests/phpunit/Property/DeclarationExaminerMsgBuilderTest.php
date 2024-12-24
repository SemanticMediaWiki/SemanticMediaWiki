<?php

namespace SMW\Tests\Property;

use Html;
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
class DeclarationExaminerMsgBuilderTest extends \PHPUnit\Framework\TestCase {

	private $declarationExaminer;

	protected function setUp(): void {
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
			->willReturn( $messages );

		$instance = new DeclarationExaminerMsgBuilder();

		$this->assertEquals(
			$expected,
			$instance->buildHTML( $this->declarationExaminer )
		);
	}

	/**
	 * Return a message box using core MW method
	 * This is required because the output is different depending on the MW version
	 *
	 * @param string $type
	 * @param string $key
	 * @param string $content
	 * @return $string HTML of the message box
	 */
	private function getMessageBoxHTML( $type, $key, $content ) {
		$class = "plainlinks $key";
		switch ( $type ) {
			case 'plain':
				return Html::rawElement( 'div', [ 'class' => $class ], $content );
			case 'error':
				return Html::errorBox( $content, '', $class );
			case 'info':
				return Html::noticeBox( $content, $class );
			case 'warning':
				return Html::warningBox( $content, $class );
			default:
				return '';
		}
	}

	public function messageProvider() {
		yield [
			[ [ 'plain', 'foo' ] ],
			$this->getMessageBoxHTML( 'plain', 'foo', '⧼foo⧽' )
		];

		// Test message ranking: error messages should appear before plain messages
		yield [
			[ [ 'plain', 'bar' ], [ 'error', 'foo' ] ],
			$this->getMessageBoxHTML( 'error', 'foo', '⧼foo⧽' ) . $this->getMessageBoxHTML( 'plain', 'bar', '⧼bar⧽' )
		];

		// Test list rendering: messages with _list should generate HTML unordered lists
		yield [
			[ [ 'error', '_msgkey' => 'foo', '_list' => [ 'a', 'b' ] ] ],
			$this->getMessageBoxHTML( 'error', 'foo', '⧼foo⧽<ul><li>a</li><li>b</li></ul>' )
		];

		// Test message merging: multiple messages should be combined with proper spacing
		yield [
			[ [ 'error', '_merge' => [ 'a', 'b' ] ] ],
			$this->getMessageBoxHTML( 'error', 'a-b', '⧼a⧽&nbsp;⧼b⧽' )
		];

		// Test unknown type
		yield [
			[ [ '__foobar__', 'foo' ] ],
			''
		];
	}

}
