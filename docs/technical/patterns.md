According to Douglas C. Schmidt ([Schmidt D. S. et.al., 1996][sp]) a pattern is a recurring solution to a standard problem and starting with the 1.9 release cycle, Semantic MediaWiki is making use of some design patterns to allow a more clearer division of responsibilities (see [SRP][srp]) among its objects and classes.

## Decorator pattern
The intent of a [Decorator][decp] is to add additional responsibilities (which can vary according to its context) during runtime without altering objects using the same interface.

#### PropertyAnnotatorDecorator
PropertyAnnotator interface is mainly responsible for adding annotations to the SemanticData container, can be combined in any order and can communicate with attached Observers.

* PropertyAnnotator describes an interface that enables to add responsibilities
* NullPropertyAnnotator describes an implementation to which responsibilities can be added
* PropertyAnnotatorDecorator contains a reference to the NullPropertyAnnotator and other decorators that derive from this class
* CategoryPropertyAnnotator responsible for adding category annotations
* SortKeyPropertyAnnotator responsible for adding a "_SKEY" annotation
* PredefinedPropertyAnnotator responsible for adding predefined annotations
* RedirectPropertyAnnotator responsible for adding a "_REDI" annotation

#### ProfileAnnotatorDecorator
ProfileAnnotator responsible for specifying an interface for adding query profile data to the SemanticData container.

* ProfileAnnotator describes an interface for handling and adding profile data
* ProfileAnnotatorDecorator
* NullProfile contains the initial setup for storing query profile data
* DescriptionProfile responsible for adding query description data
* FormatProfile responsible for adding format data

## Observer pattern
The [observer pattern][obp] can help to decrease shared interdependencies, separating object responsibilities, and lower [cyclomatic complexity][crap].

### Observer
* Observer interface describing methods provided by an Observer
* BaseObserver an abstract class implementing the Observer interface

#### ParserData
ParserData is a hybrid object which extends and acts as an Observer but also implements the DispatchableSubject interface making it at the same time an object that can send notifications to other registered Observers using a ObservableDispatcher.

#### UpdateObserver
UpdateObserver is extending the BaseObserver and responsible in collecting functions that carry out update tasks. The class is generally used in tandem with the ObservableDispatcher.

```php
public function updateStore() {
	$this->dispatcher->setState( 'runStoreUpdater' );
}
```
```php
SMW\ParserAfterTidy->process( )
SMW\ParserAfterTidy->performUpdate( )
SMW\ParserData->updateStore( )
SMW\ObservableSubject->setState( )
SMW\ObservableSubject->notify( )
SMW\BaseObserver->update( )
SMW\UpdateObserver->runStoreUpdater( )
```

### ObservableSubject
* Observable interface describing base methods to be used by a subject
* ObservableSubject an abstract class implementing the Observable interface (the name is used to avoid misinterpretations with Semantic MediaWiki's own data reference object known as DIWikiPage)

#### PropertyAnnotatorDecorator
PropertyAnnotatorDecorator is a ObservableSubject allowing it to communicate with attached Observers (such as ParserData).

```php
public function addAnnotation() {
	...
	$this->setState( 'updateOutput' );
}
```

### DispatchableSubject
A ObservableSubject can directly communicate with any attached Observer but when a class implements the DispatchableSubject interface it instead becomes a source that can communicate through an ObservableDispatcher with those Observers attached to the Dispatcher allowing it to loosen its dependency on a specific Observer implementation.

Instead of using an inheritance model or implementing an Observable interface to notify an attached Observer, a DispatchableSubject uses a Dispatcher as intermediary transmitter to notify an Observer.

* DispatchableSubject an interface describing a subject as dispatchable and enable to invoke a ObservableDispatcher
* ObservableDispatcher and interface that extends an ObservableSubject to enable a subject to redirect its communication to an Observer.A dispatcher is used as agent to transmit state changes which inherits all necessary methods to interact with an Observer which can encourage loose coupling to a specific pattern
* ObservableSubjectDispatcher specific implementation of the ObservableDispatcher interface

#### ParserData
ParserData is not only an Observer for other ObservableSubjects but itself is able to inform other Observers using a ObservableDispatcher. For example, when accessing updateStore() the actual work (and with it the responsibility to carry out the task) is forwarded to a partner that implements a 'runStoreUpdater' process without invoking responsibilities on the ParserData instance but still enable other objects to access it through the current instance.

```php
public function updateStore() {
	$this->dispatcher->setState( 'runStoreUpdater' );
}
```
```php
SMW\ParserData->updateStore( )
SMW\ObservableSubject->setState( )
SMW\ObservableSubject->notify( )
SMW\BaseObserver->update( )
SMW\UpdateObserver->runStoreUpdater( )
SMW\StoreUpdater->runUpdater( )
```

#### PropertyTypeComparator
PropertyTypeComparator class implements the DispatchableSubject and is responsible to identify differences between the current SemanticData and the Store data and schedules a call to UpdateDispatcherJob if it finds a disparity. This class does not have to know how to resolve the data difference, therefore it notifies the dispatcher to find an Observer that has a 'runUpdateDispatcher' process available.

```php
protected function addDispatchJob( $addJob = true ) {
	$this->dispatcher->setState( 'runUpdateDispatcher' );
}
```
```php
SMW\PropertyTypeComparator->runComparator( )
SMW\PropertyTypeComparator->compareConversionTypedFactors( )
SMW\PropertyTypeComparator->notifyDispatcher( )
SMW\ObservableSubject->setState( )
SMW\ObservableSubject->notify( )
SMW\Observer->update( )
SMW\UpdateObserver->runUpdateDispatcher( )
SMW\UpdateDispatcherJob->run( )
```

### ObservableMessageReporter
ObservableMessageReporter message reporter that reports messages by passing them along to all registered handlers

[sp]: http://www.cs.wustl.edu/~schmidt/CACM-editorial.html
[srp]: https://en.wikipedia.org/wiki/Single_responsibility_principle
[decp]: https://en.wikipedia.org/wiki/Decorator_pattern
[obp]: https://en.wikipedia.org/wiki/Observer_pattern
[crap]: https://www.semantic-mediawiki.org/wiki/CRAP