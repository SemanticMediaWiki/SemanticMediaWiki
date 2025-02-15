<?php

namespace SMW\Tests\MediaWiki\Template;

use SMW\MediaWiki\Template\Template;
use SMW\MediaWiki\Template\TemplateSet;

/**
 * @covers \SMW\MediaWiki\Template\TemplateSet
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class TemplateSetTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TemplateSet::class,
			 new TemplateSet()
		);
	}

	public function testAddTemplate() {
		$instance = new TemplateSet( [ 'foo' ] );
		$instance->addTemplate( new Template( 'Bar' ) );

		$this->assertSame(
			'foo{{Bar}}',
			$instance->text()
		);
	}

}
