<?php

namespace SMW\DataValues;

use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Exception\DataItemException;
use SMW\Message;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;

/**
 * Objects of this class represent properties in SMW.
 *
 * This class represents both normal (user-defined) properties and predefined
 * ("special") properties. Predefined properties may still have a standard label
 * (and associated wiki article) and they will behave just like user-defined
 * properties in most cases (e.g. when asking for a printout text, a link to the
 * according page is produced).
 *
 * It is possible that predefined properties have no visible label at all, if they
 * are used only internally and never specified by or shown to the user. Those
 * will use their internal ID as DB key, and empty texts for most printouts. All
 * other properties use their canonical DB key (even if they are predefined and
 * have an id).
 *
 * Functions are provided to check whether a property is visible or
 * user-defined, and to get the internal ID, if any.
 *
 * @note This datavalue is used only for representing properties and, possibly
 * objects/values, but never for subjects (pages as such). Hence it does not
 * provide a complete Title-like interface, or support for things like sortkey.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class PropertyValue extends DataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__pro';

	/**
	 * Avoid the display of a tooltip
	 */
	const OPT_NO_HIGHLIGHT = 'no.highlight';

	/**
	 * Use linker with the highlighter
	 */
	const OPT_HIGHLIGHT_LINKER = 'highlight.linker';

	/**
	 * Avoid the display of a preferred label marker
	 */
	const OPT_NO_PREF_LHNT = 'no.preflabel.marker';

	/**
	 * Indicates to use a canonical label
	 */
	const OPT_CANONICAL_LABEL = 'canonical.label';

	/**
	 * Special formatting of the label/preferred label
	 */
	const FORMAT_LABEL = 'format.label';

	/**
	 * Label to be used for matching a DB search
	 */
	const SEARCH_LABEL = 'search.label';

	/**
	 * Cache for wiki page value object associated to this property, or
	 * null if no such page exists. Use getWikiPageValue() to get the data.
	 * @var SMWWikiPageValue
	 */
	protected $m_wikipage = null;

	/**
	 * @var array
	 */
	protected $linkAttributes = [];

	/**
	 * @var string
	 */
	private $preferredLabel = '';

	/**
	 * @var DIProperty
	 */
	private $inceptiveProperty;

	/**
	 * @var ValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @since 2.4
	 *
	 * @param string $typeid
	 */
	public function __construct( $typeid = self::TYPE_ID ) {
		parent::__construct( $typeid );
	}

	/**
	 * @deprecated since 3.0
	 */
	static private function makeUserProperty( $propertyLabel ) {
		return DataValueFactory::getInstance()->newPropertyValueByLabel( $propertyLabel );
	}

	/**
	 * @removed since 3.0
	 */
	static private function makeProperty( $propertyid ) {
		$diProperty = new DIProperty( $propertyid );
		$dvProperty = new SMWPropertyValue( self::TYPE_ID );
		$dvProperty->setDataItem( $diProperty );
		return $dvProperty;
	}

	/**
	 * We use the internal wikipage object to store some of this objects data.
	 * Clone it to make sure that data can be modified independently from the
	 * original object's content.
	 */
	public function __clone() {
		if ( !is_null( $this->m_wikipage ) ) {
			$this->m_wikipage = clone $this->m_wikipage;
		}
	}

	/**
	 * @note If the inceptive property and the property referenced in dataItem
	 * are not equal then the dataItem represents the end target to which the
	 * inceptive property has been redirected.
	 *
	 * @since 2.4
	 *
	 * @return DIProperty
	 */
	public function getInceptiveProperty() {
		return $this->inceptiveProperty;
	}

	/**
	 * Extended parsing function to first check whether value refers to pre-defined
	 * property, resolve aliases, and set internal property id accordingly.
	 * @todo Accept/enforce property namespace.
	 */
	protected function parseUserValue( $value ) {
		$this->m_wikipage = null;

		$propertyValueParser = $this->dataValueServiceFactory->getValueParser(
			$this
		);

		$propertyValueParser->isQueryContext(
			$this->getOption( self::OPT_QUERY_CONTEXT )
		);

		$reqCapitalizedFirstChar = $this->getContextPage() !== null && $this->getContextPage()->getNamespace() === SMW_NS_PROPERTY;

		$propertyValueParser->reqCapitalizedFirstChar(
			$reqCapitalizedFirstChar
		);

		list( $propertyName, $capitalizedName, $inverse ) = $propertyValueParser->parse( $value );

		foreach ( $propertyValueParser->getErrors() as $error ) {
			return $this->addErrorMsg( $error, Message::PARSE );
		}

		try {
			$this->m_dataitem = $this->createDataItemFrom(
				$reqCapitalizedFirstChar,
				$propertyName,
				$capitalizedName,
				$inverse
			);
		} catch ( DataItemException $e ) { // happens, e.g., when trying to sort queries by property "-"
			$this->addErrorMsg( [ 'smw_noproperty', $value ] );
			$this->m_dataitem = new DIProperty( 'ERROR', false ); // just to have something
		}

		// @see the SMW_DV_PROV_DTITLE explanation
		if ( $this->isEnabledFeature( SMW_DV_PROV_DTITLE ) ) {
			$dataItem = $this->dataValueServiceFactory->getPropertySpecificationLookup()->getPropertyFromDisplayTitle(
				$value
			);

			$this->m_dataitem = $dataItem ? $dataItem : $this->m_dataitem;
		}

		// Copy the original DI to ensure we can compare it against a possible redirect
		$this->inceptiveProperty = $this->m_dataitem;

		if ( $this->isEnabledFeature( SMW_DV_PROV_REDI ) ) {
			$this->m_dataitem = $this->m_dataitem->getRedirectTarget();
		}

		// If no external caption has been invoked then fetch a preferred label
		if ( $this->m_caption === false || $this->m_caption === '' ) {
			$this->preferredLabel = $this->m_dataitem->getPreferredLabel( $this->getOption( self::OPT_USER_LANGUAGE ) );
		}

		// Use the preferred label as first choice for a caption, if available
		if ( $this->preferredLabel !== '' ) {
			$this->m_caption = $this->preferredLabel;
		} elseif ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem DataItem
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( $dataItem->getDIType() !== DataItem::TYPE_PROPERTY ) {
			return false;
		}

		$this->inceptiveProperty = $dataItem;
		$this->m_dataitem = $dataItem;
		$this->preferredLabel = $this->m_dataitem->getPreferredLabel();

		unset( $this->m_wikipage );
		$this->m_caption = false;
		$this->linkAttributes = [];

		if ( $this->preferredLabel !== '' ) {
			$this->m_caption = $this->preferredLabel;
		}

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getPreferredLabel() {
		return $this->preferredLabel;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $linkAttributes
	 */
	public function setLinkAttributes( array $linkAttributes ) {
		$this->linkAttributes = $linkAttributes;

		if ( $this->getWikiPageValue() instanceof SMWDataValue ) {
			$this->m_wikipage->setLinkAttributes( $linkAttributes );
		}
	}

	public function setCaption( $caption ) {
		parent::setCaption( $caption );
		if ( $this->getWikiPageValue() instanceof SMWDataValue ) { // pass caption to embedded datavalue (used for printout)
			$this->m_wikipage->setCaption( $caption );
		}
	}

	public function setOutputFormat( $formatstring ) {

		if ( $formatstring === false || $formatstring === '' ) {
			return;
		}

		$this->m_outformat = $formatstring;

		if ( $this->getWikiPageValue() instanceof SMWDataValue ) {
			$this->m_wikipage->setOutputFormat( $formatstring );
		}
	}

	public function setInverse( $isinverse ) {
		return $this->m_dataitem = new DIProperty( $this->m_dataitem->getKey(), ( $isinverse == true ) );
	}

	/**
	 * Return a wiki page value that can be used for displaying this
	 * property, or null if no such wiki page exists (for predefined
	 * properties without any label).
	 * @return SMWWikiPageValue or null
	 */
	public function getWikiPageValue() {

		if ( isset( $this->m_wikipage ) ) {
			return $this->m_wikipage;
		}

		$diWikiPage = $this->m_dataitem->getCanonicalDiWikiPage();

		if ( $diWikiPage !== null ) {
			$this->m_wikipage = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPage, null, $this->m_caption );
			$this->m_wikipage->setOutputFormat( $this->m_outformat );
			$this->m_wikipage->setLinkAttributes( $this->linkAttributes );
			$this->m_wikipage->copyOptions( $this->getOptions() );
			$this->addError( $this->m_wikipage->getErrors() );
		} else { // should rarely happen ($value is only changed if the input $value really was a label for a predefined prop)
			$this->m_wikipage = null;
		}

		return $this->m_wikipage;
	}

	/**
	 * Return TRUE if this is a property that can be displayed, and not a pre-defined
	 * property that is used only internally and does not even have a user-readable name.
	 * @note Every user defined property is necessarily visible.
	 */
	public function isVisible() {
		return $this->isValid() && ( $this->m_dataitem->isUserDefined() || $this->m_dataitem->getCanonicalLabel() !== '' );
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isRestricted() {

		if ( !$this->isValid() ) {
			return true;
		}

		$propertyRestrictionExaminer = $this->dataValueServiceFactory->getPropertyRestrictionExaminer();

		$propertyRestrictionExaminer->isQueryContext(
			$this->getOption( self::OPT_QUERY_CONTEXT )
		);

		$propertyRestrictionExaminer->checkRestriction(
			$this->getDataItem(),
			$this->getContextPage()
		);

		if ( !$propertyRestrictionExaminer->hasRestriction() ) {
			return false;
		}

		$this->restrictionError = $propertyRestrictionExaminer->getError();

		return true;
	}

	/**
	 * @see DataValue::getShortWikiText
	 *
	 * @return string
	 */
	public function getShortWikiText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::WIKI_SHORT, $linker ] );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 *
	 * @return string
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::HTML_SHORT, $linker ] );
	}

	/**
	 * @see DataValue::getLongWikiText
	 *
	 * @return string
	 */
	public function getLongWikiText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::WIKI_LONG, $linker ] );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 *
	 * @return string
	 */
	public function getLongHTMLText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::HTML_LONG, $linker ] );
	}

	/**
	 * @see DataValue::getWikiValue
	 *
	 * @return string
	 */
	public function getWikiValue() {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::VALUE ] );
	}

	/**
	 * Outputs a formatted property label that takes into account preferred/
	 * canonical label characteristics
	 *
	 * @param integer|string $format
	 * @param Linker|null $linker
	 *
	 * @return string
	 */
	public function getFormattedLabel( $format = DataValueFormatter::VALUE, $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		$this->setOption(
			self::FORMAT_LABEL,
			$format
		);

		return $this->valueFormatter->format( $this, [ self::FORMAT_LABEL, $linker ] );
	}

	/**
	 * Outputs a label that corresponds to the display and sort characteristics (
	 * e.g. display title etc.) and can be used to initiate a match and search
	 * process.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getSearchLabel() {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ self::SEARCH_LABEL ] );
	}

	/**
	 * Convenience method to find the type id of this property. Most callers
	 * should rather use DIProperty::findPropertyTypeId() directly. Note
	 * that this is not the same as getTypeID(), which returns the id of
	 * this property datavalue.
	 *
	 * @return string
	 */
	public function getPropertyTypeID() {

		if ( !$this->isValid() ) {
			return '__err';
		}

		return $this->m_dataitem->findPropertyTypeId();
	}

	private function createDataItemFrom( $reqCapitalizedFirstChar, $propertyName, $capitalizedName, $inverse ) {

		$contentLanguage = $this->getOption( self::OPT_CONTENT_LANGUAGE );

		// Probe on capitalizedFirstChar because we only want predefined
		// properties (e.g. Has type vs. has type etc.) to adhere the rule while
		// custom (user) defined properties can appear in any form
		if ( $reqCapitalizedFirstChar ) {
			$dataItem = DIProperty::newFromUserLabel( $capitalizedName, $inverse, $contentLanguage );
			$propertyName = $dataItem->isUserDefined() ? $propertyName : $capitalizedName;
		}

		return DIProperty::newFromUserLabel( $propertyName, $inverse, $contentLanguage );
	}

}
