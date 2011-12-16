<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * Special page to show object relation pairs.
 *
 * @author Denny Vrandecic
 */

/**
 * This special page for Semantic MediaWiki implements a
 * view on a object-relation pair, i.e. a page that shows
 * all the fillers of a property for a certain page.
 * This is typically used for overflow results from other
 * dynamic output pages.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWPageProperty extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'PageProperty', '', false );
	}

	public function execute( $query ) {
		global $wgRequest, $wgOut;
		$linker = smwfGetLinker();
		$this->setHeaders();

		// Get parameters
		$pagename = $wgRequest->getVal( 'from' );
		$propname = $wgRequest->getVal( 'type' );
		$limit = $wgRequest->getVal( 'limit' );
		$offset = $wgRequest->getVal( 'offset' );

		if ( $limit === '' ) $limit =  20;
		if ( $offset === '' ) $offset = 0;

		if ( $propname === '' ) { // No GET parameters? Try the URL:
			$queryparts = explode( '::', $query );
			$propname = $query;
			if ( count( $queryparts ) > 1 ) {
				$pagename = $queryparts[0];
				$propname = implode( '::', array_slice( $queryparts, 1 ) );
			}
		}

		$subject = SMWDataValueFactory::newTypeIDValue( '_wpg', $pagename );
		$pagename = $subject->isValid() ? $subject->getPrefixedText() : '';
		$property = SMWPropertyValue::makeUserProperty( $propname );
		$propname = $property->isValid() ? $property->getWikiValue() : '';

		// Produce output
		$html = '';
		if ( ( $propname === '' ) ) { // no property given, show a message
			$html .= wfMsg( 'smw_pp_docu' ) . "\n";
		} else { // property given, find and display results
			// FIXME: very ugly, needs i18n
			$wgOut->setPagetitle( ( $pagename !== '' ? $pagename . ' ':'' ) . $property->getWikiValue() );

			// get results (get one more, to see if we have to add a link to more)
			$options = new SMWRequestOptions();
			$options->limit = $limit + 1;
			$options->offset = $offset;
			$options->sort = true;
			$results = smwfGetStore()->getPropertyValues( $pagename !== '' ? $subject->getDataItem() : null, $property->getDataItem(), $options );

			// prepare navigation bar if needed
			if ( ( $offset > 0 ) || ( count( $results ) > $limit ) ) {
				if ( $offset > 0 ) {
					$navigation = Html::element(
						'a',
						array(
							'href' => $this->getTitle()->getLocalURL( array(
								'offset' => max( 0, $offset - $limit ),
								'limit' => $limit,
								'type' => $propname,
								'from' => $pagename
							) )
						),
						wfMsg( 'smw_result_prev' )
					);
				} else {
					$navigation = wfMsg( 'smw_result_prev' );
				}

				$navigation .=
					'&#160;&#160;&#160;&#160; <b>' .
						wfMsg( 'smw_result_results' ) . ' ' .
						( $offset + 1 ) . '– ' . ( $offset + min( count( $results ), $limit ) ) .
					'</b>&#160;&#160;&#160;&#160;';

				if ( count( $results ) == ( $limit + 1 ) ) {
					$navigation = Html::element(
						'a',
						array(
							'href' => $this->getTitle()->getLocalURL( array(
								'offset' => ( $offset + $limit ),
								'limit' => $limit,
								'type' => $propname,
								'from' => $pagename
							) )
						),
						wfMsg( 'smw_result_next' )
					);
				} else {
					$navigation .= wfMsg( 'smw_result_next' );
				}
			} else {
				$navigation = '';
			}

			// display results
			$html .= '<br />' . $navigation;
			if ( count( $results ) == 0 ) {
				$html .= wfMsg( 'smw_result_noresults' );
			} else {
				$html .= "<ul>\n";
				$count = $limit + 1;

				foreach ( $results as $di ) {
					$count--;
					if ( $count < 1 ) continue;

					$dv = SMWDataValueFactory::newDataItemValue( $di, $property->getDataItem() );
					$html .= '<li>' . $dv->getLongHTMLText( $linker ); // do not show infolinks, the magnifier "+" is ambiguous with the browsing '+' for '_wpg' (see below)

					if ( $property->getDataItem()->findPropertyTypeID() == '_wpg' ) {
						$browselink = SMWInfolink::newBrowsingLink( '+', $dv->getLongWikiText() );
						$html .= ' &#160;' . $browselink->getHTML( $linker );
					}

					$html .=  "</li> \n";
				}

				$html .= "</ul>\n";
			}

			$html .= $navigation;
		}

		// Display query form
		$spectitle = $this->getTitle();
		$html .= '<p>&#160;</p>';
		$html .= '<form name="pageproperty" action="' . htmlspecialchars( $spectitle->getLocalURL() ) . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg( 'smw_pp_from' ) . ' <input type="text" name="from" value="' . htmlspecialchars( $pagename ) . '" />' . "&#160;&#160;&#160;\n";
		$html .= wfMsg( 'smw_pp_type' ) . ' <input type="text" name="type" value="' . htmlspecialchars( $propname ) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg( 'smw_pp_submit' ) . "\"/>\n</form>\n";

		$wgOut->addHTML( $html );
		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

}
