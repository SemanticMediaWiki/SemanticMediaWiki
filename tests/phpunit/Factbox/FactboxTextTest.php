<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\FactboxText;

/**
 * @covers \SMW\Factbox\FactboxText
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 4.1.1
 *
 * @author Morne Alberts
 */
class FactboxTextTest extends \PHPUnit_Framework_TestCase {

	public function testSetText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( 'Foo bar' );

		$this->assertSame(
			'Foo bar',
			$factboxText->getText()
		);
	}

	public function testHasText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( 'Foo bar' );

		$this->assertTrue(
			$factboxText->hasText()
		);
	}

	public function testDefaultDoesNotHaveText(): void {
		$factboxText = new FactboxText();

		$this->assertFalse(
			$factboxText->hasText()
		);
	}

	public function testClearDoesNotHaveText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( 'Foo Bar' );
		$factboxText->clear();

		$this->assertFalse(
			$factboxText->hasText()
		);
	}

	public function testHasNonEmptyText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( 'Foo bar' );

		$this->assertTrue(
			$factboxText->hasNonEmptyText()
		);
	}

	public function testClearDoesNotHaveNonEmptyText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( 'Foo Bar' );
		$factboxText->clear();

		$this->assertFalse(
			$factboxText->hasNonEmptyText()
		);
	}

	public function testEmptyStringDoesNotHaveNonEmptyText(): void {
		$factboxText = new FactboxText();

		$factboxText->setText( '' );

		$this->assertFalse(
			$factboxText->hasNonEmptyText()
		);
	}

}
