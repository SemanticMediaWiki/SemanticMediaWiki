/**!
* tippy.js v4.2.0
* (c) 2017-2019 atomiks
* MIT License
*/
(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(require('popper.js')) :
  typeof define === 'function' && define.amd ? define(['popper.js'], factory) :
  (global = global || self, global.tippy = factory(global.Popper));
}(this, function (Popper) { 'use strict';

  Popper = Popper && Popper.hasOwnProperty('default') ? Popper['default'] : Popper;

  function _extends() {
    _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    return _extends.apply(this, arguments);
  }

  var version = "4.2.0";

  var isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';
  var ua = isBrowser ? navigator.userAgent : '';
  var isIE = /MSIE |Trident\//.test(ua);
  var isUCBrowser = /UCBrowser\//.test(ua);
  var isIOS = isBrowser && /iPhone|iPad|iPod/.test(navigator.platform) && !window.MSStream;

  var defaultProps = {
    a11y: true,
    allowHTML: true,
    animateFill: true,
    animation: 'shift-away',
    appendTo: function appendTo() {
      return document.body;
    },
    aria: 'describedby',
    arrow: false,
    arrowType: 'sharp',
    boundary: 'scrollParent',
    content: '',
    delay: 0,
    distance: 10,
    duration: [325, 275],
    flip: true,
    flipBehavior: 'flip',
    flipOnUpdate: false,
    followCursor: false,
    hideOnClick: true,
    ignoreAttributes: false,
    inertia: false,
    interactive: false,
    interactiveBorder: 2,
    interactiveDebounce: 0,
    lazy: true,
    maxWidth: 350,
    multiple: false,
    offset: 0,
    onHidden: function onHidden() {},
    onHide: function onHide() {},
    onMount: function onMount() {},
    onShow: function onShow() {},
    onShown: function onShown() {},
    placement: 'top',
    popperOptions: {},
    role: 'tooltip',
    showOnInit: false,
    size: 'regular',
    sticky: false,
    target: '',
    theme: 'dark',
    touch: true,
    touchHold: false,
    trigger: 'mouseenter focus',
    updateDuration: 0,
    wait: null,
    zIndex: 9999
    /**
     * If the set() method encounters one of these, the popperInstance must be
     * recreated
     */

  };
  var POPPER_INSTANCE_DEPENDENCIES = ['arrow', 'arrowType', 'boundary', 'distance', 'flip', 'flipBehavior', 'flipOnUpdate', 'offset', 'placement', 'popperOptions'];

  var Selectors = {
    POPPER: '.tippy-popper',
    TOOLTIP: '.tippy-tooltip',
    CONTENT: '.tippy-content',
    BACKDROP: '.tippy-backdrop',
    ARROW: '.tippy-arrow',
    ROUND_ARROW: '.tippy-roundarrow'
  };

  var elementProto = isBrowser ? Element.prototype : {};
  var matches = elementProto.matches || elementProto.matchesSelector || elementProto.webkitMatchesSelector || elementProto.mozMatchesSelector || elementProto.msMatchesSelector;
  /**
   * Ponyfill for Array.from - converts iterable values to an array
   */

  function arrayFrom(value) {
    return [].slice.call(value);
  }
  /**
   * Ponyfill for Element.prototype.closest
   */

  function closest(element, parentSelector) {
    return (elementProto.closest || function (selector) {
      // @ts-ignore
      var el = this;

      while (el) {
        if (matches.call(el, selector)) {
          return el;
        }

        el = el.parentElement;
      }
    }).call(element, parentSelector);
  }
  /**
   * Works like Element.prototype.closest, but uses a callback instead
   */

  function closestCallback(element, callback) {
    while (element) {
      if (callback(element)) {
        return element;
      }

      element = element.parentElement;
    }
  }

  var PASSIVE = {
    passive: true
  };
  var PADDING = 4;

  var keys = Object.keys(defaultProps);
  /**
   * Returns an object of optional props from data-tippy-* attributes
   */

  function getDataAttributeOptions(reference) {
    return keys.reduce(function (acc, key) {
      var valueAsString = (reference.getAttribute("data-tippy-".concat(key)) || '').trim();

      if (!valueAsString) {
        return acc;
      }

      if (key === 'content') {
        acc[key] = valueAsString;
      } else {
        try {
          acc[key] = JSON.parse(valueAsString);
        } catch (e) {
          acc[key] = valueAsString;
        }
      }

      return acc;
    }, {});
  }
  /**
   * Polyfills the virtual reference (plain object) with Element.prototype props
   * Mutating because DOM elements are mutated, adds `_tippy` property
   */

  function polyfillElementPrototypeProperties(virtualReference) {
    var polyfills = {
      isVirtual: true,
      attributes: virtualReference.attributes || {},
      setAttribute: function setAttribute(key, value) {
        virtualReference.attributes[key] = value;
      },
      getAttribute: function getAttribute(key) {
        return virtualReference.attributes[key];
      },
      removeAttribute: function removeAttribute(key) {
        delete virtualReference.attributes[key];
      },
      hasAttribute: function hasAttribute(key) {
        return key in virtualReference.attributes;
      },
      addEventListener: function addEventListener() {},
      removeEventListener: function removeEventListener() {},
      classList: {
        classNames: {},
        add: function add(key) {
          virtualReference.classList.classNames[key] = true;
        },
        remove: function remove(key) {
          delete virtualReference.classList.classNames[key];
        },
        contains: function contains(key) {
          return key in virtualReference.classList.classNames;
        }
      }
    };

    for (var key in polyfills) {
      virtualReference[key] = polyfills[key];
    }
  }

  /**
   * Determines if a value is a "bare" virtual element (before mutations done
   * by `polyfillElementPrototypeProperties()`). JSDOM elements show up as
   * [object Object], we can check if the value is "element-like" if it has
   * `addEventListener`
   */

  function isBareVirtualElement(value) {
    return {}.toString.call(value) === '[object Object]' && !value.addEventListener;
  }
  /**
   * Safe .hasOwnProperty check, for prototype-less objects
   */

  function hasOwnProperty(obj, key) {
    return {}.hasOwnProperty.call(obj, key);
  }
  /**
   * Returns an array of elements based on the value
   */

  function getArrayOfElements(value) {
    if (isSingular(value)) {
      // TODO: VirtualReference is not compatible to type Element
      return [value];
    }

    if (value instanceof NodeList) {
      return arrayFrom(value);
    }

    if (Array.isArray(value)) {
      return value;
    }

    try {
      return arrayFrom(document.querySelectorAll(value));
    } catch (e) {
      return [];
    }
  }
  /**
   * Returns a value at a given index depending on if it's an array or number
   */

  function getValue(value, index, defaultValue) {
    if (Array.isArray(value)) {
      var v = value[index];
      return v == null ? defaultValue : v;
    }

    return value;
  }
  /**
   * Debounce utility
   */

  function debounce(fn, ms) {
    var timeoutId;
    return function () {
      var _this = this,
          _arguments = arguments;

      clearTimeout(timeoutId); // @ts-ignore

      timeoutId = setTimeout(function () {
        return fn.apply(_this, _arguments);
      }, ms);
    };
  }
  /**
   * Prevents errors from being thrown while accessing nested modifier objects
   * in `popperOptions`
   */

  function getModifier(obj, key) {
    return obj && obj.modifiers && obj.modifiers[key];
  }
  /**
   * Determines if an array or string includes a value
   */

  function includes(a, b) {
    return a.indexOf(b) > -1;
  }
  /**
   * Determines if the value is singular-like
   */

  function isSingular(value) {
    return !!(value && hasOwnProperty(value, 'isVirtual')) || value instanceof Element;
  }
  /**
   * Firefox extensions don't allow setting .innerHTML directly, this will trick it
   */

  function innerHTML() {
    return 'innerHTML';
  }
  /**
   * Evaluates a function if one, or returns the value
   */

  function evaluateValue(value, args) {
    return typeof value === 'function' ? value.apply(null, args) : value;
  }
  /**
   * Sets a popperInstance `flip` modifier's enabled state
   */

  function setFlipModifierEnabled(modifiers, value) {
    modifiers.filter(function (m) {
      return m.name === 'flip';
    })[0].enabled = value;
  }
  /**
   * Determines if an element can receive focus
   * Always returns true for virtual objects
   */

  function canReceiveFocus(element) {
    return element instanceof Element ? matches.call(element, 'a[href],area[href],button,details,input,textarea,select,iframe,[tabindex]') && !element.hasAttribute('disabled') : true;
  }
  /**
   * Returns a new `div` element
   */

  function div() {
    return document.createElement('div');
  }
  /**
   * Evaluates the props object by merging data attributes and
   * disabling conflicting options where necessary
   */

  function evaluateProps(reference, props) {
    var out = _extends({}, props, {
      content: evaluateValue(props.content, [reference])
    }, props.ignoreAttributes ? {} : getDataAttributeOptions(reference));

    if (out.arrow || isUCBrowser) {
      out.animateFill = false;
    }

    return out;
  }
  /**
   * Validates an object of options with the valid default props object
   */

  function validateOptions(options, defaultProps) {
    Object.keys(options).forEach(function (option) {
      if (!hasOwnProperty(defaultProps, option)) {
        throw new Error("[tippy]: `".concat(option, "` is not a valid option"));
      }
    });
  }

  /**
   * Sets the innerHTML of an element
   */

  function setInnerHTML(element, html) {
    element[innerHTML()] = html instanceof Element ? html[innerHTML()] : html;
  }
  /**
   * Sets the content of a tooltip
   */

  function setContent(contentEl, props) {
    if (props.content instanceof Element) {
      setInnerHTML(contentEl, '');
      contentEl.appendChild(props.content);
    } else if (typeof props.content !== 'function') {
      var key = props.allowHTML ? 'innerHTML' : 'textContent';
      contentEl[key] = props.content;
    }
  }
  /**
   * Returns the child elements of a popper element
   */

  function getChildren(popper) {
    return {
      tooltip: popper.querySelector(Selectors.TOOLTIP),
      backdrop: popper.querySelector(Selectors.BACKDROP),
      content: popper.querySelector(Selectors.CONTENT),
      arrow: popper.querySelector(Selectors.ARROW) || popper.querySelector(Selectors.ROUND_ARROW)
    };
  }
  /**
   * Adds `data-inertia` attribute
   */

  function addInertia(tooltip) {
    tooltip.setAttribute('data-inertia', '');
  }
  /**
   * Removes `data-inertia` attribute
   */

  function removeInertia(tooltip) {
    tooltip.removeAttribute('data-inertia');
  }
  /**
   * Creates an arrow element and returns it
   */

  function createArrowElement(arrowType) {
    var arrow = div();

    if (arrowType === 'round') {
      arrow.className = 'tippy-roundarrow';
      setInnerHTML(arrow, '<svg viewBox="0 0 18 7" xmlns="http://www.w3.org/2000/svg"><path d="M0 7s2.021-.015 5.253-4.218C6.584 1.051 7.797.007 9 0c1.203-.007 2.416 1.035 3.761 2.782C16.012 7.005 18 7 18 7H0z"/></svg>');
    } else {
      arrow.className = 'tippy-arrow';
    }

    return arrow;
  }
  /**
   * Creates a backdrop element and returns it
   */

  function createBackdropElement() {
    var backdrop = div();
    backdrop.className = 'tippy-backdrop';
    backdrop.setAttribute('data-state', 'hidden');
    return backdrop;
  }
  /**
   * Adds interactive-related attributes
   */

  function addInteractive(popper, tooltip) {
    popper.setAttribute('tabindex', '-1');
    tooltip.setAttribute('data-interactive', '');
  }
  /**
   * Removes interactive-related attributes
   */

  function removeInteractive(popper, tooltip) {
    popper.removeAttribute('tabindex');
    tooltip.removeAttribute('data-interactive');
  }
  /**
   * Applies a transition duration to a list of elements
   */

  function applyTransitionDuration(els, value) {
    els.forEach(function (el) {
      if (el) {
        el.style.transitionDuration = "".concat(value, "ms");
      }
    });
  }
  /**
   * Add/remove transitionend listener from tooltip
   */

  function toggleTransitionEndListener(tooltip, action, listener) {
    // UC Browser hasn't adopted the `transitionend` event despite supporting
    // unprefixed transitions...
    var eventName = isUCBrowser && document.body.style.webkitTransition !== undefined ? 'webkitTransitionEnd' : 'transitionend';
    tooltip[action + 'EventListener'](eventName, listener);
  }
  /**
   * Returns the popper's placement, ignoring shifting (top-start, etc)
   */

  function getPopperPlacement(popper) {
    var fullPlacement = popper.getAttribute('x-placement');
    return fullPlacement ? fullPlacement.split('-')[0] : '';
  }
  /**
   * Sets the visibility state to elements so they can begin to transition
   */

  function setVisibilityState(els, state) {
    els.forEach(function (el) {
      if (el) {
        el.setAttribute('data-state', state);
      }
    });
  }
  /**
   * Triggers reflow
   */

  function reflow(popper) {
    void popper.offsetHeight;
  }
  /**
   * Adds/removes theme from tooltip's classList
   */

  function toggleTheme(tooltip, action, theme) {
    theme.split(' ').forEach(function (themeName) {
      tooltip.classList[action](themeName + '-theme');
    });
  }
  /**
   * Constructs the popper element and returns it
   */

  function createPopperElement(id, props) {
    var popper = div();
    popper.className = 'tippy-popper';
    popper.id = "tippy-".concat(id);
    popper.style.zIndex = '' + props.zIndex;

    if (props.role) {
      popper.setAttribute('role', props.role);
    }

    var tooltip = div();
    tooltip.className = 'tippy-tooltip';
    tooltip.style.maxWidth = props.maxWidth + (typeof props.maxWidth === 'number' ? 'px' : '');
    tooltip.setAttribute('data-size', props.size);
    tooltip.setAttribute('data-animation', props.animation);
    tooltip.setAttribute('data-state', 'hidden');
    toggleTheme(tooltip, 'add', props.theme);
    var content = div();
    content.className = 'tippy-content';
    content.setAttribute('data-state', 'hidden');

    if (props.interactive) {
      addInteractive(popper, tooltip);
    }

    if (props.arrow) {
      tooltip.appendChild(createArrowElement(props.arrowType));
    }

    if (props.animateFill) {
      tooltip.appendChild(createBackdropElement());
      tooltip.setAttribute('data-animatefill', '');
    }

    if (props.inertia) {
      addInertia(tooltip);
    }

    setContent(content, props);
    tooltip.appendChild(content);
    popper.appendChild(tooltip);
    return popper;
  }
  /**
   * Updates the popper element based on the new props
   */

  function updatePopperElement(popper, prevProps, nextProps) {
    var _getChildren = getChildren(popper),
        tooltip = _getChildren.tooltip,
        content = _getChildren.content,
        backdrop = _getChildren.backdrop,
        arrow = _getChildren.arrow;

    popper.style.zIndex = '' + nextProps.zIndex;
    tooltip.setAttribute('data-size', nextProps.size);
    tooltip.setAttribute('data-animation', nextProps.animation);
    tooltip.style.maxWidth = nextProps.maxWidth + (typeof nextProps.maxWidth === 'number' ? 'px' : '');

    if (nextProps.role) {
      popper.setAttribute('role', nextProps.role);
    } else {
      popper.removeAttribute('role');
    }

    if (prevProps.content !== nextProps.content) {
      setContent(content, nextProps);
    } // animateFill


    if (!prevProps.animateFill && nextProps.animateFill) {
      tooltip.appendChild(createBackdropElement());
      tooltip.setAttribute('data-animatefill', '');
    } else if (prevProps.animateFill && !nextProps.animateFill) {
      tooltip.removeChild(backdrop);
      tooltip.removeAttribute('data-animatefill');
    } // arrow


    if (!prevProps.arrow && nextProps.arrow) {
      tooltip.appendChild(createArrowElement(nextProps.arrowType));
    } else if (prevProps.arrow && !nextProps.arrow) {
      tooltip.removeChild(arrow);
    } // arrowType


    if (prevProps.arrow && nextProps.arrow && prevProps.arrowType !== nextProps.arrowType) {
      tooltip.replaceChild(createArrowElement(nextProps.arrowType), arrow);
    } // interactive


    if (!prevProps.interactive && nextProps.interactive) {
      addInteractive(popper, tooltip);
    } else if (prevProps.interactive && !nextProps.interactive) {
      removeInteractive(popper, tooltip);
    } // inertia


    if (!prevProps.inertia && nextProps.inertia) {
      addInertia(tooltip);
    } else if (prevProps.inertia && !nextProps.inertia) {
      removeInertia(tooltip);
    } // theme


    if (prevProps.theme !== nextProps.theme) {
      toggleTheme(tooltip, 'remove', prevProps.theme);
      toggleTheme(tooltip, 'add', nextProps.theme);
    }
  }
  /**
   * Runs the callback after the popper's position has been updated
   * update() is debounced with Promise.resolve() or setTimeout()
   * scheduleUpdate() is update() wrapped in requestAnimationFrame()
   */

  function afterPopperPositionUpdates(popperInstance, callback) {
    var popper = popperInstance.popper,
        options = popperInstance.options;
    var onCreate = options.onCreate,
        onUpdate = options.onUpdate;

    options.onCreate = options.onUpdate = function (data) {
      reflow(popper);
      callback();

      if (onUpdate) {
        onUpdate(data);
      }

      options.onCreate = onCreate;
      options.onUpdate = onUpdate;
    };
  }
  /**
   * Hides all visible poppers on the document
   */

  function hideAll() {
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        checkHideOnClick = _ref.checkHideOnClick,
        exclude = _ref.exclude,
        duration = _ref.duration;

    arrayFrom(document.querySelectorAll(Selectors.POPPER)).forEach(function (popper) {
      var instance = popper._tippy;

      if (instance && (checkHideOnClick ? instance.props.hideOnClick === true : true) && (!exclude || popper !== exclude.popper)) {
        instance.hide(duration);
      }
    });
  }
  /**
   * Determines if the mouse cursor is outside of the popper's interactive border
   * region
   */

  function isCursorOutsideInteractiveBorder(popperPlacement, popperRect, event, props) {
    if (!popperPlacement) {
      return true;
    }

    var x = event.clientX,
        y = event.clientY;
    var interactiveBorder = props.interactiveBorder,
        distance = props.distance;
    var exceedsTop = popperRect.top - y > (popperPlacement === 'top' ? interactiveBorder + distance : interactiveBorder);
    var exceedsBottom = y - popperRect.bottom > (popperPlacement === 'bottom' ? interactiveBorder + distance : interactiveBorder);
    var exceedsLeft = popperRect.left - x > (popperPlacement === 'left' ? interactiveBorder + distance : interactiveBorder);
    var exceedsRight = x - popperRect.right > (popperPlacement === 'right' ? interactiveBorder + distance : interactiveBorder);
    return exceedsTop || exceedsBottom || exceedsLeft || exceedsRight;
  }
  /**
   * Returns the distance offset, taking into account the default offset due to
   * the transform: translate() rule (10px) in CSS
   */

  function getOffsetDistanceInPx(distance) {
    return -(distance - 10) + 'px';
  }

  var isUsingTouch = false;
  function onDocumentTouch() {
    if (isUsingTouch) {
      return;
    }

    isUsingTouch = true;

    if (isIOS) {
      document.body.classList.add('tippy-iOS');
    }

    if (window.performance) {
      document.addEventListener('mousemove', onDocumentMouseMove);
    }
  }
  var lastMouseMoveTime = 0;
  function onDocumentMouseMove() {
    var now = performance.now(); // Chrome 60+ is 1 mousemove per animation frame, use 20ms time difference

    if (now - lastMouseMoveTime < 20) {
      isUsingTouch = false;
      document.removeEventListener('mousemove', onDocumentMouseMove);

      if (!isIOS) {
        document.body.classList.remove('tippy-iOS');
      }
    }

    lastMouseMoveTime = now;
  }
  function onDocumentClick(event) {
    // Simulated events dispatched on the document
    if (!(event.target instanceof Element)) {
      return hideAll();
    } // Clicked on an interactive popper


    var popper = closest(event.target, Selectors.POPPER);

    if (popper && popper._tippy && popper._tippy.props.interactive) {
      return;
    } // Clicked on a reference


    var reference = closestCallback(event.target, function (el) {
      return el._tippy && el._tippy.reference === el;
    });

    if (reference) {
      var instance = reference._tippy;

      if (instance) {
        var isClickTrigger = includes(instance.props.trigger || '', 'click');

        if (isUsingTouch || isClickTrigger) {
          return hideAll({
            exclude: instance,
            checkHideOnClick: true
          });
        }

        if (instance.props.hideOnClick !== true || isClickTrigger) {
          return;
        }

        instance.clearDelayTimeouts();
      }
    }

    hideAll({
      checkHideOnClick: true
    });
  }
  function onWindowBlur() {
    var _document = document,
        activeElement = _document.activeElement;

    if (activeElement && activeElement.blur && activeElement._tippy) {
      activeElement.blur();
    }
  }
  /**
   * Adds the needed global event listeners
   */

  function bindGlobalEventListeners() {
    document.addEventListener('click', onDocumentClick, true);
    document.addEventListener('touchstart', onDocumentTouch, PASSIVE);
    window.addEventListener('blur', onWindowBlur);
  }

  var idCounter = 1;
  /**
   * Creates and returns a Tippy object. We're using a closure pattern instead of
   * a class so that the exposed object API is clean without private members
   * prefixed with `_`.
   */

  function createTippy(reference, collectionProps) {
    var props = evaluateProps(reference, collectionProps); // If the reference shouldn't have multiple tippys, return null early

    if (!props.multiple && reference._tippy) {
      return null;
    }
    /* ======================= 🔒 Private members 🔒 ======================= */
    // The last trigger event type that caused the tippy to show


    var lastTriggerEventType; // The last mousemove event object created by the document mousemove event

    var lastMouseMoveEvent; // Timeout created by the show delay

    var showTimeoutId; // Timeout created by the hide delay

    var hideTimeoutId; // Frame created by scheduleHide()

    var animationFrameId; // Flag to determine if the tippy is scheduled to show due to the show timeout

    var isScheduledToShow = false; // The current `transitionend` callback reference

    var transitionEndListener; // Array of event listeners currently attached to the reference element

    var listeners = []; // Private onMouseMove handler reference, debounced or not

    var debouncedOnMouseMove = props.interactiveDebounce > 0 ? debounce(onMouseMove, props.interactiveDebounce) : onMouseMove; // Node the tippy is currently appended to

    var parentNode;
    /* ======================= 🔑 Public members 🔑 ======================= */
    // id used for the `aria-describedby` / `aria-labelledby` attribute

    var id = idCounter++; // Popper element reference

    var popper = createPopperElement(id, props); // Popper element children: { arrow, backdrop, content, tooltip }

    var popperChildren = getChildren(popper);
    var state = {
      // Is the instance currently enabled?
      isEnabled: true,
      // Is the tippy currently showing and not transitioning out?
      isVisible: false,
      // Has the instance been destroyed?
      isDestroyed: false,
      // Is the tippy currently mounted to the DOM?
      isMounted: false,
      // Has the tippy finished transitioning in?
      isShown: false // Popper.js instance for the tippy is lazily created

    };
    var popperInstance = null;
    var instance = {
      // properties
      id: id,
      reference: reference,
      popper: popper,
      popperChildren: popperChildren,
      popperInstance: popperInstance,
      props: props,
      state: state,
      // methods
      clearDelayTimeouts: clearDelayTimeouts,
      set: set,
      setContent: setContent$$1,
      show: show,
      hide: hide,
      enable: enable,
      disable: disable,
      destroy: destroy
    };
    addTriggersToReference();

    if (!props.lazy) {
      createPopperInstance();
      instance.popperInstance.disableEventListeners();
    }

    if (props.showOnInit) {
      scheduleShow();
    } // Ensure the reference element can receive focus (and is not a delegate)


    if (props.a11y && !props.target && !canReceiveFocus(reference)) {
      reference.setAttribute('tabindex', '0');
    } // Prevent a tippy with a delay from hiding if the cursor left then returned
    // before it started hiding


    popper.addEventListener('mouseenter', function (event) {
      if (instance.props.interactive && instance.state.isVisible && lastTriggerEventType === 'mouseenter') {
        scheduleShow(event);
      }
    });
    popper.addEventListener('mouseleave', function () {
      if (instance.props.interactive && lastTriggerEventType === 'mouseenter') {
        document.addEventListener('mousemove', debouncedOnMouseMove);
      }
    }); // Install shortcuts

    reference._tippy = instance;
    popper._tippy = instance;
    return instance;
    /* ======================= 🔒 Private methods 🔒 ======================= */

    /**
     * Positions the virtual reference near the cursor
     */

    function positionVirtualReferenceNearCursor(event) {
      var _lastMouseMoveEvent = lastMouseMoveEvent = event,
          clientX = _lastMouseMoveEvent.clientX,
          clientY = _lastMouseMoveEvent.clientY;

      if (!instance.popperInstance) {
        return;
      } // Ensure virtual reference is padded to prevent tooltip from
      // overflowing. Maybe Popper.js issue?


      var placement = getPopperPlacement(instance.popper);
      var padding = instance.props.arrow ? PADDING + (instance.props.arrowType === 'round' ? 18 : 16) : PADDING;
      var isVerticalPlacement = includes(['top', 'bottom'], placement);
      var isHorizontalPlacement = includes(['left', 'right'], placement); // Top / left boundary

      var x = isVerticalPlacement ? Math.max(padding, clientX) : clientX;
      var y = isHorizontalPlacement ? Math.max(padding, clientY) : clientY; // Bottom / right boundary

      if (isVerticalPlacement && x > padding) {
        x = Math.min(clientX, window.innerWidth - padding);
      }

      if (isHorizontalPlacement && y > padding) {
        y = Math.min(clientY, window.innerHeight - padding);
      }

      var rect = instance.reference.getBoundingClientRect();
      var followCursor = instance.props.followCursor;
      var isHorizontal = followCursor === 'horizontal';
      var isVertical = followCursor === 'vertical';
      instance.popperInstance.reference = _extends({}, instance.popperInstance.reference, {
        getBoundingClientRect: function getBoundingClientRect() {
          return {
            width: 0,
            height: 0,
            top: isHorizontal ? rect.top : y,
            bottom: isHorizontal ? rect.bottom : y,
            left: isVertical ? rect.left : x,
            right: isVertical ? rect.right : x
          };
        },
        clientWidth: 0,
        clientHeight: 0
      });
      instance.popperInstance.scheduleUpdate();

      if (followCursor === 'initial' && instance.state.isVisible) {
        removeFollowCursorListener();
      }
    }
    /**
     * Creates the tippy instance for a delegate when it's been triggered
     */


    function createDelegateChildTippy(event) {
      if (event) {
        var targetEl = closest(event.target, instance.props.target);

        if (targetEl && !targetEl._tippy) {
          createTippy(targetEl, _extends({}, instance.props, {
            content: evaluateValue(collectionProps.content, [targetEl]),
            appendTo: collectionProps.appendTo,
            target: '',
            showOnInit: true
          }));
          scheduleShow(event);
        }
      }
    }
    /**
     * Setup before show() is invoked (delays, etc.)
     */


    function scheduleShow(event) {
      clearDelayTimeouts();

      if (instance.state.isVisible) {
        return;
      } // Is a delegate, create an instance for the child target


      if (instance.props.target) {
        return createDelegateChildTippy(event);
      }

      isScheduledToShow = true;

      if (instance.props.wait) {
        return instance.props.wait(instance, event);
      } // If the tooltip has a delay, we need to be listening to the mousemove as
      // soon as the trigger event is fired, so that it's in the correct position
      // upon mount.
      // Edge case: if the tooltip is still mounted, but then scheduleShow() is
      // called, it causes a jump.


      if (hasFollowCursorBehavior() && !instance.state.isMounted) {
        document.addEventListener('mousemove', positionVirtualReferenceNearCursor);
      }

      var delay = getValue(instance.props.delay, 0, defaultProps.delay);

      if (delay) {
        showTimeoutId = setTimeout(function () {
          show();
        }, delay);
      } else {
        show();
      }
    }
    /**
     * Setup before hide() is invoked (delays, etc.)
     */


    function scheduleHide() {
      clearDelayTimeouts();

      if (!instance.state.isVisible) {
        return removeFollowCursorListener();
      }

      isScheduledToShow = false;
      var delay = getValue(instance.props.delay, 1, defaultProps.delay);

      if (delay) {
        hideTimeoutId = setTimeout(function () {
          if (instance.state.isVisible) {
            hide();
          }
        }, delay);
      } else {
        // Fixes a `transitionend` problem when it fires 1 frame too
        // late sometimes, we don't want hide() to be called.
        animationFrameId = requestAnimationFrame(function () {
          hide();
        });
      }
    }
    /**
     * Removes the follow cursor listener
     */


    function removeFollowCursorListener() {
      document.removeEventListener('mousemove', positionVirtualReferenceNearCursor);
    }
    /**
     * Cleans up old listeners
     */


    function cleanupOldMouseListeners() {
      document.body.removeEventListener('mouseleave', scheduleHide);
      document.removeEventListener('mousemove', debouncedOnMouseMove);
    }
    /**
     * Event listener invoked upon trigger
     */


    function onTrigger(event) {
      if (!instance.state.isEnabled || isEventListenerStopped(event)) {
        return;
      }

      if (!instance.state.isVisible) {
        lastTriggerEventType = event.type;

        if (event instanceof MouseEvent) {
          lastMouseMoveEvent = event;
        }
      } // Toggle show/hide when clicking click-triggered tooltips


      if (event.type === 'click' && instance.props.hideOnClick !== false && instance.state.isVisible) {
        scheduleHide();
      } else {
        scheduleShow(event);
      }
    }
    /**
     * Event listener used for interactive tooltips to detect when they should
     * hide
     */


    function onMouseMove(event) {
      var referenceTheCursorIsOver = closestCallback(event.target, function (el) {
        return el._tippy;
      });
      var isCursorOverPopper = closest(event.target, Selectors.POPPER) === instance.popper;
      var isCursorOverReference = referenceTheCursorIsOver === instance.reference;

      if (isCursorOverPopper || isCursorOverReference) {
        return;
      }

      if (isCursorOutsideInteractiveBorder(getPopperPlacement(instance.popper), instance.popper.getBoundingClientRect(), event, instance.props)) {
        cleanupOldMouseListeners();
        scheduleHide();
      }
    }
    /**
     * Event listener invoked upon mouseleave
     */


    function onMouseLeave(event) {
      if (isEventListenerStopped(event)) {
        return;
      }

      if (instance.props.interactive) {
        document.body.addEventListener('mouseleave', scheduleHide);
        document.addEventListener('mousemove', debouncedOnMouseMove);
        return;
      }

      scheduleHide();
    }
    /**
     * Event listener invoked upon blur
     */


    function onBlur(event) {
      if (event.target !== instance.reference) {
        return;
      }

      if (instance.props.interactive && event.relatedTarget && instance.popper.contains(event.relatedTarget)) {
        return;
      }

      scheduleHide();
    }
    /**
     * Event listener invoked when a child target is triggered
     */


    function onDelegateShow(event) {
      if (closest(event.target, instance.props.target)) {
        scheduleShow(event);
      }
    }
    /**
     * Event listener invoked when a child target should hide
     */


    function onDelegateHide(event) {
      if (closest(event.target, instance.props.target)) {
        scheduleHide();
      }
    }
    /**
     * Determines if an event listener should stop further execution due to the
     * `touchHold` option
     */


    function isEventListenerStopped(event) {
      var supportsTouch = 'ontouchstart' in window;
      var isTouchEvent = includes(event.type, 'touch');
      var touchHold = instance.props.touchHold;
      return supportsTouch && isUsingTouch && touchHold && !isTouchEvent || isUsingTouch && !touchHold && isTouchEvent;
    }
    /**
     * Creates the popper instance for the instance
     */


    function createPopperInstance() {
      var popperOptions = instance.props.popperOptions;
      var _instance$popperChild = instance.popperChildren,
          tooltip = _instance$popperChild.tooltip,
          arrow = _instance$popperChild.arrow;
      var preventOverflowModifier = getModifier(popperOptions, 'preventOverflow');

      function applyMutations(data) {
        if (instance.props.flip && !instance.props.flipOnUpdate) {
          if (data.flipped) {
            instance.popperInstance.options.placement = data.placement;
          }

          setFlipModifierEnabled(instance.popperInstance.modifiers, false);
        }

        tooltip.setAttribute('x-placement', data.placement);
        var basePlacement = getPopperPlacement(instance.popper);
        var styles = tooltip.style; // Account for the `distance` offset

        styles.top = styles.bottom = styles.left = styles.right = '';
        styles[basePlacement] = getOffsetDistanceInPx(instance.props.distance);
        var padding = preventOverflowModifier && preventOverflowModifier.padding !== undefined ? preventOverflowModifier.padding : PADDING;
        var isPaddingNumber = typeof padding === 'number';

        var computedPadding = _extends({
          top: isPaddingNumber ? padding : padding.top,
          bottom: isPaddingNumber ? padding : padding.bottom,
          left: isPaddingNumber ? padding : padding.left,
          right: isPaddingNumber ? padding : padding.right
        }, !isPaddingNumber && padding);

        computedPadding[basePlacement] = isPaddingNumber ? padding + instance.props.distance : (padding[basePlacement] || 0) + instance.props.distance;
        instance.popperInstance.modifiers.filter(function (m) {
          return m.name === 'preventOverflow';
        })[0].padding = computedPadding;
      }

      var config = _extends({
        placement: instance.props.placement
      }, popperOptions, {
        modifiers: _extends({}, popperOptions ? popperOptions.modifiers : {}, {
          preventOverflow: _extends({
            boundariesElement: instance.props.boundary,
            padding: PADDING
          }, preventOverflowModifier),
          arrow: _extends({
            element: arrow,
            enabled: !!arrow
          }, getModifier(popperOptions, 'arrow')),
          flip: _extends({
            enabled: instance.props.flip,
            // The tooltip is offset by 10px from the popper in CSS,
            // we need to account for its distance
            padding: instance.props.distance + PADDING,
            behavior: instance.props.flipBehavior
          }, getModifier(popperOptions, 'flip')),
          offset: _extends({
            offset: instance.props.offset
          }, getModifier(popperOptions, 'offset'))
        }),
        // This gets invoked when calling `.set()` and updating a popper
        // instance dependency, since a new popper instance gets created
        onCreate: function onCreate(data) {
          applyMutations(data);

          if (popperOptions && popperOptions.onCreate) {
            popperOptions.onCreate(data);
          }
        },
        // This gets invoked on initial create and show()/scroll/resize update.
        // This is due to `afterPopperPositionUpdates` overwriting onCreate()
        // with onUpdate()
        onUpdate: function onUpdate(data) {
          applyMutations(data);

          if (popperOptions && popperOptions.onUpdate) {
            popperOptions.onUpdate(data);
          }
        }
      });

      instance.popperInstance = new Popper(instance.reference, instance.popper, config);
    }
    /**
     * Mounts the tooltip to the DOM, callback to show tooltip is run **after**
     * popper's position has updated
     */


    function mount(callback) {
      var shouldEnableListeners = !hasFollowCursorBehavior() && !(instance.props.followCursor === 'initial' && isUsingTouch);

      if (!instance.popperInstance) {
        createPopperInstance();

        if (!shouldEnableListeners) {
          instance.popperInstance.disableEventListeners();
        }
      } else {
        if (!hasFollowCursorBehavior()) {
          instance.popperInstance.scheduleUpdate();

          if (shouldEnableListeners) {
            instance.popperInstance.enableEventListeners();
          }
        }

        setFlipModifierEnabled(instance.popperInstance.modifiers, instance.props.flip);
      } // If the instance previously had followCursor behavior, it will be
      // positioned incorrectly if triggered by `focus` afterwards.
      // Update the reference back to the real DOM element


      instance.popperInstance.reference = instance.reference;
      var arrow = instance.popperChildren.arrow;

      if (hasFollowCursorBehavior()) {
        if (arrow) {
          arrow.style.margin = '0';
        }

        if (lastMouseMoveEvent) {
          positionVirtualReferenceNearCursor(lastMouseMoveEvent);
        }
      } else if (arrow) {
        arrow.style.margin = '';
      } // Allow followCursor: 'initial' on touch devices


      if (isUsingTouch && lastMouseMoveEvent && instance.props.followCursor === 'initial') {
        positionVirtualReferenceNearCursor(lastMouseMoveEvent);

        if (arrow) {
          arrow.style.margin = '0';
        }
      }

      afterPopperPositionUpdates(instance.popperInstance, callback);
      var appendTo = instance.props.appendTo;
      parentNode = appendTo === 'parent' ? instance.reference.parentNode : evaluateValue(appendTo, [instance.reference]);

      if (!parentNode.contains(instance.popper)) {
        parentNode.appendChild(instance.popper);
        instance.props.onMount(instance);
        instance.state.isMounted = true;
      }
    }
    /**
     * Determines if the instance is in `followCursor` mode
     */


    function hasFollowCursorBehavior() {
      return instance.props.followCursor && !isUsingTouch && lastTriggerEventType !== 'focus';
    }
    /**
     * Updates the tooltip's position on each animation frame
     */


    function makeSticky() {
      applyTransitionDuration([instance.popper], isIE ? 0 : instance.props.updateDuration);

      function updatePosition() {
        if (instance.popperInstance) {
          instance.popperInstance.scheduleUpdate();
        }

        if (instance.state.isMounted) {
          requestAnimationFrame(updatePosition);
        } else {
          applyTransitionDuration([instance.popper], 0);
        }
      }

      updatePosition();
    }
    /**
     * Invokes a callback once the tooltip has fully transitioned out
     */


    function onTransitionedOut(duration, callback) {
      onTransitionEnd(duration, function () {
        if (!instance.state.isVisible && parentNode && parentNode.contains(instance.popper)) {
          callback();
        }
      });
    }
    /**
     * Invokes a callback once the tooltip has fully transitioned in
     */


    function onTransitionedIn(duration, callback) {
      onTransitionEnd(duration, callback);
    }
    /**
     * Invokes a callback once the tooltip's CSS transition ends
     */


    function onTransitionEnd(duration, callback) {
      var tooltip = instance.popperChildren.tooltip;
      /**
       * Listener added as the `transitionend` handler
       */

      function listener(event) {
        if (event.target === tooltip) {
          toggleTransitionEndListener(tooltip, 'remove', listener);
          callback();
        }
      } // Make callback synchronous if duration is 0
      // `transitionend` won't fire otherwise


      if (duration === 0) {
        return callback();
      }

      toggleTransitionEndListener(tooltip, 'remove', transitionEndListener);
      toggleTransitionEndListener(tooltip, 'add', listener);
      transitionEndListener = listener;
    }
    /**
     * Adds an event listener to the reference and stores it in `listeners`
     */


    function on(eventType, handler) {
      var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      instance.reference.addEventListener(eventType, handler, options);
      listeners.push({
        eventType: eventType,
        handler: handler,
        options: options
      });
    }
    /**
     * Adds event listeners to the reference based on the `trigger` prop
     */


    function addTriggersToReference() {
      if (instance.props.touchHold && !instance.props.target) {
        on('touchstart', onTrigger, PASSIVE);
        on('touchend', onMouseLeave, PASSIVE);
      }

      instance.props.trigger.trim().split(' ').forEach(function (eventType) {
        if (eventType === 'manual') {
          return;
        } // Non-delegates


        if (!instance.props.target) {
          on(eventType, onTrigger);

          switch (eventType) {
            case 'mouseenter':
              on('mouseleave', onMouseLeave);
              break;

            case 'focus':
              on(isIE ? 'focusout' : 'blur', onBlur);
              break;
          }
        } else {
          // Delegates
          switch (eventType) {
            case 'mouseenter':
              on('mouseover', onDelegateShow);
              on('mouseout', onDelegateHide);
              break;

            case 'focus':
              on('focusin', onDelegateShow);
              on('focusout', onDelegateHide);
              break;

            case 'click':
              on(eventType, onDelegateShow);
              break;
          }
        }
      });
    }
    /**
     * Removes event listeners from the reference
     */


    function removeTriggersFromReference() {
      listeners.forEach(function (_ref) {
        var eventType = _ref.eventType,
            handler = _ref.handler,
            options = _ref.options;
        instance.reference.removeEventListener(eventType, handler, options);
      });
      listeners = [];
    }
    /**
     * Returns inner elements used in show/hide methods
     */


    function getInnerElements() {
      return [instance.popperChildren.tooltip, instance.popperChildren.backdrop, instance.popperChildren.content];
    }
    /* ======================= 🔑 Public methods 🔑 ======================= */

    /**
     * Enables the instance to allow it to show or hide
     */


    function enable() {
      instance.state.isEnabled = true;
    }
    /**
     * Disables the instance to disallow it to show or hide
     */


    function disable() {
      instance.state.isEnabled = false;
    }
    /**
     * Clears pending timeouts related to the `delay` prop if any
     */


    function clearDelayTimeouts() {
      clearTimeout(showTimeoutId);
      clearTimeout(hideTimeoutId);
      cancelAnimationFrame(animationFrameId);
    }
    /**
     * Sets new props for the instance and redraws the tooltip
     */


    function set(options) {
      // Backwards-compatible after TypeScript change
      options = options || {};
      validateOptions(options, defaultProps);
      var prevProps = instance.props;
      var nextProps = evaluateProps(instance.reference, _extends({}, instance.props, options, {
        ignoreAttributes: true
      }));
      nextProps.ignoreAttributes = hasOwnProperty(options, 'ignoreAttributes') ? options.ignoreAttributes || false : prevProps.ignoreAttributes;
      instance.props = nextProps;

      if (hasOwnProperty(options, 'trigger') || hasOwnProperty(options, 'touchHold')) {
        removeTriggersFromReference();
        addTriggersToReference();
      }

      if (hasOwnProperty(options, 'interactiveDebounce')) {
        cleanupOldMouseListeners();
        debouncedOnMouseMove = debounce(onMouseMove, options.interactiveDebounce || 0);
      }

      updatePopperElement(instance.popper, prevProps, nextProps);
      instance.popperChildren = getChildren(instance.popper);

      if (instance.popperInstance) {
        instance.popperInstance.update();

        if (POPPER_INSTANCE_DEPENDENCIES.some(function (prop) {
          return hasOwnProperty(options, prop);
        })) {
          instance.popperInstance.destroy();
          createPopperInstance();

          if (!instance.state.isVisible) {
            instance.popperInstance.disableEventListeners();
          }

          if (instance.props.followCursor && lastMouseMoveEvent) {
            positionVirtualReferenceNearCursor(lastMouseMoveEvent);
          }
        }
      }
    }
    /**
     * Shortcut for .set({ content: newContent })
     */


    function setContent$$1(content) {
      set({
        content: content
      });
    }
    /**
     * Shows the tooltip
     */


    function show() {
      var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getValue(instance.props.duration, 0, defaultProps.duration[1]);

      if (instance.state.isDestroyed || !instance.state.isEnabled || isUsingTouch && !instance.props.touch) {
        return;
      } // Destroy tooltip if the reference element is no longer on the DOM


      if (!hasOwnProperty(instance.reference, 'isVirtual') && !document.documentElement.contains(instance.reference)) {
        return destroy();
      } // Do not show tooltip if the reference element has a `disabled` attribute


      if (instance.reference.hasAttribute('disabled')) {
        return;
      }

      if (instance.props.onShow(instance) === false) {
        return;
      }

      instance.popper.style.visibility = 'visible';
      instance.state.isVisible = true;

      if (instance.props.interactive) {
        instance.reference.classList.add('tippy-active');
      } // Prevent a transition if the popper is at the opposite placement


      applyTransitionDuration([instance.popper, instance.popperChildren.tooltip, instance.popperChildren.backdrop], 0);
      mount(function () {
        if (!instance.state.isVisible) {
          return;
        } // Arrow will sometimes not be positioned correctly. Force another update


        if (!hasFollowCursorBehavior()) {
          instance.popperInstance.update();
        }

        applyTransitionDuration([instance.popper], props.updateDuration);
        applyTransitionDuration(getInnerElements(), duration);

        if (instance.popperChildren.backdrop) {
          instance.popperChildren.content.style.transitionDelay = Math.round(duration / 12) + 'ms';
        }

        if (instance.props.sticky) {
          makeSticky();
        }

        setVisibilityState(getInnerElements(), 'visible');
        onTransitionedIn(duration, function () {
          instance.popperChildren.tooltip.classList.add('tippy-notransition');

          if (instance.props.aria) {
            instance.reference.setAttribute("aria-".concat(instance.props.aria), instance.popper.id);
          }

          instance.props.onShown(instance);
          instance.state.isShown = true;
        });
      });
    }
    /**
     * Hides the tooltip
     */


    function hide() {
      var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getValue(instance.props.duration, 1, defaultProps.duration[1]);

      if (instance.state.isDestroyed || !instance.state.isEnabled) {
        return;
      }

      if (instance.props.onHide(instance) === false) {
        return;
      }

      instance.popperChildren.tooltip.classList.remove('tippy-notransition');

      if (instance.props.interactive) {
        instance.reference.classList.remove('tippy-active');
      }

      instance.popper.style.visibility = 'hidden';
      instance.state.isVisible = false;
      instance.state.isShown = false;
      applyTransitionDuration(getInnerElements(), duration);
      setVisibilityState(getInnerElements(), 'hidden');
      onTransitionedOut(duration, function () {
        if (!isScheduledToShow) {
          removeFollowCursorListener();
        }

        if (instance.props.aria) {
          instance.reference.removeAttribute("aria-".concat(instance.props.aria));
        }

        instance.popperInstance.disableEventListeners();
        instance.popperInstance.options.placement = instance.props.placement;
        parentNode.removeChild(instance.popper);
        instance.props.onHidden(instance);
        instance.state.isMounted = false;
      });
    }
    /**
     * Destroys the tooltip
     */


    function destroy(destroyTargetInstances) {
      if (instance.state.isDestroyed) {
        return;
      } // If the popper is currently mounted to the DOM, we want to ensure it gets
      // hidden and unmounted instantly upon destruction


      if (instance.state.isMounted) {
        hide(0);
      }

      removeTriggersFromReference();
      delete instance.reference._tippy;

      if (instance.props.target && destroyTargetInstances) {
        arrayFrom(instance.reference.querySelectorAll(instance.props.target)).forEach(function (child) {
          if (child._tippy) {
            child._tippy.destroy();
          }
        });
      }

      if (instance.popperInstance) {
        instance.popperInstance.destroy();
      }

      instance.state.isDestroyed = true;
    }
  }

  /**
   * Groups an array of instances by taking control of their props during
   * certain lifecycles.
   */

  function group(instances) {
    var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref$delay = _ref.delay,
        delay = _ref$delay === void 0 ? instances[0].props.delay : _ref$delay,
        _ref$duration = _ref.duration,
        duration = _ref$duration === void 0 ? 0 : _ref$duration;

    // Already grouped. Cannot group instances more than once (yet) or stale lifecycle
    // closures will be invoked, causing a stack overflow
    if (instances.some(function (instance) {
      return hasOwnProperty(instance, '_originalProps');
    })) {
      return;
    }

    var isAnyTippyOpen = false;
    instances.forEach(function (instance) {
      instance._originalProps = _extends({}, instance.props);
    });

    function setIsAnyTippyOpen(value) {
      isAnyTippyOpen = value;
      updateInstances();
    }

    function onShow(instance) {
      instance._originalProps.onShow(instance);

      instances.forEach(function (instance) {
        instance.set({
          duration: duration
        });
        instance.hide();
      });
      setIsAnyTippyOpen(true);
    }

    function onHide(instance) {
      instance._originalProps.onHide(instance);

      setIsAnyTippyOpen(false);
    }

    function onShown(instance) {
      instance._originalProps.onShown(instance);

      instance.set({
        duration: instance._originalProps.duration
      });
    }

    function updateInstances() {
      instances.forEach(function (instance) {
        instance.set({
          onShow: onShow,
          onShown: onShown,
          onHide: onHide,
          delay: isAnyTippyOpen ? [0, Array.isArray(delay) ? delay[1] : delay] : delay,
          duration: isAnyTippyOpen ? duration : instance._originalProps.duration
        });
      });
    }

    updateInstances();
  }

  var globalEventListenersBound = false;
  /**
   * Exported module
   */

  function tippy(targets, options) {
    validateOptions(options || {}, defaultProps);

    if (!globalEventListenersBound) {
      bindGlobalEventListeners();
      globalEventListenersBound = true;
    }

    var props = _extends({}, defaultProps, options); // If they are specifying a virtual positioning reference, we need to polyfill
    // some native DOM props


    if (isBareVirtualElement(targets)) {
      polyfillElementPrototypeProperties(targets);
    }

    var instances = getArrayOfElements(targets).reduce(function (acc, reference) {
      var instance = reference && createTippy(reference, props);

      if (instance) {
        acc.push(instance);
      }

      return acc;
    }, []);
    return isSingular(targets) ? instances[0] : instances;
  }
  /**
   * Static props
   */


  tippy.version = version;
  tippy.defaults = defaultProps;
  /**
   * Static methods
   */

  tippy.setDefaults = function (partialDefaults) {
    Object.keys(partialDefaults).forEach(function (key) {
      // @ts-ignore
      defaultProps[key] = partialDefaults[key];
    });
  };

  tippy.hideAll = hideAll;
  tippy.group = group;
  /**
   * Auto-init tooltips for elements with a `data-tippy="..."` attribute
   */

  function autoInit() {
    arrayFrom(document.querySelectorAll('[data-tippy]')).forEach(function (el) {
      var content = el.getAttribute('data-tippy');

      if (content) {
        tippy(el, {
          content: content
        });
      }
    });
  }

  if (isBrowser) {
    setTimeout(autoInit);
  }

  return tippy;

}));
//# sourceMappingURL=index.js.map