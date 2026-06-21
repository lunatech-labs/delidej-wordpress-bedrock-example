/**
 * Popup Campaign Frontend JavaScript
 *
 * Handles triggers, animations, forms, countdown timers, coupon copy,
 * frequency control, and analytics for popup campaigns.
 *
 * @package HashBar
 * @since 2.0.0
 */

(function() {
  'use strict';

  /**
   * Popup Campaign Manager
   */
  var PopupCampaignManager = {
    popups: [],
    activePopup: null,
    initialized: false,

    /**
     * Initialize all popup campaigns
     */
    init: function() {
      if (this.initialized) {
        return;
      }

      var self = this;
      var popupElements = document.querySelectorAll('.hashbar-popup-campaign');

      if (popupElements.length === 0) {
        return;
      }

      popupElements.forEach(function(element) {
        var popup = self.createPopupInstance(element);
        if (popup) {
          self.popups.push(popup);
          self.initializePopup(popup);
        }
      });

      this.initialized = true;
    },

    /**
     * Create popup instance from DOM element
     */
    createPopupInstance: function(element) {
      var popupId = element.getAttribute('data-popup-id');
      if (!popupId) {
        return null;
      }

      return {
        id: popupId,
        element: element,
        overlay: document.querySelector('.hashbar-popup-overlay[data-popup-id="' + popupId + '"]'),
        isVisible: false,
        hasTriggered: false,
        previousFocus: null,
        config: this.parsePopupConfig(element)
      };
    },

    /**
     * Parse popup configuration from data attributes
     */
    parsePopupConfig: function(element) {
      return {
        // Position
        position: element.getAttribute('data-position') || 'center',

        // Trigger settings
        triggerType: element.getAttribute('data-trigger-type') || 'time_delay',
        triggerDelay: parseInt(element.getAttribute('data-trigger-delay'), 10) || 5,
        triggerScrollPercent: parseInt(element.getAttribute('data-trigger-scroll-percent'), 10) || 50,
        triggerClickSelector: element.getAttribute('data-trigger-click-selector') || '',
        triggerClickDelay: parseInt(element.getAttribute('data-trigger-click-delay'), 10) || 0,
        triggerInactivityTime: parseInt(element.getAttribute('data-trigger-inactivity-time'), 10) || 30,
        triggerElementSelector: element.getAttribute('data-trigger-element-selector') || '',
        triggerPageViewsCount: parseInt(element.getAttribute('data-trigger-page-views-count'), 10) || 3,

        // Exit intent
        exitSensitivity: element.getAttribute('data-exit-sensitivity') || 'medium',
        exitMobileEnabled: element.getAttribute('data-exit-mobile-enabled') === 'true',

        // Visitor targeting
        targetNewVisitors: element.getAttribute('data-target-new-visitors') === 'true',
        targetReturningVisitors: element.getAttribute('data-target-returning-visitors') === 'true',

        // Animation
        animationEntry: element.getAttribute('data-animation-entry') || 'fadeIn',
        animationExit: element.getAttribute('data-animation-exit') || 'fadeOut',
        animationDuration: parseInt(element.getAttribute('data-animation-duration'), 10) || 300,
        animationDelay: parseInt(element.getAttribute('data-animation-delay'), 10) || 0,

        // Frequency
        frequencyType: element.getAttribute('data-frequency-type') || 'always',
        frequencyDays: parseInt(element.getAttribute('data-frequency-days'), 10) || 7,
        frequencyTimes: parseInt(element.getAttribute('data-frequency-times'), 10) || 3,
        afterClose: element.getAttribute('data-after-close') || 'respect_frequency',
        afterCloseDays: parseInt(element.getAttribute('data-after-close-days'), 10) || 7,
        afterConvert: element.getAttribute('data-after-convert') || 'hide_popup',
        afterConvertDays: parseInt(element.getAttribute('data-after-convert-days'), 10) || 30,

        // Display settings
        overlayEnabled: element.getAttribute('data-overlay-enabled') !== 'false',
        overlayClose: element.getAttribute('data-overlay-close') !== 'false',
        closeEnabled: element.getAttribute('data-close-enabled') !== 'false',
        escClose: element.getAttribute('data-esc-close') !== 'false',

        // Countdown
        countdownEnabled: element.getAttribute('data-countdown-enabled') === 'true',
        countdownType: element.getAttribute('data-countdown-type') || 'fixed_date',
        countdownEndDate: element.getAttribute('data-countdown-end-date') || '',
        countdownDuration: parseInt(element.getAttribute('data-countdown-duration'), 10) || 24,
        countdownDailyTime: element.getAttribute('data-countdown-daily-time') || '00:00',
        countdownRecurringDays: element.getAttribute('data-countdown-recurring-days') || '',
        countdownTimezone: element.getAttribute('data-countdown-timezone') || 'site',
        countdownShowDays: element.getAttribute('data-countdown-show-days') !== 'false',
        countdownShowHours: element.getAttribute('data-countdown-show-hours') !== 'false',
        countdownShowMinutes: element.getAttribute('data-countdown-show-minutes') !== 'false',
        countdownShowSeconds: element.getAttribute('data-countdown-show-seconds') !== 'false',
        countdownExpiredAction: element.getAttribute('data-countdown-expired-action') || 'show_message',
        countdownExpiredMessage: element.getAttribute('data-countdown-expired-message') || 'This offer has expired!',
        countdownExpiredRedirect: element.getAttribute('data-countdown-expired-redirect') || '',

        // Coupon
        couponEnabled: element.getAttribute('data-coupon-enabled') === 'true',
        couponCode: element.getAttribute('data-coupon-code') || '',
        couponAutoCopy: element.getAttribute('data-coupon-auto-copy') === 'true',
        couponCopiedText: element.getAttribute('data-coupon-copied-text') || 'Copied!',
        couponClickToCopyText: element.getAttribute('data-coupon-click-to-copy-text') || 'Click to copy',

        // Form
        formEnabled: element.getAttribute('data-form-enabled') === 'true',

        // Schedule (for visitor timezone)
        scheduleTimezone: element.getAttribute('data-schedule-timezone') || 'site',
        scheduleStartDate: element.getAttribute('data-schedule-start-date') || '',
        scheduleEndDate: element.getAttribute('data-schedule-end-date') || '',
        scheduleDaysEnabled: element.getAttribute('data-schedule-days-enabled') === 'true',
        scheduleDays: element.getAttribute('data-schedule-days') || '',
        scheduleTimeEnabled: element.getAttribute('data-schedule-time-enabled') === 'true',
        scheduleTimeStart: element.getAttribute('data-schedule-time-start') || '00:00',
        scheduleTimeEnd: element.getAttribute('data-schedule-time-end') || '23:59'
      };
    },

    /**
     * Initialize a single popup
     */
    initializePopup: function(popup) {
      var self = this;

      // Check frequency first
      if (!this.shouldShowByFrequency(popup)) {
        return;
      }

      // Check visitor targeting (new vs returning)
      if (!this.shouldShowByVisitorType(popup)) {
        return;
      }

      // Check schedule (visitor timezone)
      if (!this.shouldShowBySchedule(popup)) {
        return;
      }

      // Initialize close button
      this.initCloseButton(popup);

      // Initialize overlay click
      this.initOverlayClick(popup);

      // Initialize ESC key
      this.initEscKey(popup);

      // Initialize countdown if enabled
      if (popup.config.countdownEnabled) {
        this.initCountdown(popup);
      }

      // Initialize coupon if enabled
      if (popup.config.couponEnabled) {
        this.initCoupon(popup);
      }

      // Initialize form if enabled
      if (popup.config.formEnabled) {
        this.initForm(popup);
      }

      // Initialize button click tracking
      this.initButtonClickTracking(popup);

      // Setup trigger
      this.setupTrigger(popup);
    },

    /**
     * Initialize button click tracking
     */
    initButtonClickTracking: function(popup) {
      var self = this;

      // Track CTA button clicks
      var ctaButtons = popup.element.querySelectorAll('.hashbar-popup-cta, [data-popup-cta]');
      ctaButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          self.trackEvent(popup, 'click', 'cta');
        });
      });

      // Track secondary button clicks
      var secondaryButtons = popup.element.querySelectorAll('.hashbar-popup-secondary');
      secondaryButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          self.trackEvent(popup, 'click', 'secondary');
        });
      });

      // Track any submit button clicks (for conversions)
      var submitButtons = popup.element.querySelectorAll('.hashbar-popup-submit, [type="submit"]');
      submitButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          self.trackEvent(popup, 'click', 'submit');
        });
      });
    },

    /**
     * Check if popup should show based on frequency settings
     */
    shouldShowByFrequency: function(popup) {
      var popupId = popup.id;
      var config = popup.config;
      var cookieKey = 'hashbar_popup_' + popupId;
      var sessionKey = 'hashbar_popup_session_' + popupId;

      // Check if user closed the popup
      var closedCookie = this.getCookie(cookieKey + '_closed');
      if (closedCookie) {
        return false;
      }

      // Check if user converted (submitted form)
      var convertedCookie = this.getCookie(cookieKey + '_converted');
      if (convertedCookie) {
        // Check after convert behavior
        if (config.afterConvert === 'never_show' || config.afterConvert === 'dont_show_ever') {
          return false; // Never show again
        }
        if (config.afterConvert === 'show_after_days') {
          // Check if enough days have passed
          var convertedTime = parseInt(convertedCookie, 10);
          if (!isNaN(convertedTime)) {
            var daysSinceConvert = (Date.now() - convertedTime) / (1000 * 60 * 60 * 24);
            if (daysSinceConvert < config.afterConvertDays) {
              return false; // Not enough days passed
            }
          } else if (convertedCookie === 'true') {
            // Legacy format - treat as never show
            return false;
          }
        }
        // 'always_show' - continue with normal frequency checks
      }

      // Check session storage for session-based blocking
      var sessionBlocked = sessionStorage.getItem(sessionKey + '_blocked');
      if (sessionBlocked === 'true') {
        return false;
      }

      // Check frequency type
      switch (config.frequencyType) {
        case 'always':
          return true;

        case 'once_per_session':
          var sessionShown = sessionStorage.getItem(sessionKey + '_shown');
          if (sessionShown === 'true') {
            return false;
          }
          return true;

        case 'once_per_day':
          var lastShown = this.getCookie(cookieKey + '_last_shown');
          if (lastShown) {
            var lastShownDate = new Date(parseInt(lastShown, 10));
            var now = new Date();
            if (lastShownDate.toDateString() === now.toDateString()) {
              return false;
            }
          }
          return true;

        case 'once_per_x_days':
          var lastShownDays = this.getCookie(cookieKey + '_last_shown');
          if (lastShownDays) {
            var daysSinceShown = (Date.now() - parseInt(lastShownDays, 10)) / (1000 * 60 * 60 * 24);
            if (daysSinceShown < config.frequencyDays) {
              return false;
            }
          }
          return true;

        case 'once_ever':
          var everShown = this.getCookie(cookieKey + '_ever_shown');
          if (everShown === 'true') {
            return false;
          }
          return true;

        case 'x_times_total':
          var showCount = parseInt(this.getCookie(cookieKey + '_show_count') || '0', 10);
          if (showCount >= config.frequencyTimes) {
            return false;
          }
          return true;

        default:
          return true;
      }
    },

    /**
     * Check if popup should show based on visitor type (new vs returning)
     */
    shouldShowByVisitorType: function(popup) {
      var config = popup.config;
      var visitorCookie = 'hashbar_returning_visitor';

      // Check if visitor has been here before
      var isReturningVisitor = this.getCookie(visitorCookie) === 'true';

      // Mark this visitor as returning for future visits
      if (!isReturningVisitor) {
        this.setCookie(visitorCookie, 'true', 365); // Set cookie for 1 year
      }

      // If targeting new visitors only
      if (config.targetNewVisitors && !config.targetReturningVisitors) {
        if (isReturningVisitor) {
          return false;
        }
      }

      // If targeting returning visitors only
      if (config.targetReturningVisitors && !config.targetNewVisitors) {
        if (!isReturningVisitor) {
          return false;
        }
      }

      // If both are enabled or both are disabled, show to everyone
      return true;
    },

    /**
     * Check if popup should show based on schedule (visitor timezone only)
     * Server-side checks handle site timezone, this handles visitor timezone
     */
    shouldShowBySchedule: function(popup) {
      var config = popup.config;

      // Only check for visitor timezone - site timezone is handled server-side
      if (config.scheduleTimezone !== 'visitor') {
        return true;
      }

      var now = new Date();

      // Check start date (visitor timezone)
      if (config.scheduleStartDate) {
        try {
          var startDate = new Date(config.scheduleStartDate);
          if (now < startDate) {
            return false; // Before start date
          }
        } catch (e) {
          // Invalid date, skip check
        }
      }

      // Check end date (visitor timezone)
      if (config.scheduleEndDate) {
        try {
          var endDate = new Date(config.scheduleEndDate);
          if (now > endDate) {
            return false; // After end date
          }
        } catch (e) {
          // Invalid date, skip check
        }
      }

      // Check days of week restriction
      if (config.scheduleDaysEnabled) {
        var scheduleDays = config.scheduleDays;
        if (scheduleDays) {
          try {
            // Parse days array if it's a string
            if (typeof scheduleDays === 'string') {
              scheduleDays = JSON.parse(scheduleDays);
            }

            if (Array.isArray(scheduleDays) && scheduleDays.length > 0) {
              // Get current day name in lowercase (e.g., 'monday', 'tuesday')
              var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
              var currentDay = dayNames[now.getDay()];

              // Convert schedule days to lowercase for comparison
              var scheduleDaysLower = scheduleDays.map(function(day) {
                return day.toLowerCase();
              });

              if (scheduleDaysLower.indexOf(currentDay) === -1) {
                return false; // Current day is not in the allowed days
              }
            }
          } catch (e) {
            // If parsing fails, skip this check
          }
        } else {
          // Days restriction enabled but no days selected - don't display
          return false;
        }
      }

      // Check time of day restriction
      if (config.scheduleTimeEnabled) {
        var timeStart = config.scheduleTimeStart || '00:00';
        var timeEnd = config.scheduleTimeEnd || '23:59';

        // Get current time in HH:mm format
        var currentHours = now.getHours().toString().padStart(2, '0');
        var currentMinutes = now.getMinutes().toString().padStart(2, '0');
        var currentTime = currentHours + ':' + currentMinutes;

        // Simple 24-hour check: current time must be between start and end
        if (currentTime < timeStart || currentTime > timeEnd) {
          return false; // Outside time window
        }
      }

      return true;
    },

    /**
     * Record that popup was shown (for frequency tracking)
     */
    recordPopupShown: function(popup) {
      var popupId = popup.id;
      var config = popup.config;
      var cookieKey = 'hashbar_popup_' + popupId;
      var sessionKey = 'hashbar_popup_session_' + popupId;

      // Mark session as shown
      sessionStorage.setItem(sessionKey + '_shown', 'true');

      // Update cookie-based tracking
      this.setCookie(cookieKey + '_last_shown', Date.now().toString(), 365);

      if (config.frequencyType === 'once_ever') {
        this.setCookie(cookieKey + '_ever_shown', 'true', 3650); // ~10 years
      }

      if (config.frequencyType === 'x_times_total') {
        var showCount = parseInt(this.getCookie(cookieKey + '_show_count') || '0', 10);
        this.setCookie(cookieKey + '_show_count', (showCount + 1).toString(), 365);
      }

      // Track view (impression)
      this.trackEvent(popup, 'view');
    },

    /**
     * Setup trigger for popup
     */
    setupTrigger: function(popup) {
      var self = this;
      var config = popup.config;

      switch (config.triggerType) {
        case 'immediate':
          // Show immediately (animation delay is handled in showPopup)
          self.showPopup(popup);
          break;

        case 'time_delay':
          setTimeout(function() {
            self.showPopup(popup);
          }, config.triggerDelay * 1000);
          break;

        case 'exit_intent':
          this.setupExitIntent(popup);
          break;

        case 'scroll_depth':
          this.setupScrollTrigger(popup);
          break;

        case 'click':
          this.setupClickTrigger(popup);
          break;

        case 'inactivity':
          this.setupInactivityTrigger(popup);
          break;

        case 'element_visible':
          this.setupElementVisibleTrigger(popup);
          break;

        case 'page_views':
          this.setupPageViewsTrigger(popup);
          break;
      }
    },

    /**
     * Setup exit intent trigger
     */
    setupExitIntent: function(popup) {
      var self = this;
      var config = popup.config;
      var sensitivity = config.exitSensitivity;
      var threshold = sensitivity === 'high' ? 50 : (sensitivity === 'low' ? 10 : 30);

      // Desktop: mouse leaving viewport through top
      document.documentElement.addEventListener('mouseleave', function(e) {
        if (popup.hasTriggered) return;

        // Check if mouse is leaving through the top of the viewport
        if (e.clientY <= threshold) {
          self.showPopup(popup);
        }
      });

      // Mobile alternative triggers
      if (config.exitMobileEnabled && this.isMobile()) {
        // Fast scroll up detection
        var lastScrollTop = 0;
        var scrollUpCount = 0;

        window.addEventListener('scroll', function() {
          if (popup.hasTriggered) return;

          var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

          if (scrollTop < lastScrollTop) {
            scrollUpCount++;
            if (scrollUpCount > 3) {
              self.showPopup(popup);
            }
          } else {
            scrollUpCount = 0;
          }

          lastScrollTop = scrollTop;
        });

        // Back button detection (history state)
        window.addEventListener('popstate', function() {
          if (popup.hasTriggered) return;
          self.showPopup(popup);
        });
      }
    },

    /**
     * Setup scroll depth trigger
     */
    setupScrollTrigger: function(popup) {
      var self = this;
      var targetPercent = popup.config.triggerScrollPercent;

      window.addEventListener('scroll', function onScroll() {
        if (popup.hasTriggered) return;

        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var scrollPercent = (scrollTop / docHeight) * 100;

        if (scrollPercent >= targetPercent) {
          window.removeEventListener('scroll', onScroll);
          self.showPopup(popup);
        }
      });
    },

    /**
     * Setup click trigger
     */
    setupClickTrigger: function(popup) {
      var self = this;
      var selector = popup.config.triggerClickSelector;
      var clickDelay = popup.config.triggerClickDelay || 0;

      if (!selector) {
        return;
      }

      document.addEventListener('click', function(e) {
        if (popup.hasTriggered) return;

        var target = e.target.closest(selector);
        if (target) {
          e.preventDefault();
          if (clickDelay > 0) {
            setTimeout(function() {
              self.showPopup(popup);
            }, clickDelay * 1000);
          } else {
            self.showPopup(popup);
          }
        }
      });
    },

    /**
     * Setup inactivity trigger
     */
    setupInactivityTrigger: function(popup) {
      var self = this;
      var inactivityTime = popup.config.triggerInactivityTime * 1000;
      var timer = null;

      function resetTimer() {
        if (popup.hasTriggered) return;

        clearTimeout(timer);
        timer = setTimeout(function() {
          self.showPopup(popup);
        }, inactivityTime);
      }

      // Reset on any user activity
      ['mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(event) {
        document.addEventListener(event, resetTimer, { passive: true });
      });

      // Start timer
      resetTimer();
    },

    /**
     * Setup element visible trigger
     */
    setupElementVisibleTrigger: function(popup) {
      var self = this;
      var selector = popup.config.triggerElementSelector;

      if (!selector) {
        return;
      }

      var targetElement = document.querySelector(selector);
      if (!targetElement) {
        return;
      }

      // Use Intersection Observer
      var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting && !popup.hasTriggered) {
            observer.disconnect();
            self.showPopup(popup);
          }
        });
      }, { threshold: 0.5 });

      observer.observe(targetElement);
    },

    /**
     * Setup page views trigger
     */
    setupPageViewsTrigger: function(popup) {
      var self = this;
      var requiredViews = popup.config.triggerPageViewsCount;
      var storageKey = 'hashbar_popup_page_views_' + popup.id;

      var currentViews = parseInt(localStorage.getItem(storageKey) || '0', 10) + 1;
      localStorage.setItem(storageKey, currentViews.toString());

      if (currentViews >= requiredViews) {
        // Animation delay is handled in showPopup
        self.showPopup(popup);
      }
    },

    /**
     * Show popup with animation
     */
    showPopup: function(popup) {
      if (popup.hasTriggered || popup.isVisible) {
        return;
      }

      // Don't show popup if countdown has expired and action was hide_popup
      if (popup.countdownExpired) {
        return;
      }

      var self = this;
      popup.hasTriggered = true;

      // Internal function to actually show the popup
      function doShowPopup() {
        popup.isVisible = true;
        self.activePopup = popup;

        // Show overlay
        if (popup.overlay && popup.config.overlayEnabled) {
          popup.overlay.classList.add('hashbar-popup-visible');
        }

        // Apply entry animation class
        var element = popup.element;
        element.style.setProperty('--hashbar-popup-duration', popup.config.animationDuration + 'ms');
        element.classList.add('hashbar-popup-visible');
        element.classList.add('hashbar-popup-anim-' + popup.config.animationEntry);

        popup.previousFocus = document.activeElement;

        var closeBtn = element.querySelector('[data-popup-close]');
        var focusTarget = closeBtn && typeof closeBtn.focus === 'function' ? closeBtn : null;
        if (!focusTarget) {
          var focusables = element.querySelectorAll('a[href]:not([tabindex="-1"]), button:not([disabled]):not([tabindex="-1"]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled])');
          focusTarget = focusables.length ? focusables[0] : element;
        }

        if (focusTarget === element) {
          element.setAttribute('tabindex', '-1');
        }

        if (focusTarget && typeof focusTarget.focus === 'function') {
          focusTarget.focus();
        }

        // Record that popup was shown
        self.recordPopupShown(popup);

        // Reinitialize external form plugins for dynamically loaded content
        self.initExternalForms(popup);

        // Remove animation class after animation completes
        setTimeout(function() {
          element.classList.remove('hashbar-popup-anim-' + popup.config.animationEntry);
        }, popup.config.animationDuration);
      }

      // Apply animation delay if set
      if (popup.config.animationDelay > 0) {
        setTimeout(doShowPopup, popup.config.animationDelay);
      } else {
        doShowPopup();
      }
    },

    /**
     * Hide popup with animation
     */
    hidePopup: function(popup, reason) {
      if (!popup.isVisible) {
        return;
      }

      var self = this;
      reason = reason || 'close';

      var element = popup.element;
      var config = popup.config;

      // Apply exit animation
      element.style.setProperty('--hashbar-popup-duration', config.animationDuration + 'ms');
      element.classList.add('hashbar-popup-anim-' + config.animationExit);

      // Hide after animation
      setTimeout(function() {
        element.classList.remove('hashbar-popup-visible');
        element.classList.remove('hashbar-popup-anim-' + config.animationExit);

        // Hide overlay
        if (popup.overlay) {
          popup.overlay.classList.remove('hashbar-popup-visible');
        }

        popup.isVisible = false;
        self.activePopup = null;

        element.removeAttribute('tabindex');

        var prev = popup.previousFocus;
        if (prev && typeof prev.focus === 'function') {
          try {
            prev.focus();
          } catch (err) {}
        }
        popup.previousFocus = null;

        // Handle after close behavior
        if (reason === 'close') {
          self.handleAfterClose(popup);
        } else if (reason === 'convert') {
          self.handleAfterConvert(popup);
        }
      }, config.animationDuration);

      // Track close event
      if (reason === 'close') {
        this.trackEvent(popup, 'close');
      }
    },

    /**
     * Handle after close behavior
     */
    handleAfterClose: function(popup) {
      var config = popup.config;
      var cookieKey = 'hashbar_popup_' + popup.id;
      var sessionKey = 'hashbar_popup_session_' + popup.id;

      switch (config.afterClose) {
        case 'dont_show_session':
          sessionStorage.setItem(sessionKey + '_blocked', 'true');
          break;

        case 'dont_show_x_days':
          this.setCookie(cookieKey + '_closed', 'true', config.afterCloseDays);
          break;

        case 'dont_show_ever':
          this.setCookie(cookieKey + '_closed', 'true', 3650);
          break;

        case 'respect_frequency':
        default:
          // Just let frequency settings handle it
          break;
      }
    },

    /**
     * Handle after convert behavior
     */
    handleAfterConvert: function(popup) {
      var config = popup.config;
      var cookieKey = 'hashbar_popup_' + popup.id;

      switch (config.afterConvert) {
        case 'hide_popup':
          // Default - popup is already hidden
          break;

        case 'show_success':
          // Success message is shown in form handler
          break;

        case 'redirect':
          // Redirect handled in form handler
          break;

        case 'never_show':
          // Never show this popup again after conversion
          this.setCookie(cookieKey + '_converted', 'true', 3650);
          break;

        case 'show_after_days':
          // Show again after X days - store timestamp for checking
          this.setCookie(cookieKey + '_converted', Date.now().toString(), config.afterConvertDays);
          break;

        case 'always_show':
          // Continue showing normally - don't set any cookie
          break;

        case 'dont_show_ever':
          // Legacy support
          this.setCookie(cookieKey + '_converted', 'true', 3650);
          break;
      }
    },

    /**
     * Initialize close button
     */
    initCloseButton: function(popup) {
      var self = this;
      var closeButtons = popup.element.querySelectorAll('.hashbar-popup-close');

      closeButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          self.hidePopup(popup, 'close');
        });
      });
    },

    /**
     * Initialize overlay click to close
     */
    initOverlayClick: function(popup) {
      var self = this;

      if (!popup.overlay || !popup.config.overlayClose) {
        return;
      }

      popup.overlay.addEventListener('click', function(e) {
        if (e.target === popup.overlay) {
          self.hidePopup(popup, 'close');
        }
      });
    },

    /**
     * Initialize ESC key to close
     */
    initEscKey: function(popup) {
      var self = this;

      if (!popup.config.escClose) {
        return;
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.isVisible) {
          self.hidePopup(popup, 'close');
        }
      });
    },

    /**
     * Initialize countdown timer
     */
    initCountdown: function(popup) {
      var self = this;
      var config = popup.config;
      var countdownElement = popup.element.querySelector('.hashbar-popup-countdown');

      if (!countdownElement) {
        return;
      }

      var timerInterval = null;

      // Check initial countdown state before showing popup
      // If countdown has expired and action is hide_popup, keep popup hidden
      var initialRemaining = this.calculateRemainingTime(popup);
      var isExpired = initialRemaining <= 0;
      var shouldHide = isExpired && config.countdownExpiredAction === 'hide_popup';

      if (shouldHide) {
        // Countdown already expired - keep popup hidden and don't start timer
        // Mark as handled so showPopup won't try to show it
        popup.countdownExpired = true;
        return;
      }

      // Countdown not expired - remove initial hidden state so popup can be shown
      if (popup.element.classList.contains('hashbar-popup-countdown-hidden')) {
        popup.element.classList.remove('hashbar-popup-countdown-hidden');
        popup.element.style.visibility = '';
        popup.element.style.opacity = '';
        // Also show overlay if it exists and was hidden
        if (popup.overlay) {
          popup.overlay.style.visibility = '';
          popup.overlay.style.opacity = '';
        }
      }

      function updateCountdown() {
        var remaining = self.calculateRemainingTime(popup);

        if (remaining <= 0) {
          clearInterval(timerInterval);
          self.handleCountdownExpired(popup, countdownElement);
          return;
        }

        self.renderCountdownTime(countdownElement, remaining, config);
      }

      // Initial update
      updateCountdown();

      // Update every second
      timerInterval = setInterval(updateCountdown, 1000);
    },

    /**
     * Get timezone offset in hours
     */
    getTimezoneOffset: function(timezone) {
      if (timezone === 'site') {
        // Use site timezone offset from PHP
        return typeof HashbarPopupData !== 'undefined' && HashbarPopupData.siteTimezoneOffset !== undefined
          ? parseFloat(HashbarPopupData.siteTimezoneOffset)
          : 0;
      }
      if (timezone === 'visitor') {
        // Use visitor's local timezone
        return -(new Date().getTimezoneOffset() / 60);
      }
      return 0;
    },

    /**
     * Calculate remaining time for countdown
     */
    calculateRemainingTime: function(popup) {
      var config = popup.config;
      var now = Date.now();
      var timezone = config.countdownTimezone || 'site';
      var timezoneOffset = this.getTimezoneOffset(timezone);

      switch (config.countdownType) {
        case 'fixed_date':
        case 'fixed':
          if (!config.countdownEndDate) {
            return 0;
          }

          var remaining;

          if (timezone === 'visitor') {
            // For visitor timezone, parse the ISO date directly
            // The stored date represents the exact moment the user selected (in their timezone when they set it)
            // All visitors will see the countdown end at the same actual moment
            var endDateObj = new Date(config.countdownEndDate);
            if (isNaN(endDateObj.getTime())) {
              return 0;
            }
            remaining = endDateObj.getTime() - now;
          } else {
            // For site timezone, we need to interpret the stored time as site time
            // Parse the date components from the stored value
            var endDateObj = new Date(config.countdownEndDate);
            if (isNaN(endDateObj.getTime())) {
              return 0;
            }

            // Get the local time components (what the admin saw in the date picker)
            var endYear = endDateObj.getFullYear();
            var endMonth = endDateObj.getMonth();
            var endDay = endDateObj.getDate();
            var endHours = endDateObj.getHours();
            var endMinutes = endDateObj.getMinutes();

            // Calculate with site timezone offset
            var targetOffsetMs = timezoneOffset * 60 * 60 * 1000;
            var endTimeUtc = new Date(Date.UTC(endYear, endMonth, endDay, endHours, endMinutes, 0, 0));
            // The actual UTC time is: input time minus the timezone offset
            var actualEndTimeUtc = endTimeUtc.getTime() - targetOffsetMs;
            remaining = actualEndTimeUtc - now;
          }

          return remaining;

        case 'daily_recurring':
          // Calculate next reset time
          var resetTimeParts = config.countdownDailyTime ? config.countdownDailyTime.split(':') : ['00', '00'];
          var resetHour = parseInt(resetTimeParts[0], 10) || 0;
          var resetMinute = parseInt(resetTimeParts[1], 10) || 0;

          var distance;

          if (timezone === 'visitor') {
            // For visitor timezone, work directly in local time
            var nowLocal = new Date();
            var resetDate = new Date(nowLocal);
            resetDate.setHours(resetHour, resetMinute, 0, 0);

            if (resetDate <= nowLocal) {
              resetDate.setDate(resetDate.getDate() + 1);
            }

            distance = resetDate.getTime() - now;
          } else {
            // For site timezone, work in UTC and apply offset
            var targetOffsetMs = timezoneOffset * 60 * 60 * 1000;

            // Get current UTC time
            var nowUtc = now;
            // Calculate current time in site timezone (as UTC timestamp)
            var nowInSiteTz = nowUtc + targetOffsetMs;
            var nowInSiteTzDate = new Date(nowInSiteTz);

            // Create reset time for today in site timezone (using UTC methods)
            var nextResetUtc = Date.UTC(
              nowInSiteTzDate.getUTCFullYear(),
              nowInSiteTzDate.getUTCMonth(),
              nowInSiteTzDate.getUTCDate(),
              resetHour,
              resetMinute,
              0, 0
            );

            // Convert reset time from site timezone to UTC
            nextResetUtc = nextResetUtc - targetOffsetMs;

            // If reset time has already passed, move to next day
            if (nextResetUtc <= nowUtc) {
              nextResetUtc += 24 * 60 * 60 * 1000; // Add 24 hours
            }

            distance = nextResetUtc - nowUtc;
          }

          return distance;

        case 'evergreen':
          // Each visitor gets their own timer
          var sessionKey = 'hashbar_popup_countdown_' + popup.id;
          var startTime = sessionStorage.getItem(sessionKey);

          if (!startTime) {
            startTime = now.toString();
            sessionStorage.setItem(sessionKey, startTime);
          }

          var elapsed = now - parseInt(startTime, 10);
          var duration = config.countdownDuration * 60 * 60 * 1000; // hours to ms
          return duration - elapsed;

        default:
          return 0;
      }
    },

    /**
     * Render countdown time to element
     */
    renderCountdownTime: function(element, remaining, config) {
      var days = Math.floor(remaining / (1000 * 60 * 60 * 24));
      var hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((remaining % (1000 * 60)) / 1000);

      // Update individual unit elements using data attributes
      var daysEl = element.querySelector('[data-countdown-days]');
      var hoursEl = element.querySelector('[data-countdown-hours]');
      var minutesEl = element.querySelector('[data-countdown-minutes]');
      var secondsEl = element.querySelector('[data-countdown-seconds]');

      if (daysEl) daysEl.textContent = this.padZero(days);
      if (hoursEl) hoursEl.textContent = this.padZero(hours);
      if (minutesEl) minutesEl.textContent = this.padZero(minutes);
      if (secondsEl) secondsEl.textContent = this.padZero(seconds);
    },

    /**
     * Handle countdown expiration
     */
    handleCountdownExpired: function(popup, countdownElement) {
      var config = popup.config;

      switch (config.countdownExpiredAction) {
        case 'hide_popup':
          this.hidePopup(popup, 'expired');
          break;

        case 'show_message':
          countdownElement.innerHTML = '<div class="hashbar-popup-countdown-expired">' +
            this.escapeHtml(config.countdownExpiredMessage) + '</div>';
          break;

        case 'redirect':
          if (config.countdownExpiredRedirect) {
            window.location.href = config.countdownExpiredRedirect;
          }
          break;

        case 'hide_countdown':
          countdownElement.style.display = 'none';
          break;

        default:
          countdownElement.innerHTML = '<div class="hashbar-popup-countdown-expired">Expired</div>';
      }
    },

    /**
     * Initialize coupon functionality
     */
    initCoupon: function(popup) {
      var self = this;
      var config = popup.config;
      var couponElement = popup.element.querySelector('.hashbar-popup-coupon');

      if (!couponElement) {
        return;
      }

      var codeElement = couponElement.querySelector('.hashbar-popup-coupon-code');
      var copyButton = couponElement.querySelector('.hashbar-popup-coupon-copy');

      if (!codeElement) {
        return;
      }

      // Check for auto-copy setting (from popup data attribute or coupon element)
      var autoCopy = config.couponAutoCopy || couponElement.getAttribute('data-autocopy') === 'true';

      // Copy button click - must be set up first to stop propagation
      if (copyButton) {
        copyButton.addEventListener('click', function(e) {
          e.stopPropagation(); // Prevent triggering the coupon code click handler
          self.copyCouponCode(popup, copyButton);
        });
      }

      // Auto copy on click (for clicking on the coupon code area, not the button)
      if (autoCopy && codeElement) {
        codeElement.style.cursor = 'pointer';
        codeElement.classList.add('hashbar-autocopy');

        // Add "Click to copy" tooltip
        var clickToCopyTooltip = document.createElement('span');
        clickToCopyTooltip.className = 'hashbar-click-to-copy-tooltip';
        clickToCopyTooltip.textContent = config.couponClickToCopyText || 'Click to copy';
        codeElement.appendChild(clickToCopyTooltip);

        codeElement.addEventListener('click', function(e) {
          // Only trigger if not clicking on the copy button
          if (!e.target.closest('.hashbar-popup-coupon-copy')) {
            self.copyCouponCode(popup, codeElement);
          }
        });
      }
    },

    /**
     * Copy coupon code to clipboard
     */
    copyCouponCode: function(popup, element) {
      var self = this;
      var config = popup.config;
      var code = config.couponCode;

      if (!code) {
        return;
      }

      // Copy to clipboard
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(function() {
          self.showCopiedFeedback(element, config.couponCopiedText);
          self.trackEvent(popup, 'coupon_copy', code);
        }).catch(function() {
          self.fallbackCopy(code);
          self.showCopiedFeedback(element, config.couponCopiedText);
        });
      } else {
        this.fallbackCopy(code);
        this.showCopiedFeedback(element, config.couponCopiedText);
        this.trackEvent(popup, 'coupon_copy', code);
      }
    },

    /**
     * Show copied feedback
     */
    showCopiedFeedback: function(element, text) {
      var copiedText = text || 'Copied!';

      if (element.classList.contains('hashbar-popup-coupon-copy')) {
        // Get the text span inside the button
        var textSpan = element.querySelector('span');
        var originalText = textSpan ? textSpan.textContent : 'Copy';
        var btnCopiedText = element.getAttribute('data-copied-text') || copiedText;

        element.classList.add('hashbar-copied');
        if (textSpan) {
          textSpan.textContent = btnCopiedText;
        }

        setTimeout(function() {
          element.classList.remove('hashbar-copied');
          if (textSpan) {
            textSpan.textContent = originalText;
          }
        }, 2000);
      } else if (element.classList.contains('hashbar-popup-coupon-code')) {
        // For coupon code element, show a tooltip instead of replacing content
        element.classList.add('hashbar-copied');

        // Create or update tooltip
        var tooltip = element.querySelector('.hashbar-copied-tooltip');
        if (!tooltip) {
          tooltip = document.createElement('span');
          tooltip.className = 'hashbar-copied-tooltip';
          element.appendChild(tooltip);
        }
        tooltip.textContent = copiedText;
        tooltip.style.opacity = '1';
        tooltip.style.visibility = 'visible';

        setTimeout(function() {
          element.classList.remove('hashbar-copied');
          tooltip.style.opacity = '0';
          tooltip.style.visibility = 'hidden';
        }, 2000);
      } else {
        // Fallback for other elements
        var originalText = element.textContent;
        element.textContent = copiedText;
        setTimeout(function() {
          element.textContent = originalText;
        }, 2000);
      }
    },

    /**
     * Fallback copy for older browsers
     */
    fallbackCopy: function(text) {
      var textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);

      try {
        textarea.select();
        document.execCommand('copy');
      } finally {
        document.body.removeChild(textarea);
      }
    },

    /**
     * Initialize form functionality
     */
    initForm: function(popup) {
      var self = this;
      var form = popup.element.querySelector('.hashbar-popup-form');

      if (!form) {
        return;
      }

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        self.handleFormSubmit(popup, form);
      });

      // Real-time validation
      var inputs = form.querySelectorAll('input, textarea, select');
      inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
          self.validateField(input);
        });

        input.addEventListener('input', function() {
          // Clear error on input
          var field = input.closest('.hashbar-popup-form-field');
          if (field) {
            field.classList.remove('hashbar-field-error');
            var errorEl = field.querySelector('.hashbar-popup-form-error');
            if (errorEl) {
              errorEl.remove();
            }
          }
        });
      });
    },

    /**
     * Validate a single field
     */
    validateField: function(input) {
      var field = input.closest('.hashbar-popup-form-field');
      if (!field) return true;

      var isValid = true;
      var errorMessage = '';

      // Required validation
      if (input.hasAttribute('required') && !input.value.trim()) {
        isValid = false;
        errorMessage = 'This field is required';
      }

      // Email validation
      if (input.type === 'email' && input.value) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
          isValid = false;
          errorMessage = 'Please enter a valid email address';
        }
      }

      // Show/hide error
      var existingError = field.querySelector('.hashbar-popup-form-error');
      if (existingError) {
        existingError.remove();
      }

      if (!isValid) {
        field.classList.add('hashbar-field-error');
        var errorEl = document.createElement('div');
        errorEl.className = 'hashbar-popup-form-error';
        errorEl.textContent = errorMessage;
        field.appendChild(errorEl);
      } else {
        field.classList.remove('hashbar-field-error');
      }

      return isValid;
    },

    /**
     * Handle form submission
     */
    handleFormSubmit: function(popup, form) {
      var self = this;
      var submitButton = form.querySelector('.hashbar-popup-submit');

      // Validate all fields
      var inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
      var allValid = true;

      inputs.forEach(function(input) {
        if (!self.validateField(input)) {
          allValid = false;
        }
      });

      if (!allValid) {
        // Shake animation on error
        popup.element.querySelector('.hashbar-popup-container').classList.add('hashbar-popup-shake');
        setTimeout(function() {
          popup.element.querySelector('.hashbar-popup-container').classList.remove('hashbar-popup-shake');
        }, 500);
        return;
      }

      // Show loading state with custom submitting text
      if (submitButton) {
        submitButton.classList.add('hashbar-popup-btn-loading');
        submitButton.disabled = true;
        // Store original text and show submitting text from data attribute
        var submittingText = submitButton.getAttribute('data-submitting-text') || 'Submitting...';
        submitButton.setAttribute('data-original-text', submitButton.textContent);
        submitButton.textContent = submittingText;
      }

      // Collect form data
      var formData = new FormData(form);
      var formFields = {};
      formData.forEach(function(value, key) {
        formFields[key] = value;
      });

      // Build request data with form_data wrapper (required by PHP handler)
      var data = {
        popup_id: popup.id,
        form_data: formFields,
        page_url: window.location.href,
        page_id: window.HashbarPopupData && window.HashbarPopupData.pageId || 0,
        referrer: document.referrer || ''
      };

      // Add UTM parameters if present
      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('utm_source')) data.utm_source = urlParams.get('utm_source');
      if (urlParams.get('utm_medium')) data.utm_medium = urlParams.get('utm_medium');
      if (urlParams.get('utm_campaign')) data.utm_campaign = urlParams.get('utm_campaign');
      if (urlParams.get('utm_term')) data.utm_term = urlParams.get('utm_term');
      if (urlParams.get('utm_content')) data.utm_content = urlParams.get('utm_content');

      // Send to server
      this.submitFormData(popup, form, data, submitButton);
    },

    /**
     * Submit form data to server
     */
    submitFormData: function(popup, form, data, submitButton) {
      var self = this;
      var restUrl = (window.HashbarPopupData && window.HashbarPopupData.restUrl) || '/wp-json/hashbar/v1/';
      var nonce = (window.HashbarPopupData && window.HashbarPopupData.nonce) || '';

      fetch(restUrl + 'popup-campaigns/' + data.popup_id + '/submit', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce
        },
        body: JSON.stringify(data)
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(result) {
        if (submitButton) {
          submitButton.classList.remove('hashbar-popup-btn-loading');
          submitButton.disabled = false;
          // Restore original button text
          var originalText = submitButton.getAttribute('data-original-text');
          if (originalText) {
            submitButton.textContent = originalText;
          }
        }

        if (result.success) {
          self.handleFormSuccess(popup, form, result);
          self.trackEvent(popup, 'conversion', 'form_submit');
        } else {
          self.handleFormError(popup, form, result.message || 'An error occurred');
        }
      })
      .catch(function() {
        if (submitButton) {
          submitButton.classList.remove('hashbar-popup-btn-loading');
          submitButton.disabled = false;
          // Restore original button text
          var originalText = submitButton.getAttribute('data-original-text');
          if (originalText) {
            submitButton.textContent = originalText;
          }
        }
        self.handleFormError(popup, form, 'Network error. Please try again.');
      });
    },

    /**
     * Handle successful form submission
     */
    handleFormSuccess: function(popup, form, result) {
      var self = this;
      var container = popup.element.querySelector('.hashbar-popup-content');

      // Get success action from API response (message, redirect, close)
      var successAction = result.success_action || 'message';
      var closeDelay = (result.close_delay || 3) * 1000; // Convert to milliseconds

      // Handle redirect immediately if that's the action
      if (successAction === 'redirect' && result.redirect_url) {
        // Show success message with redirecting text
        if (container) {
          container.innerHTML = '<div class="hashbar-popup-form-success">' +
            '<div class="hashbar-popup-form-success-icon">&#10003;</div>' +
            '<div class="hashbar-popup-form-success-message">' + (result.message || 'Thank you!') + '</div>' +
            '<div class="hashbar-popup-form-redirecting">' + (result.redirecting_text || 'Redirecting...') + '</div>' +
            '</div>';
        }
        setTimeout(function() {
          window.location.href = result.redirect_url;
        }, 1500);
        return;
      }

      // Show success message for 'message' action
      if (container) {
        container.innerHTML = '<div class="hashbar-popup-form-success">' +
          '<div class="hashbar-popup-form-success-icon">&#10003;</div>' +
          '<div class="hashbar-popup-form-success-message">' + (result.message || 'Thank you!') + '</div>' +
          '</div>';
      }

      // Handle close action
      if (successAction === 'close') {
        setTimeout(function() {
          self.hidePopup(popup, 'convert');
        }, closeDelay);
      }
      // For 'message' action, keep popup open showing the success message
    },

    /**
     * Handle form error
     */
    handleFormError: function(popup, form, message) {
      // Show error above form
      var existingError = form.querySelector('.hashbar-popup-form-error-global');
      if (existingError) {
        existingError.remove();
      }

      var errorEl = document.createElement('div');
      errorEl.className = 'hashbar-popup-form-error hashbar-popup-form-error-global';
      errorEl.textContent = message;
      form.insertBefore(errorEl, form.firstChild);

      // Shake animation
      popup.element.querySelector('.hashbar-popup-container').classList.add('hashbar-popup-shake');
      setTimeout(function() {
        popup.element.querySelector('.hashbar-popup-container').classList.remove('hashbar-popup-shake');
      }, 500);
    },

    /**
     * Initialize external form plugins and success handlers
     */
    initExternalForms: function(popup) {
      var self = this;
      var element = popup.element;

      if (popup.externalFormsInitialized) {
        return;
      }
      popup.externalFormsInitialized = true;

      // Contact Form 7
      var cf7Form = element.querySelector('.wpcf7-form');
      if (cf7Form && typeof wpcf7 !== 'undefined' && !cf7Form.wpcf7Initialized) {
        try {
          if (typeof wpcf7.init === 'function') {
            wpcf7.init(cf7Form);
            cf7Form.wpcf7Initialized = true;
          } else if (typeof wpcf7.initForm === 'function') {
            wpcf7.initForm(cf7Form);
            cf7Form.wpcf7Initialized = true;
          }
        } catch (e) {}
      }

      // WPForms
      var wpForm = element.querySelector('.wpforms-form');
      if (wpForm && typeof wpforms !== 'undefined' && !wpForm.wpformsInitialized) {
        try {
          if (typeof wpforms.init === 'function') {
            wpforms.init();
            wpForm.wpformsInitialized = true;
          } else if (typeof wpforms.ready === 'function') {
            wpforms.ready();
            wpForm.wpformsInitialized = true;
          }
        } catch (e) {}
      }

      self.initExternalFormHandlers(popup);
    },

    /**
     * Initialize handlers for external form success events
     */
    initExternalFormHandlers: function(popup) {
      var self = this;
      var element = popup.element;
      var closeDelay = popup.config.formCloseDelay || 3;

      // HT Contact Form
      var htForm = element.querySelector('.ht-form');
      if (htForm) {
        htForm.addEventListener('submit', function() {
          var attempts = 0;
          var checkInterval = setInterval(function() {
            attempts++;
            var successMsg = element.querySelector('.ht-form-success');
            if (successMsg) {
              clearInterval(checkInterval);
              setTimeout(function() {
                self.hidePopup(popup, 'convert');
              }, closeDelay * 1000);
            }
            if (attempts > 100) clearInterval(checkInterval);
          }, 100);
        });
      }

      // Contact Form 7
      var cf7Form = element.querySelector('.wpcf7-form');
      if (cf7Form) {
        cf7Form.addEventListener('wpcf7mailsent', function() {
          setTimeout(function() {
            self.hidePopup(popup, 'convert');
          }, closeDelay * 1000);
        });
      }

      // WPForms
      var wpForm = element.querySelector('.wpforms-form');
      if (wpForm && typeof jQuery !== 'undefined') {
        jQuery(wpForm).on('wpformsAjaxSubmitSuccess', function() {
          setTimeout(function() {
            self.hidePopup(popup, 'convert');
          }, closeDelay * 1000);
        });
      }

      // Fluent Forms
      var fluentForm = element.querySelector('.frm-fluent-form');
      if (fluentForm && typeof jQuery !== 'undefined') {
        jQuery(fluentForm).on('fluentform_submission_success', function() {
          setTimeout(function() {
            self.hidePopup(popup, 'convert');
          }, closeDelay * 1000);
        });
      }

      // Gravity Forms
      if (typeof jQuery !== 'undefined') {
        jQuery(document).on('gform_confirmation_loaded', function(e, formId) {
          var gfForm = element.querySelector('#gform_' + formId);
          if (gfForm) {
            setTimeout(function() {
              self.hidePopup(popup, 'convert');
            }, closeDelay * 1000);
          }
        });
      }
    },

    /**
     * Analytics event queue for batch sending
     */
    analyticsQueue: [],
    analyticsTimer: null,
    ANALYTICS_BATCH_DELAY: 2000, // 2 seconds

    /**
     * Get or generate session ID for analytics
     */
    getSessionId: function() {
      var sessionKey = 'hashbar_analytics_session_id';
      var sessionId = sessionStorage.getItem(sessionKey);
      if (!sessionId) {
        // Generate UUID v4
        sessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
          var r = Math.random() * 16 | 0;
          var v = c === 'x' ? r : (r & 0x3 | 0x8);
          return v.toString(16);
        });
        sessionStorage.setItem(sessionKey, sessionId);
      }
      return sessionId;
    },

    /**
     * Track event for analytics
     */
    trackEvent: function(popup, eventType, eventValue) {
      var self = this;

      // Prevent duplicate tracking within the same page load (same approach as announcement bar)
      var trackingKey = 'hashbar_popup_tracked_' + popup.id + '_' + eventType + (eventValue ? '_' + eventValue : '');
      if (sessionStorage.getItem(trackingKey) === 'true') {
        return; // Already tracked in this page view
      }
      sessionStorage.setItem(trackingKey, 'true');

      // Build event data (using campaign_id as expected by backend)
      var eventData = {
        campaign_id: parseInt(popup.id, 10),
        event_type: eventType,
        session_id: this.getSessionId(),
        timestamp: Date.now(),
        page_url: window.location.href,
        page_id: (window.HashbarPopupData && window.HashbarPopupData.pageId) || 0,
        referrer_url: document.referrer || '',
        user_agent: navigator.userAgent,
        screen_width: window.innerWidth,
        screen_height: window.innerHeight,
        device_type: this.getDeviceType()
      };

      if (eventValue) {
        eventData.event_value = eventValue;
      }

      // Add variant ID if A/B testing is active
      var variantId = this.getAssignedVariant(popup);
      if (variantId) {
        eventData.variant_id = variantId;
      }

      // Add to queue
      this.analyticsQueue.push(eventData);

      // Clear existing timer
      if (this.analyticsTimer) {
        clearTimeout(this.analyticsTimer);
      }

      // Set timer to batch send
      this.analyticsTimer = setTimeout(function() {
        self.sendAnalyticsBatch();
      }, this.ANALYTICS_BATCH_DELAY);

      // Also send on page unload
      if (!this.unloadListenerAttached) {
        this.attachUnloadListener();
      }
    },

    /**
     * Get device type for analytics
     */
    getDeviceType: function() {
      var width = window.innerWidth;
      if (width < 768) return 'mobile';
      if (width < 1024) return 'tablet';
      return 'desktop';
    },

    /**
     * Get assigned A/B test variant for popup
     */
    getAssignedVariant: function(popup) {
      // First check the data attribute set by PHP (most reliable)
      var variantId = popup.element.getAttribute('data-variant-id');
      if (variantId) {
        return variantId;
      }

      // Fall back to cookie
      var cookieName = 'hashbar_popup_ab_' + popup.id;
      var cookieVariant = this.getCookie(cookieName);
      if (cookieVariant) {
        return cookieVariant;
      }

      // Fall back to localStorage
      var storageKey = 'hashbar_popup_variant_' + popup.id;
      return localStorage.getItem(storageKey) || null;
    },

    /**
     * Set assigned A/B test variant for popup
     */
    setAssignedVariant: function(popup, variantId) {
      var storageKey = 'hashbar_popup_variant_' + popup.id;
      localStorage.setItem(storageKey, variantId);
    },

    /**
     * Flag to prevent duplicate batch sends
     */
    isSendingBatch: false,

    /**
     * Send analytics batch to server
     */
    sendAnalyticsBatch: function() {
      // Prevent duplicate sends (race condition between timer, visibilitychange, beforeunload)
      if (this.analyticsQueue.length === 0 || this.isSendingBatch) {
        return;
      }

      this.isSendingBatch = true;

      var restUrl = (window.HashbarPopupData && window.HashbarPopupData.restUrl) || '/wp-json/hashbar/v1/';
      var events = this.analyticsQueue.slice(); // Copy queue
      this.analyticsQueue = []; // Clear queue

      var payload = {
        events: events
      };

      var self = this;

      // Use beacon API for better reliability (especially on page unload)
      if (navigator.sendBeacon) {
        var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        navigator.sendBeacon(restUrl + 'popup-analytics/batch', blob);
        // Reset flag after a short delay for beacon
        setTimeout(function() { self.isSendingBatch = false; }, 100);
      } else {
        fetch(restUrl + 'popup-analytics/batch', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload),
          keepalive: true
        }).then(function() {
          self.isSendingBatch = false;
        }).catch(function() {
          self.isSendingBatch = false;
        });
      }
    },

    /**
     * Attach unload listener for sending remaining events
     */
    unloadListenerAttached: false,
    attachUnloadListener: function() {
      var self = this;
      this.unloadListenerAttached = true;

      window.addEventListener('beforeunload', function() {
        self.sendAnalyticsBatch();
      });

      // Also handle visibility change (mobile)
      document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
          self.sendAnalyticsBatch();
        }
      });
    },

    /**
     * Track button click
     */
    trackButtonClick: function(popup, buttonType) {
      this.trackEvent(popup, 'click', buttonType || 'button');
    },

    /**
     * Cookie utilities
     */
    setCookie: function(name, value, days) {
      var expires = '';
      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
      }
      document.cookie = name + '=' + (value || '') + expires + '; path=/';
    },

    getCookie: function(name) {
      var nameEQ = name + '=';
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();
        if (cookie.indexOf(nameEQ) === 0) {
          return cookie.substring(nameEQ.length);
        }
      }
      return null;
    },

    /**
     * Utility functions
     */
    padZero: function(num) {
      return (num < 10 ? '0' : '') + num;
    },

    escapeHtml: function(text) {
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    },

    isMobile: function() {
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
  };

  /**
   * Initialize on DOM ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      PopupCampaignManager.init();
    });
  } else {
    PopupCampaignManager.init();
  }

  // Expose for external use
  window.HashbarPopupCampaign = PopupCampaignManager;

})();
