/**!
* tippy.js v4.0.2
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

  var version = "4.0.2";

  var isBrowser = typeof window !== 'undefined';
  var ua = isBrowser && navigator.userAgent;
  var isIE = /MSIE |Trident\//.test(ua);
  var isUCBrowser = /UCBrowser\//.test(ua);
  var isIOS = isBrowser && /iPhone|iPad|iPod/.test(navigator.platform) && !window.MSStream;

  var Defaults = {
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
    delay: [0, 20],
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
   * @param {ArrayLike} value
   * @return {any[]}
   */

  function arrayFrom(value) {
    return [].slice.call(value);
  }
  /**
   * Ponyfill for Element.prototype.closest
   * @param {Element} element
   * @param {String} parentSelector
   * @return {Element}
   */

  function closest(element, parentSelector) {
    return (elementProto.closest || function (selector) {
      var el = this;

      while (el) {
        if (matches.call(el, selector)) return el;
        el = el.parentElement;
      }
    }).call(element, parentSelector);
  }
  /**
   * Works like Element.prototype.closest, but uses a callback instead
   * @param {Element} element
   * @param {Function} callback
   * @return {Element}
   */

  function closestCallback(element, callback) {
    while (element) {
      if (callback(element)) return element;
      element = element.parentElement;
    }
  }

  /**
   * Determines if a value is a plain object
   * @param {any} value
   * @return {Boolean}
   */

  function isPlainObject(value) {
    return {}.toString.call(value) === '[object Object]';
  }
  /**
   * Safe .hasOwnProperty check, for prototype-less objects
   * @param {Object} obj
   * @param {String} key
   * @return {Boolean}
   */

  function hasOwnProperty(obj, key) {
    return {}.hasOwnProperty.call(obj, key);
  }
  /**
   * Returns an array of elements based on the value
   * @param {any} value
   * @return {Array}
   */

  function getArrayOfElements(value) {
    if (isSingular(value)) {
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
   * @param {any} value
   * @param {Number} index
   * @param {any} defaultValue
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
   * @param {Function} fn
   * @param {Number} ms
   */

  function debounce(fn, ms) {
    var timeoutId;
    return function () {
      var _this = this,
          _arguments = arguments;

      clearTimeout(timeoutId);
      timeoutId = setTimeout(function () {
        return fn.apply(_this, _arguments);
      }, ms);
    };
  }
  /**
   * Prevents errors from being thrown while accessing nested modifier objects
   * in `popperOptions`
   * @param {Object} obj
   * @param {String} key
   * @return {Object|undefined}
   */

  function getModifier(obj, key) {
    return obj && obj.modifiers && obj.modifiers[key];
  }
  /**
   * Determines if an array or string includes a value
   * @param {Array|String} a
   * @param {any} b
   * @return {Boolean}
   */

  function includes(a, b) {
    return a.indexOf(b) > -1;
  }
  /**
   * Determines if the value is singular-like
   * @param {any} value
   * @return {Boolean}
   */

  function isSingular(value) {
    return isPlainObject(value) || value instanceof Element;
  }
  /**
   * Tricking bundlers, linters, and minifiers
   * @return {String}
   */

  function innerHTML() {
    return 'innerHTML';
  }
  /**
   * Evaluates a function if one, or returns the value
   * @param {any} value
   * @param {any[]} args
   * @return {Boolean}
   */

  function evaluateValue(value, args) {
    return typeof value === 'function' ? value.apply(null, args) : value;
  }
  /**
   * Sets a popperInstance `flip` modifier's enabled state
   * @param {Object[]} modifiers
   * @param {any} value
   */

  function setFlipModifierEnabled(modifiers, value) {
    modifiers.filter(function (m) {
      return m.name === 'flip';
    })[0].enabled = value;
  }

  /**
   * Returns a new `div` element
   * @return {HTMLDivElement}
   */

  function div() {
    return document.createElement('div');
  }
  /**
   * Sets the innerHTML of an element while tricking linters & minifiers
   * @param {HTMLElement} el
   * @param {Element|String} html
   */

  function setInnerHTML(el, html) {
    el[innerHTML()] = html instanceof Element ? html[innerHTML()] : html;
  }
  /**
   * Sets the content of a tooltip
   * @param {HTMLElement} contentEl
   * @param {Object} props
   */

  function setContent(contentEl, props) {
    if (props.content instanceof Element) {
      setInnerHTML(contentEl, '');
      contentEl.appendChild(props.content);
    } else {
      contentEl[props.allowHTML ? 'innerHTML' : 'textContent'] = props.content;
    }
  }
  /**
   * Returns the child elements of a popper element
   * @param {HTMLElement} popper
   * @return {Object}
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
   * @param {HTMLElement} tooltip
   */

  function addInertia(tooltip) {
    tooltip.setAttribute('data-inertia', '');
  }
  /**
   * Removes `data-inertia` attribute
   * @param {HTMLElement} tooltip
   */

  function removeInertia(tooltip) {
    tooltip.removeAttribute('data-inertia');
  }
  /**
   * Creates an arrow element and returns it
   * @return {HTMLDivElement}
   */

  function createArrowElement(arrowType) {
    var arrow = div();

    if (arrowType === 'round') {
      arrow.className = 'tippy-roundarrow';
      setInnerHTML(arrow, '<svg viewBox="0 0 24 8" xmlns="http://www.w3.org/2000/svg"><path d="M3 8s2.021-.015 5.253-4.218C9.584 2.051 10.797 1.007 12 1c1.203-.007 2.416 1.035 3.761 2.782C19.012 8.005 21 8 21 8H3z"/></svg>');
    } else {
      arrow.className = 'tippy-arrow';
    }

    return arrow;
  }
  /**
   * Creates a backdrop element and returns it
   * @return {HTMLDivElement}
   */

  function createBackdropElement() {
    var backdrop = div();
    backdrop.className = 'tippy-backdrop';
    backdrop.setAttribute('data-state', 'hidden');
    return backdrop;
  }
  /**
   * Adds interactive-related attributes
   * @param {HTMLElement} popper
   * @param {HTMLElement} tooltip
   */

  function addInteractive(popper, tooltip) {
    popper.setAttribute('tabindex', '-1');
    tooltip.setAttribute('data-interactive', '');
  }
  /**
   * Removes interactive-related attributes
   * @param {HTMLElement} popper
   * @param {HTMLElement} tooltip
   */

  function removeInteractive(popper, tooltip) {
    popper.removeAttribute('tabindex');
    tooltip.removeAttribute('data-interactive');
  }
  /**
   * Applies a transition duration to a list of elements
   * @param {Array} els
   * @param {Number} value
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
   * @param {Element} tooltip
   * @param {String} action
   * @param {Function} listener
   */

  function toggleTransitionEndListener(tooltip, action, listener) {
    // UC Browser hasn't adopted the `transitionend` event despite supporting
    // unprefixed transitions...
    var eventName = isUCBrowser && document.body.style.WebkitTransition !== undefined ? 'webkitTransitionEnd' : 'transitionend';
    tooltip[action + 'EventListener'](eventName, listener);
  }
  /**
   * Returns the popper's placement, ignoring shifting (top-start, etc)
   * @param {Element} popper
   * @return {String}
   */

  function getPopperPlacement(popper) {
    var fullPlacement = popper.getAttribute('x-placement');
    return fullPlacement ? fullPlacement.split('-')[0] : '';
  }
  /**
   * Sets the visibility state to elements so they can begin to transition
   * @param {Array} els
   * @param {String} state
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
   * @param {Element} popper
   */

  function reflow(popper) {
    void popper.offsetHeight;
  }
  /**
   * Adds/removes theme from tooltip's classList
   * @param {HTMLDivElement} tooltip
   * @param {String} action
   * @param {String} theme
   */

  function toggleTheme(tooltip, action, theme) {
    theme.split(' ').forEach(function (themeName) {
      tooltip.classList[action](themeName + '-theme');
    });
  }
  /**
   * Constructs the popper element and returns it
   * @param {Number} id
   * @param {Object} props
   * @return {HTMLDivElement}
   */

  function createPopperElement(id, props) {
    var popper = div();
    popper.className = 'tippy-popper';
    popper.id = "tippy-".concat(id);
    popper.style.zIndex = props.zIndex;

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
   * @param {HTMLDivElement} popper
   * @param {Object} prevProps
   * @param {Object} nextProps
   */

  function updatePopperElement(popper, prevProps, nextProps) {
    var _getChildren = getChildren(popper),
        tooltip = _getChildren.tooltip,
        content = _getChildren.content,
        backdrop = _getChildren.backdrop,
        arrow = _getChildren.arrow;

    popper.style.zIndex = nextProps.zIndex;
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
   * @param {Popper} popperInstance
   * @param {Function} callback
   */

  function afterPopperPositionUpdates(popperInstance, callback) {
    var popper = popperInstance.popper,
        options = popperInstance.options;
    var onCreate = options.onCreate,
        onUpdate = options.onUpdate;

    options.onCreate = options.onUpdate = function (data) {
      reflow(popper);
      callback();
      onUpdate(data);
      options.onCreate = onCreate;
      options.onUpdate = onUpdate;
    };
  }
  /**
   * Hides all visible poppers on the document
   * @param {Object} options
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
   * @param {String} popperPlacement
   * @param {ClientRect} popperRect
   * @param {MouseEvent} event
   * @param {Object} props
   * @return {Boolean}
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
   * @param {Number} distance
   * @return {String}
   */

  function getOffsetDistanceInPx(distance) {
    return -(distance - 10) + 'px';
  }

  var PASSIVE = {
    passive: true
  };
  var PADDING = 3;

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
  function onDocumentClick(_ref) {
    var target = _ref.target;

    // Simulated events dispatched on the document
    if (!(target instanceof Element)) {
      return hideAll();
    } // Clicked on an interactive popper


    var popper = closest(target, Selectors.POPPER);

    if (popper && popper._tippy && popper._tippy.props.interactive) {
      return;
    } // Clicked on a reference


    var reference = closestCallback(target, function (el) {
      return el._tippy && el._tippy.reference === el;
    });

    if (reference) {
      var instance = reference._tippy;
      var isClickTrigger = includes(instance.props.trigger, 'click');

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

  var keys = Object.keys(Defaults);
  /**
   * Determines if an element can receive focus
   * @param {Element|Object} el
   * @return {Boolean}
   */

  function canReceiveFocus(el) {
    return el instanceof Element ? matches.call(el, 'a[href],area[href],button,details,input,textarea,select,iframe,[tabindex]') && !el.hasAttribute('disabled') : true;
  }
  /**
   * Returns an object of optional props from data-tippy-* attributes
   * @param {Element|Object} reference
   * @return {Object}
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
   * @param {Object} virtualReference
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
   * Evaluates the props object by merging data attributes and
   * disabling conflicting options where necessary
   * @param {Element} reference
   * @param {Object} props
   * @return {Object}
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
   * @param {Object} options
   * @param {Object} defaults
   */

  function validateOptions() {
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var defaults = arguments.length > 1 ? arguments[1] : undefined;
    Object.keys(options).forEach(function (option) {
      if (!hasOwnProperty(defaults, option)) {
        throw new Error("[tippy]: `".concat(option, "` is not a valid option"));
      }
    });
  }

  var idCounter = 1;
  /**
   * Creates and returns a Tippy object. We're using a closure pattern instead of
   * a class so that the exposed object API is clean without private members
   * prefixed with `_`.
   * @param {Element} reference
   * @param {Object} collectionProps
   * @return {Object} instance
   */

  function createTippy(reference, collectionProps) {
    var props = evaluateProps(reference, collectionProps); // If the reference shouldn't have multiple tippys, return null early

    if (!props.multiple && reference._tippy) {
      return null;
    }
    /* ======================= 🔒 Private members 🔒 ======================= */
    // The last trigger event object that caused the tippy to show


    var lastTriggerEvent = {}; // The last mousemove event object created by the document mousemove event

    var lastMouseMoveEvent = null; // Timeout created by the show delay

    var showTimeoutId = 0; // Timeout created by the hide delay

    var hideTimeoutId = 0; // Flag to determine if the tippy is preparing to show due to the show timeout

    var isPreparingToShow = false; // The current `transitionend` callback reference

    var transitionEndListener = function transitionEndListener() {}; // Array of event listeners currently attached to the reference element


    var listeners = []; // Private onMouseMove handler reference, debounced or not

    var debouncedOnMouseMove = props.interactiveDebounce > 0 ? debounce(onMouseMove, props.interactiveDebounce) : onMouseMove; // Node the tippy is currently appended to

    var parentNode = null;
    /* ======================= 🔑 Public members 🔑 ======================= */
    // id used for the `aria-describedby` / `aria-labelledby` attribute

    var id = idCounter++; // Popper element reference

    var popper = createPopperElement(id, props); // Prevent a tippy with a delay from hiding if the cursor left then returned
    // before it started hiding

    popper.addEventListener('mouseenter', function (event) {
      if (instance.props.interactive && instance.state.isVisible && lastTriggerEvent.type === 'mouseenter') {
        prepareShow(event);
      }
    });
    popper.addEventListener('mouseleave', function () {
      if (instance.props.interactive && lastTriggerEvent.type === 'mouseenter') {
        document.addEventListener('mousemove', debouncedOnMouseMove);
      }
    }); // Popper element children: { arrow, backdrop, content, tooltip }

    var popperChildren = getChildren(popper); // The state of the tippy

    var state = {
      // If the tippy is currently enabled
      isEnabled: true,
      // show() invoked, not currently transitioning out
      isVisible: false,
      // If the tippy has been destroyed
      isDestroyed: false,
      // If the tippy is on the DOM (transitioning out or in)
      isMounted: false,
      // show() transition finished
      isShown: false // Popper.js instance for the tippy is lazily created

    };
    var popperInstance = null; // 🌟 tippy instance

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
      prepareShow();
    } // Ensure the reference element can receive focus (and is not a delegate)


    if (props.a11y && !props.target && !canReceiveFocus(reference)) {
      reference.setAttribute('tabindex', '0');
    } // Install shortcuts


    reference._tippy = instance;
    popper._tippy = instance;
    return instance;
    /* ======================= 🔒 Private methods 🔒 ======================= */

    /**
     * Positions the virtual reference near the mouse cursor
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
      var padding = instance.popperChildren.arrow ? PADDING + 16 : PADDING;
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
      instance.popperInstance.reference = {
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
      };
      instance.popperInstance.scheduleUpdate();

      if (followCursor === 'initial' && instance.state.isVisible) {
        removeFollowCursorListener();
      }
    }
    /**
     * Creates the tippy instance for a delegate when it's been triggered
     */


    function createDelegateChildTippy(event) {
      var targetEl = closest(event.target, instance.props.target);

      if (targetEl && !targetEl._tippy) {
        createTippy(targetEl, _extends({}, instance.props, {
          content: evaluateValue(collectionProps.content, [targetEl]),
          appendTo: collectionProps.appendTo,
          target: '',
          showOnInit: true
        }));
        prepareShow(event);
      }
    }
    /**
     * Setup before show() is invoked (delays, etc.)
     */


    function prepareShow(event) {
      clearDelayTimeouts();

      if (instance.state.isVisible) {
        return;
      } // Is a delegate, create an instance for the child target


      if (instance.props.target) {
        return createDelegateChildTippy(event);
      }

      isPreparingToShow = true;

      if (instance.props.wait) {
        return instance.props.wait(instance, event);
      } // If the tooltip has a delay, we need to be listening to the mousemove as
      // soon as the trigger event is fired, so that it's in the correct position
      // upon mount.
      // Edge case: if the tooltip is still mounted, but then prepareShow() is
      // called, it causes a jump.


      if (hasFollowCursorBehavior() && !instance.state.isMounted) {
        document.addEventListener('mousemove', positionVirtualReferenceNearCursor);
      }

      var delay = getValue(instance.props.delay, 0, Defaults.delay);

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


    function prepareHide() {
      clearDelayTimeouts();

      if (!instance.state.isVisible) {
        return removeFollowCursorListener();
      }

      isPreparingToShow = false;
      var delay = getValue(instance.props.delay, 1, Defaults.delay);

      if (delay) {
        hideTimeoutId = setTimeout(function () {
          if (instance.state.isVisible) {
            hide();
          }
        }, delay);
      } else {
        hide();
      }
    }
    /**
     * Removes the follow cursor listener
     */


    function removeFollowCursorListener() {
      document.removeEventListener('mousemove', positionVirtualReferenceNearCursor);
      lastMouseMoveEvent = null;
    }
    /**
     * Cleans up old listeners
     */


    function cleanupOldMouseListeners() {
      document.body.removeEventListener('mouseleave', prepareHide);
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
        lastTriggerEvent = event; // Use the `mouseenter` event as a "mock" mousemove event for touch
        // devices

        if (isUsingTouch && includes(event.type, 'mouse')) {
          lastMouseMoveEvent = event;
        }
      } // Toggle show/hide when clicking click-triggered tooltips


      if (event.type === 'click' && instance.props.hideOnClick !== false && instance.state.isVisible) {
        prepareHide();
      } else {
        prepareShow(event);
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
        prepareHide();
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
        document.body.addEventListener('mouseleave', prepareHide);
        document.addEventListener('mousemove', debouncedOnMouseMove);
        return;
      }

      prepareHide();
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

      prepareHide();
    }
    /**
     * Event listener invoked when a child target is triggered
     */


    function onDelegateShow(event) {
      if (closest(event.target, instance.props.target)) {
        prepareShow(event);
      }
    }
    /**
     * Event listener invoked when a child target should hide
     */


    function onDelegateHide(event) {
      if (closest(event.target, instance.props.target)) {
        prepareHide();
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
      instance.popperInstance = new Popper(instance.reference, instance.popper, _extends({
        placement: instance.props.placement
      }, popperOptions, {
        modifiers: _extends({}, popperOptions ? popperOptions.modifiers : {}, {
          preventOverflow: _extends({
            boundariesElement: instance.props.boundary,
            padding: PADDING
          }, getModifier(popperOptions, 'preventOverflow')),
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
        onUpdate: function onUpdate(data) {
          if (!instance.props.flipOnUpdate) {
            if (data.flipped) {
              instance.popperInstance.options.placement = data.placement;
            }

            setFlipModifierEnabled(instance.popperInstance.modifiers, false);
          }

          var styles = tooltip.style;
          styles.top = '';
          styles.bottom = '';
          styles.left = '';
          styles.right = '';
          styles[getPopperPlacement(instance.popper)] = getOffsetDistanceInPx(instance.props.distance);
        }
      }));
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

        setFlipModifierEnabled(instance.popperInstance.modifiers, true);
      } // If the instance previously had followCursor behavior, it will be
      // positioned incorrectly if triggered by `focus` afterwards.
      // Update the reference back to the real DOM element


      instance.popperInstance.reference = instance.reference;
      var arrow = instance.popperChildren.arrow;

      if (hasFollowCursorBehavior()) {
        if (arrow) {
          arrow.style.margin = '0';
        }

        var delay = getValue(instance.props.delay, 0, Defaults.delay);

        if (lastTriggerEvent.type) {
          positionVirtualReferenceNearCursor(delay && lastMouseMoveEvent ? lastMouseMoveEvent : lastTriggerEvent);
        }
      } else if (arrow) {
        arrow.style.margin = '';
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
      return instance.props.followCursor && !isUsingTouch && lastTriggerEvent.type !== 'focus';
    }
    /**
     * Updates the tooltip's position on each animation frame + timeout
     */


    function makeSticky() {
      applyTransitionDuration([instance.popper], isIE ? 0 : instance.props.updateDuration);

      var updatePosition = function updatePosition() {
        if (instance.popperInstance) {
          instance.popperInstance.scheduleUpdate();
        }

        if (instance.state.isMounted) {
          requestAnimationFrame(updatePosition);
        } else {
          applyTransitionDuration([instance.popper], 0);
        }
      };

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
      // Make callback synchronous if duration is 0
      if (duration === 0) {
        return callback();
      }

      var tooltip = instance.popperChildren.tooltip;

      var listener = function listener(e) {
        if (e.target === tooltip) {
          toggleTransitionEndListener(tooltip, 'remove', listener);
          callback();
        }
      };

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
        }

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
    }
    /**
     * Sets new props for the instance and redraws the tooltip
     */


    function set() {
      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      validateOptions(options, Defaults);
      var prevProps = instance.props;
      var nextProps = evaluateProps(instance.reference, _extends({}, instance.props, options, {
        ignoreAttributes: true
      }));
      nextProps.ignoreAttributes = hasOwnProperty(options, 'ignoreAttributes') ? options.ignoreAttributes : prevProps.ignoreAttributes;
      instance.props = nextProps;

      if (hasOwnProperty(options, 'trigger') || hasOwnProperty(options, 'touchHold')) {
        removeTriggersFromReference();
        addTriggersToReference();
      }

      if (hasOwnProperty(options, 'interactiveDebounce')) {
        cleanupOldMouseListeners();
        debouncedOnMouseMove = debounce(onMouseMove, options.interactiveDebounce);
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
      var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getValue(instance.props.duration, 0, Defaults.duration[0]);

      if (instance.state.isDestroyed || !instance.state.isEnabled || isUsingTouch && !instance.props.touch) {
        return;
      } // Destroy tooltip if the reference element is no longer on the DOM


      if (!instance.reference.isVirtual && !document.documentElement.contains(instance.reference)) {
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
        } // Allow followCursor: 'initial' on touch devices


        if (isUsingTouch && instance.props.followCursor === 'initial') {
          positionVirtualReferenceNearCursor(lastMouseMoveEvent);
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
      var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getValue(instance.props.duration, 1, Defaults.duration[1]);

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
        if (!isPreparingToShow) {
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
   * @param {Object[]} targets
   * @param {Object} options
   */
  function group(instances) {
    var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref$delay = _ref.delay,
        delay = _ref$delay === void 0 ? instances[0].props.delay : _ref$delay,
        _ref$duration = _ref.duration,
        duration = _ref$duration === void 0 ? 0 : _ref$duration;

    var isAnyTippyOpen = false;
    instances.forEach(function (instance) {
      instance._originalProps = {
        duration: instance.props.duration,
        onHide: instance.props.onHide,
        onShow: instance.props.onShow,
        onShown: instance.props.onShown
      };
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
   * @param {String|Element|Element[]|NodeList|Object} targets
   * @param {Object} options
   * @return {Object}
   */

  function tippy(targets, options) {
    validateOptions(options, Defaults);

    if (!globalEventListenersBound) {
      bindGlobalEventListeners();
      globalEventListenersBound = true;
    }

    var props = _extends({}, Defaults, options); // If they are specifying a virtual positioning reference, we need to polyfill
    // some native DOM props


    if (isPlainObject(targets)) {
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
  tippy.defaults = Defaults;
  /**
   * Static methods
   */

  tippy.setDefaults = function (partialDefaults) {
    Object.keys(partialDefaults).forEach(function (key) {
      Defaults[key] = partialDefaults[key];
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