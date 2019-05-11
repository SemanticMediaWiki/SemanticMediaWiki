
## PageContentSaveComplete hook

[#2974](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2974) Creating subobjects using the `PageContentSaveComplete` hook

```php
use Hooks;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMW\DIWikiPage;
use SMW\DIProperty;

Hooks::register( 'PageContentSaveComplete', function( $wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId, $undidRevId ) {

        $applicationFactory = ApplicationFactory::getInstance();
        $mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

        /**
         * Initialize the ParserOuput object
         */
        $editInfo = $mwCollaboratorFactory->newEditInfo(
                $wikiPage,
                $revision,
                $user
        );

        $editInfo->fetchEditInfo();

        $parserOutput = $editInfo->getOutput();

        if ( !$parserOutput instanceof ParserOutput ) {
                return true;
        }

        $parserData = $applicationFactory->newParserData(
                $wikiPage->getTitle(),
                $parserOutput
        );

        // Subject Foo ...
        $subject = $parserData->getSubject();

        // Contains the wikitext, JSON or whatever is stored for this content model
        $nativeData = $content->getNativeData();

        // Identify the content as unique
        $subobjectName = '_MYCUSTOMPREFIX' . md5( $nativeData );

        $subject = new DIWikiPage(
                $subject->getDBkey(),
                $subject->getNamespace(),
                $subject->getInterwiki(),
                $subobjectName
        );

        // Build the subobject by using a separate container object
        $containerSemanticData = new ContainerSemanticData(
                $subject
        );

        /**
         * Start doing all the work required after this
         */

        // If one knows the details you can add it directly
        $containerSemanticData->addPropertyObjectValue(
                new DIProperty( 'PropertyIWantToUse' ),
                new DIWikiPage( 'SomeTextItem', NS_MAIN )
        );

        // If you don't know the type, use the DataValueFactory (see available methods)
        $dataValue = DataValueFactory::getInstance()->newDataValueByText(
           'AnotherPropertyIWantToUse',
           '123'
        );

        $containerSemanticData->addDataValue(
                $dataValue
        );

        /**
         * Done
         */

        // This part is used to add the subobject the the main subject
        // Page: Foo -> gets a _MYCUSTOMPREFIX.... attached
        $parserData->getSemanticData()->addPropertyObjectValue(
                new DIProperty( DIProperty::TYPE_SUBOBJECT ),
                new DIContainer( $containerSemanticData )
        );

        $parserData->pushSemanticDataToParserOutput();

        return true;

} );

```