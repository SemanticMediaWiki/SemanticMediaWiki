<?php

namespace SMW;
use MWException, ContextSource, IContextSource, RequestContext;
use SMWPropertyValue, SMWDataValueFactory, SMWDIProperty, SMWDIWikiPage, SMWContainerSemanticData, SMWDIContainer;

/**
 * Class for the 'subobject' interface functions
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @author mwjames
 */
class Subobject extends ContextSource {

	/**
	 * @var subject
	 */
	 protected $subject;

	/**
	 * @var subobjectName
	 */
	 protected $subobjectName;

	/**
	 * @var semanticData
	 */
	 protected $semanticData;

	/**
	 * @var errors
	 */
	protected $errors = array();

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param SMWDIWikiPage $subject
	 * @param string|null $subobjectName
	 * @param \IContextSource|null $context
	 *
	 */
	public function __construct( SMWDIWikiPage $subject, $subobjectName = null, IContextSource $context = null ) {
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$this->setContext( $context );
		$this->subject = $subject;
		$this->setSubjectContainer( $subobjectName );
	}

	/**
	 * Factory method
	 *
	 * @since 1.9
	 *
	 * @param SMWDIWikiPage $subject
	 * @param string|null $subobjectName
	 * @param \IContextSource|null $context
	 *
	 * @return SMW\Subobject
	 */
	public static function factory( SMWDIWikiPage $subject, $subobjectName = null , IContextSource $context = null ){
		return new Subobject( $subject, $subobjectName, $context );
	}

	/**
	 * Returns the subobject name
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getSubobjectName(){
		return $this->subobjectName;
	}

	/**
	 * Return errors that happen during the insert procedure
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors(){
		return $this->errors;
	}

	/**
	 * Sets the semantic data container for a subobject wikipage object
	 *
	 * @since 1.9
	 *
	 * @param string $subobjectName
	 *
	 * @return SMWContainerSemanticData
	 */
	public function setSubjectContainer( $subobjectName ){
		if ( $subobjectName !== '' ) {
			$this->subobjectName = $subobjectName;

			$diSubWikiPage = new SMWDIWikiPage( $this->subject->getDBkey(),
				$this->subject->getNamespace(), $this->subject->getInterwiki(),
				$subobjectName );

			return $this->semanticData = new SMWContainerSemanticData( $diSubWikiPage );
		}
		return '';
	}

	/**
	 * Returns semantic data container for a subject
	 *
	 * @since 1.9
	 *
	 * @return SMWContainerSemanticData
	 */
	public function getSubjectContainer(){
		return $this->semanticData;
	}

	/**
	 * Returns subobject property data item
	 *
	 * @since 1.9
	 *
	 * @return SMWDIProperty
	 */
	public function getSubobjectProperty(){
		return new SMWDIProperty( SMWDIProperty::TYPE_SUBOBJECT );
	}

	/**
	 * Returns semantic data container for a subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWDIContainer
	 */
	public function getSubobjectContainer(){
		return new SMWDIContainer( $this->semanticData );
	}

	/**
	 * Add property/value to the semantic data container
	 *
	 * @since 1.9
	 *
	 * @param string $propertyName
	 * @param string $valueString
	 */
	public function addPropertyValue( $propertyName, $valueString ) {
		if ( $this->semanticData === array() ) {
			throw new MWException( 'The subject container is not initialized' );
		}

		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			$valueDv = SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString,
				false, $this->semanticData->getSubject() );
			$this->semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$this->semanticData->addPropertyObjectValue( new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
					$propertyDi->getDiWikiPage() );
				$this->errors = array_merge( $this->errors, $valueDv->getErrors() );
			}
		} else {
			$this->errors[] = $this->msg( 'smw_noinvannot' )->inContentLanguage()->text();
		}
	}
}