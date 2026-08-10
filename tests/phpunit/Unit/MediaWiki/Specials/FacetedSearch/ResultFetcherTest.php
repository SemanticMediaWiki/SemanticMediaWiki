<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SMW\Localizer\Message;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ResultFetcherTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultFetcher::class,
			new ResultFetcher( $this->store )
		);
	}

	public function testGetHtmlEscapesReflectedQueryError() {
		// `Message::encode` un-swaps %3C/%3E into < > after strip_tags, so a
		// crafted query chunk can smuggle live markup into the error stream.
		$error = Message::encode( [ '%3Eimg src=x onerror=alert(1)%3C' ] );

		$instance = new ResultFetcher( $this->store );
		$this->setErrors( $instance, [ $error ] );

		$html = $instance->getHtml();

		$this->assertStringNotContainsString( '<img src=x onerror=alert(1)>', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
	}

	private function setErrors( ResultFetcher $instance, array $errors ): void {
		$property = new ReflectionProperty( ResultFetcher::class, 'errors' );
		$property->setAccessible( true );
		$property->setValue( $instance, $errors );
	}

}
