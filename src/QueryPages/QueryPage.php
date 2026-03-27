<?php

namespace SMW\QueryPages;

use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\SpecialPage\QueryPage as MWQueryPage;
use MediaWiki\Xml\Xml;
use SMW\Formatters\MessageFormatter;
use SMW\MediaWiki\MessageBuilder;
use SMW\RequestOptions;
use SMW\StringCondition;

/**
 * An abstract query page base class that supports array-based
 * data retrieval instead of the SQL-based access used by MW.
 *
 *
 * @license GPL-2.0-or-later
 * @since   ??
 *
 * @author Markus Krötzsch
 */

/**
 * Abstract base class for SMW's variant of the MW QueryPage.
 * Subclasses must implement getResults() and formatResult(), as
 * well as some other standard functions of QueryPage.
 *
 * @ingroup SMW
 * @ingroup QueryPage
 */
abstract class QueryPage extends MWQueryPage {

	/** @var MessageFormatter */
	protected $msgFormatter;

	/** @var Linker */
	protected $linker = null;

	/** @var array */
	protected $selectOptions = [];

	/** @var array */
	protected $useSerchForm = false;

	/**
	 * Implemented by subclasses to provide concrete functions.
	 */
	abstract public function getResults( $requestoptions );

	/**
	 * Clear the cache and save new results
	 * @todo Implement caching for SMW query pages
	 */
	public function recache( $limit, $ignoreErrors = true ): void {
		/// TODO
	}

	public function isExpensive(): bool {
		// Disables caching for now
		return false;
	}

	public function isSyndicated(): bool {
		// TODO: why not?
		return false;
	}

	/**
	 * @see QueryPage::linkParameters
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function linkParameters(): array {
		$parameters = [];
		$property   = $this->getRequest()->getVal( 'property' );

		if ( $property !== null && $property !== '' ) {
			$parameters['property'] = $property;
		}

		$filter = $this->getRequest()->getVal( 'filter' );

		if ( $filter !== null && $filter !== '' ) {
			$parameters['filter'] = $filter;
		}

		return $parameters;
	}

	/**
	 * Returns a MessageFormatter object
	 *
	 * @since  1.9
	 *
	 * @return MessageFormatter
	 */
	public function getMessageFormatter() {
		if ( !isset( $this->msgFormatter ) ) {
			$this->msgFormatter = new MessageFormatter( $this->getLanguage() );
		}

		return $this->msgFormatter;
	}

	/**
	 * Returns a Linker object
	 *
	 * @since  1.9
	 *
	 * @return Linker
	 */
	public function getLinker(): Linker {
		if ( $this->linker === null ) {
			$this->linker = smwfGetLinker();
		}

		return $this->linker;
	}

	/**
	 * Generates a search form
	 *
	 * @since 1.9
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	public function getSearchForm( $property = '', $cacheDate = '', $propertySearch = true, $filter = '' ) {
		$this->useSerchForm = true;
		$this->getOutput()->addModules( 'ext.smw.autocomplete.property' );

		// No need to verify $this->selectOptions because its values are set
		// during doQuery() which is processed before this form is generated
		$limit = $this->selectOptions['limit'];
		$options = $this->selectOptions['requestOptions'];

		$msgBuilder = new MessageBuilder( $this->getLanguage() );

		$isFirstPage = !$options->hasCursor();
		$resultCount = $this->msg( 'smw-showingresults-cursor' )->numParams( $limit )->parse();

		$selection = $msgBuilder->cursorPrevNextToText(
			$this->getContext()->getTitle(),
			$limit,
			$isFirstPage ? null : $options->getFirstCursor(),
			$options->getLastCursor(),
			$this->linkParameters(),
			$this->selectOptions['end'],
			$options->getCursorBefore() !== null
		);

		if ( $cacheDate !== '' ) {
			$cacheDate = Xml::tags( 'p', [], $cacheDate );
		}

		if ( $propertySearch ) {
			$propertySearch = Xml::tags( 'hr', [ 'style' => 'margin-bottom:10px;' ], '' ) .
				Html::label(
					$this->msg( 'smw-special-property-searchform' )->text(),
					'smw-property-input'
				) . "\u{00A0}" .
				Html::input(
					'property',
					$property,
					'text',
					[ 'id' => 'smw-property-input', 'size' => 20 ]
				) . ' ' .
				Html::submitButton( $this->msg( 'allpagessubmit' )->text() );
		}

		if ( $filter !== '' ) {
			$filter = Xml::tags( 'hr', [ 'style' => 'margin-bottom:10px;' ], '' ) . $filter;
		}

		return Xml::tags( 'form', [
			'method' => 'get',
			'action' => htmlspecialchars( $GLOBALS['wgScript'] ),
			'class' => 'plainlinks'
		], Html::hidden( 'title', $this->getContext()->getTitle()->getPrefixedText() ) .
			Xml::fieldset( $this->msg( 'smw-special-property-searchform-options' )->text(),
				Xml::tags( 'p', [], $resultCount ) .
				Xml::tags( 'p', [], $selection ) .
				$cacheDate .
				$propertySearch .
				$filter
			)
		);
	}

	/**
	 * This is the actual workhorse. It does everything needed to make a
	 * real, honest-to-gosh query page.
	 * Alas, we need to overwrite the whole beast since we do not assume
	 * an SQL-based storage backend.
	 *
	 * @param $offset database query offset
	 * @param $limit database query limit
	 * @param $property database string query
	 */
	public function doQuery( $offset = false, $limit = false, $property = false ): ?int {
		$out  = $this->getOutput();
		$sk   = $this->getSkin();

		$options = new RequestOptions();
		$options->limit = $limit;
		$options->offset = $offset;
		$options->sort = true;

		// Set cursor params from request
		$request = $this->getRequest();
		$afterId = $request->getInt( 'after' );
		$beforeId = $request->getInt( 'before' );

		if ( $afterId > 0 ) {
			$options->setCursorAfter( $afterId );
		} elseif ( $beforeId > 0 ) {
			$options->setCursorBefore( $beforeId );
		}

		if ( $property ) {
			$options->addStringCondition( $property, StringCondition::STRCOND_MID );
		}

		if ( ( $filter = $this->getRequest()->getVal( 'filter' ) ) === 'unapprove' ) {
			$options->addExtraCondition( [ 'filter.unapprove' => true ] );
		}

		$res = $this->getResults( $options );
		$num = count( $res );

		// The lookup fetches limit+1 rows and trims the extra, setting
		// cursorHasMore if more results exist. Ideally the lookup would
		// return a result object with a hasMore field instead of using
		// the RequestOptions side-channel.
		$atend = !$options->getCursorHasMore();

		$this->selectOptions = [
			'offset'         => $offset,
			'limit'          => $limit,
			'end'            => $atend,
			'count'          => $num,
			'requestOptions' => $options,
		];

		$out->addHTML( $this->getPageHeader() );

		// if list is empty, show it
		if ( $num == 0 ) {
			$out->addHTML( '<p>' . $this->msg( 'specialpage-empty' )->escaped() . '</p>' );
			return null;
		}

		if ( $num > 0 ) {
			$s = [];
			if ( !$this->listoutput ) {
				$s[] = "<ul>\n";
			}

			foreach ( $res as $r ) {
				$format = $this->formatResult( $sk, $r );
				if ( $format ) {
					$s[] = $this->listoutput ? $format : "<li>{$format}</li>\n";
				}
			}

			if ( !$this->listoutput ) {
				$s[] = "</ul>\n";
			}
			$str = $this->listoutput ? $this->getLanguage()->listToText( $s ) : implode( '', $s );
			$out->addHTML( $str );
		}

		if ( !$this->useSerchForm ) {
			$out->addHTML( "<p>{$sl}</p>\n" );
		}

		return $num;
	}

	/**
	 * @return array|null
	 */
	public function getQueryInfo(): ?array {
		return null;
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( QueryPage::class, 'SMW\QueryPage' );
