<?php

namespace SMW\Tests\Integration\Query\ResultPrinter;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use Title;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class TemplateQueryResultPrinterIntegrationTest extends MwDBaseUnitTestCase {

	private $subjects = array();
	private $pageCreator;

	private $stringBuilder;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		$pageDeleter
			->doDeletePoolOfPages( $this->subjects );

		parent::tearDown();
	}

	/**
	 * @query {{#ask: [[Category:...]][[Has page value::...]] |template=... }}
	 */
	public function testTemplateOutputUsingUnnamedArgumentsForNonUnicode() {

		$this->stringBuilder
			->addString( '<includeonly>' )
			->addString( '[{{{#}}}]:' )
			->addString( '{{{1}}}:' )
			->addString( '{{{2}}}:' )
			->addString( '{{{userparam}}}:' )
			->addString( '</includeonly>' );

		$this->pageCreator
			->createPage( Title::newFromText( 'TemplateOutputUsingUnnamedArgumentsForNonUnicode', NS_TEMPLATE ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->stringBuilder
			->addString( '<includeonly>' )
			->addString( '<div>{{{userparam}}}</div>' )
			->addString( '</includeonly>' );

		$this->pageCreator
			->createPage( Title::newFromText( 'TemplateOutputUsingUnnamedArgumentsForNonUnicodeExtra', NS_TEMPLATE ) )
			->doEdit( $this->stringBuilder->getString() );

		foreach ( array( 'Foo', 'Bar', '123', 'yxz' ) as $title ) {

			$this->stringBuilder
				->addString( '[[Category:TemplateOutputUsingUnnamedArgumentsForNonUnicode]]' )
				->addString( '[[Has page value::ABC]]' )
				->addString( '[[Has page value::DEF]]' );

			$this->pageCreator
				->createPage( Title::newFromText( $title ) )
				->doEdit( $this->stringBuilder->getString() );

			$this->subjects[] = $this->pageCreator->getPage();
		}

		$this->stringBuilder
			->addString( '{{#ask:' )
			->addString( '[[Category:TemplateOutputUsingUnnamedArgumentsForNonUnicode]][[Has page value::ABC]]' )
			->addString( '|?Has page value' )
			->addString( '|format=template' )
			->addString( '|order=asc' )
			->addString( '|link=none' )
			->addString( '|limit=3' )
			->addString( '|searchlabel=furtherresults' )
			->addString( '|userparam=[$%&*==42]' )
			->addString( '|template=TemplateOutputUsingUnnamedArgumentsForNonUnicode' )
			->addString( '|introtemplate=TemplateOutputUsingUnnamedArgumentsForNonUnicodeExtra' )
			->addString( '|outrotemplate=TemplateOutputUsingUnnamedArgumentsForNonUnicodeExtra' )
			->addString( '}}' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->subjects[] = $this->pageCreator->getPage();

		$parserOutput = $this->pageCreator->getEditInfo()->output;

		$this->stringBuilder
			->addString( '<div>[$%&amp;*==42]</div>' )
			->addString( '[0]:123:ABC, DEF:[$%&amp;*==42]:' )
			->addString( '[1]:Bar:ABC, DEF:[$%&amp;*==42]:' )
			->addString( '[2]:Foo:ABC, DEF:[$%&amp;*==42]:' )

			// #885
			->addString( '<div>[$%&amp;*==42]</div><span class="smw-template-furtherresults">' );

		$this->stringValidator->assertThatStringContains(
			$this->stringBuilder->getString(),
			$parserOutput->getText()
		);
	}

	/**
	 * @query {{#ask: [[Category:...]][[Has page value::...]] |template=... }}
	 */
	public function testTemplateOutputUsingNamedArgumentsForUnicodeIncludedSubject() {

		$this->skipTestForDatabase(
			'postgres',
			'Skipping the test because unicode needs special treatment in postgres'
		);

		$this->stringBuilder
			->addString( '<includeonly>' )
			->addString( '[{{{#}}}]:' )
			->addString( '{{{1}}}:' )
			->addString( '{{{?Has page value}}}:' )
			->addString( '{{{userparam}}}:' )
			->addString( '</includeonly>' );

		$this->pageCreator
			->createPage( Title::newFromText( 'TemplateOutputUsingNamedArgumentsForUnicodeIncludedSubject', NS_TEMPLATE ) )
			->doEdit( $this->stringBuilder->getString() );

		foreach ( array( 'Foo', 'Bar', 'テスト', '123' ) as $title ) {

			$this->stringBuilder
				->addString( '[[Category:TemplateOutputUsingNamedArgumentsForUnicodeIncludedSubject]]' )
				->addString( '[[Has page value::123]]' )
				->addString( '[[Has page value::456]]' );

			$this->pageCreator
				->createPage( Title::newFromText( $title ) )
				->doEdit( $this->stringBuilder->getString() );

			$this->subjects[] = $this->pageCreator->getPage();
		}

		$this->stringBuilder
			->addString( '{{#ask:' )
			->addString( '[[Category:TemplateOutputUsingNamedArgumentsForUnicodeIncludedSubject]]<q>[[Has page value::123]] OR [[Has page value::456]]</q>' )
			->addString( '|?Has page value' )
			->addString( '|format=template' )
			->addString( '|order=asc' )
			->addString( '|named args=yes' )
			->addString( '|link=none' )
			->addString( '|sep=;' )
			->addString( '|userparam=[$%&*==42]' )
			->addString( '|template=TemplateOutputUsingNamedArgumentsForUnicodeIncludedSubject' )
			->addString( '}}' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->subjects[] = $this->pageCreator->getPage();

		$parserOutput = $this->pageCreator->getEditInfo()->output;

		$this->stringBuilder
			->addString( '[0]:123:123; 456:[$%&amp;*==42]:' )
			->addString( '[1]:Bar:123; 456:[$%&amp;*==42]:' )
			->addString( '[2]:Foo:123; 456:[$%&amp;*==42]:' )
			->addString( '[3]:テスト:123; 456:[$%&amp;*==42]:' );

		$this->stringValidator->assertThatStringContains(
			$this->stringBuilder->getString(),
			$parserOutput->getText()
		);
	}

}
