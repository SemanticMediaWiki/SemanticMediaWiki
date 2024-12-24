<?php

namespace SMW\Tests\Utils\Mock;

use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use Wikimedia\Rdbms\Database;

/**
 * @codeCoverageIgnore
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiMockObjectRepository extends \PHPUnit\Framework\TestCase implements MockObjectRepository {

	/** @var MockObjectBuilder */
	protected $builder;

	/**
	 * @since 1.9
	 */
	public function registerBuilder( MockObjectBuilder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * @since 1.9
	 *
	 * @return User
	 */
	public function User() {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getUserPage' )
			->willReturn( $this->builder->setValue( 'getUserPage' ) );

		return $user;
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOptions
	 */
	public function ParserOptions() {
		$parserOptions = $this->getMockBuilder( 'ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->any() )
			->method( 'getTargetLanguage' )
			->willReturn( $this->builder->setValue( 'getTargetLanguage' ) );

		return $parserOptions;
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function ParserOutput() {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$parserOutput->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $parserOutput;
	}

	/**
	 * @since 1.9
	 *
	 * @return WikiPage
	 */
	public function WikiPage() {
		$wikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$wikiPage->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $wikiPage;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return WikiFilePage
	 */
	public function WikiFilePage() {
		$wikiPage = $this->getMockBuilder( 'WikiFilePage' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$wikiPage->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $wikiPage;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return File
	 */
	public function File() {
		$wikiPage = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$wikiPage->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $wikiPage;
	}

	/**
	 * @since 1.9
	 *
	 * @return Revision
	 */
	public function Revision() {
		$revision = $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$revision->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $revision;
	}

	/**
	 * @note This mock object avoids the involvement of LinksUpdate (which
	 * requires DB access) and returns a randomized LatestRevID/ArticleID
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function Title() {
		// When interacting with a "real" Parser object, the Parser expects in
		// in 1.21+ a content model to be present while in MW 1.19/1.20 such
		// object is not required. In order to avoid operational obstruction a
		// model is set as default and can if necessary individually be overridden
		$contentModel = defined( 'CONTENT_MODEL_WIKITEXT' ) ? CONTENT_MODEL_WIKITEXT : null;

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->willReturn( $this->builder->setValue( 'getDBkey', $this->builder->newRandomString( 10, 'Title-auto-dbkey' ) ) );

		$title->expects( $this->any() )
			->method( 'getInterwiki' )
			->willReturn( $this->builder->setValue( 'getInterwiki', '' ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( $this->builder->setValue( 'getArticleID', rand( 10, 10000 ) ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( $this->builder->setValue( 'getNamespace', NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'isKnown' )
			->willReturn( $this->builder->setValue( 'exists' ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( $this->builder->setValue( 'exists' ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( $this->builder->setValue( 'getLatestRevID', rand( 10, 5000 ) ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->willReturn( $this->builder->setValue( 'getText' ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $this->builder->setValue( 'getPrefixedText', $this->builder->newRandomString( 10, 'Title-auto-prefixedtext' ) ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( $this->builder->setValue( 'isSpecialPage', false ) );

		$title->expects( $this->any() )
			->method( 'isSpecial' )
			->willReturn( $this->builder->setValue( 'isSpecial', false ) );

		$title->expects( $this->any() )
			->method( 'isDeleted' )
			->willReturn( $this->builder->setValue( 'isDeleted', false ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->willReturn( $this->builder->setValue( 'getContentModel', $contentModel ) );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->willReturn( $this->builder->setValue( 'getPageLanguage' ) );

		$title->expects( $this->any() )
			->method( 'isRedirect' )
			->willReturn( $this->builder->setValue( 'isRedirect', false ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->builder->setCallback( 'inNamespace' ) );

		return $title;
	}

	/**
	 * @since 1.9
	 *
	 * @return Skin
	 */
	public function Skin() {
		$skin = $this->getMockBuilder( 'Skin' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$skin->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $skin;
	}

	/**
	 * @since 1.9
	 *
	 * @return SkinTemplate
	 */
	public function SkinTemplate() {
		$skinTemplate = $this->getMockBuilder( 'SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getSkin' )
			->willReturn( $this->builder->setValue( 'getSkin' ) );

		return $skinTemplate;
	}

	/**
	 * @since 1.9
	 *
	 * @return Parser
	 */
	public function Parser() {
		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$parser->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $parser;
	}

	/**
	 * @since 1.9
	 *
	 * @return \MediaWiki\Deferred\LinksUpdate\LinksUpdate
	 */
	public function LinksUpdate() {
		$linksUpdate = $this->createMock( LinksUpdate::class );

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$linksUpdate->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $linksUpdate;
	}

	/**
	 * @since 1.9
	 *
	 * @return OutputPage
	 */
	public function OutputPage() {
		$outputPage = $this->getMockBuilder( 'OutputPage' )
		->disableOriginalConstructor()
		->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->builder->setValue( 'getTitle' ) );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $this->builder->setValue( 'getContext' ) );

		$outputPage->expects( $this->any() )
			->method( 'addModules' )
			->willReturn( $this->builder->setValue( 'addModules' ) );

		$outputPage->expects( $this->any() )
			->method( 'addLink' )
			->willReturn( $this->builder->setValue( 'addLink' ) );

		// getHeadLinksArray doesn't exist in MW 1.19
		$outputPage->expects( $this->any() )
			->method( 'getHeadLinksArray' )
			->will( $this->builder->setCallback( 'getHeadLinksArray' ) );

		return $outputPage;
	}

	/**
	 * @since 1.9
	 *
	 * @return Database
	 */
	public function Database() {
		// Database is an abstract class, use setMethods to implement
		// required abstract methods
		$requiredAbstractMethods = [
			'selectField',
			'doQuery',
			'getType',
			'open',
			'fetchObject',
			'fetchRow',
			'numRows',
			'numFields',
			'fieldName',
			'insertId',
			'dataSeek',
			'lastErrno',
			'lastError',
			'fieldInfo',
			'indexInfo',
			'affectedRows',
			'strencode',
			'getSoftwareLink',
			'getServerVersion',
			'closeConnection'
		];

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$database->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $database;
	}

	/**
	 * @since 1.9
	 *
	 * @return Content
	 */
	public function Content() {
		$methods = $this->builder->getInvokedMethods();

		$content = $this->getMockBuilder( 'Content' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $methods as $method ) {

			$content->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $content;
	}

	/**
	 * @since 1.9
	 *
	 * @return ContentHandler
	 */
	public function ContentHandler() {
		$requiredAbstractMethods = [
			'serializeContent',
			'unserializeContent',
			'makeEmptyContent'
		];

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$contentHandler = $this->getMockBuilder( 'ContentHandler' )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$contentHandler->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $contentHandler;
	}

	/**
	 * @since 1.9
	 *
	 * @return RequestContext
	 */
	public function RequestContext() {
		$requestContext = $this->getMockForAbstractClass( 'RequestContext' );

		return $requestContext;
	}

	/**
	 * @since 1.9
	 *
	 * @return Language
	 */
	public function Language() {
		$language = $this->getMockBuilder( 'Language' )
			->disableOriginalConstructor()
			->getMock();

		return $language;
	}

}
