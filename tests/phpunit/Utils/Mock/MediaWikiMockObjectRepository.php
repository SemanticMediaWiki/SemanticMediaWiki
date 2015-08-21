<?php

namespace SMW\Tests\Utils\Mock;

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
class MediaWikiMockObjectRepository extends \PHPUnit_Framework_TestCase implements MockObjectRepository {

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
			->will( $this->returnValue( $this->builder->setValue( 'getUserPage' ) ) );

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
			->will( $this->returnValue( $this->builder->setValue( 'getTargetLanguage' ) ) );

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
			->will( $this->returnValue( $this->builder->setValue( 'getDBkey', $this->builder->newRandomString( 10, 'Title-auto-dbkey' ) ) ) );

		$title->expects( $this->any() )
			->method( 'getInterwiki' )
			->will( $this->returnValue( $this->builder->setValue( 'getInterwiki', '' ) ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $this->builder->setValue( 'getArticleID', rand( 10, 10000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $this->builder->setValue( 'getNamespace', NS_MAIN ) ) );

		$title->expects( $this->any() )
			->method( 'isKnown' )
			->will( $this->returnValue( $this->builder->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $this->builder->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( $this->builder->setValue( 'getLatestRevID', rand( 10, 5000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $this->builder->setValue( 'getFirstRevision' ) ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->builder->setValue( 'getText' ) ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $this->builder->setValue( 'getPrefixedText', $this->builder->newRandomString( 10, 'Title-auto-prefixedtext' ) ) ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( $this->builder->setValue( 'isSpecialPage', false ) ) );

		$title->expects( $this->any() )
			->method( 'isSpecial' )
			->will( $this->returnValue( $this->builder->setValue( 'isSpecial', false ) ) );

		$title->expects( $this->any() )
			->method( 'isDeleted' )
			->will( $this->returnValue( $this->builder->setValue( 'isDeleted', false ) ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( $this->builder->setValue( 'getContentModel', $contentModel ) ) );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $this->builder->setValue( 'getPageLanguage' ) ) );

		$title->expects( $this->any() )
			->method( 'isRedirect' )
			->will( $this->returnValue( $this->builder->setValue( 'isRedirect', false ) ) );

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
			->will( $this->returnValue( $this->builder->setValue( 'getSkin' ) ) );

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
	 * @return LinksUpdate
	 */
	public function LinksUpdate() {

		$linksUpdate = $this->getMockBuilder( 'LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

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
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->builder->setValue( 'getContext' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addModules' )
			->will( $this->returnValue( $this->builder->setValue( 'addModules' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addLink' )
			->will( $this->returnValue( $this->builder->setValue( 'addLink' ) ) );

		// getHeadLinksArray doesn't exist in MW 1.19
		$outputPage->expects( $this->any() )
			->method( 'getHeadLinksArray' )
			->will( $this->builder->setCallback( 'getHeadLinksArray' ) );

		return $outputPage;
	}

	/**
	 * @since 1.9
	 *
	 * @return DatabaseBase
	 */
	public function DatabaseBase() {

		// DatabaseBase is an abstract class, use setMethods to implement
		// required abstract methods
		$requiredAbstractMethods = array(
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
		);

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$databaseBase = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$databaseBase->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $databaseBase;
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

		$requiredAbstractMethods = array(
			'serializeContent',
			'unserializeContent',
			'makeEmptyContent'
		);

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$contentHandler = $this->getMockBuilder( 'ContentHandler' )
			->disableOriginalConstructor()
			->setMethods( $methods )
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
