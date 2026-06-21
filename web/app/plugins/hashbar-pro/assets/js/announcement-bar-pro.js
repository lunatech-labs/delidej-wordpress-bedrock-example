/**
 * Announcement Bar Frontend JavaScript
 *
 * Handles close buttons, reopening, countdown timers, coupon copying,
 * and other interactive features for announcement bars.
 *
 * DEBUG: Set window.HASHBAR_DEBUG = true in browser console to enable debug logging
 */

(function() {
  'use strict';

  // Prevent double initialization if multiple scripts are loaded
  if (window.HASHBAR_ANNOUNCEMENT_INITIALIZED) {
    return;
  }
  window.HASHBAR_ANNOUNCEMENT_INITIALIZED = true;

  // Enable debug mode if URL has debug parameter or localStorage flag
  if (typeof window.HASHBAR_DEBUG === 'undefined') {
    window.HASHBAR_DEBUG =
      new URLSearchParams(window.location.search).has('hashbar_debug') ||
      localStorage.getItem('hashbar_debug') === 'true';
  }

  /**
   * Initialize announcement bars
   */
  document.addEventListener('DOMContentLoaded', function() {
    initializeAnnouncementBars();
  });

  /**
   * Check if bar should be displayed based on visitor timezone schedule
   * This handles start/end dates, time targeting and recurring days when timezone is set to 'visitor'
   *
   * @param {HTMLElement} bar The announcement bar element
   * @returns {boolean} True if bar should be displayed, false otherwise
   */
  function checkVisitorTimezoneSchedule(bar) {
    const scheduleEnabled = bar.getAttribute('data-schedule-enabled') === 'true';
    const scheduleTimezone = bar.getAttribute('data-schedule-timezone');

    // If schedule is not enabled, always display
    if (!scheduleEnabled) {
      return true;
    }

    // Only apply visitor timezone check when timezone is 'visitor'
    // 'site' timezone checks are handled server-side
    if (scheduleTimezone !== 'visitor') {
      return true;
    }

    const now = new Date();
    const currentDay = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();
    const currentTimeMinutes = currentHours * 60 + currentMinutes;

    // Check start/end date range (for visitor timezone)
    const scheduleStart = bar.getAttribute('data-schedule-start');
    const scheduleEnd = bar.getAttribute('data-schedule-end');

    // If start date is set, check if we've reached it
    if (scheduleStart) {
      // Parse the datetime-local format (YYYY-MM-DDTHH:MM)
      const startParts = scheduleStart.split('T');
      const startDateParts = startParts[0].split('-');
      const startTimeParts = (startParts[1] || '00:00').split(':');

      const startDate = new Date(
        parseInt(startDateParts[0], 10),
        parseInt(startDateParts[1], 10) - 1,
        parseInt(startDateParts[2], 10),
        parseInt(startTimeParts[0], 10),
        parseInt(startTimeParts[1] || 0, 10),
        0, 0
      );

      if (now < startDate) {
        return false; // Start date not reached yet
      }
    }

    // If end date is set, check if it has passed
    if (scheduleEnd) {
      // Parse the datetime-local format (YYYY-MM-DDTHH:MM)
      const endParts = scheduleEnd.split('T');
      const endDateParts = endParts[0].split('-');
      const endTimeParts = (endParts[1] || '23:59').split(':');

      const endDate = new Date(
        parseInt(endDateParts[0], 10),
        parseInt(endDateParts[1], 10) - 1,
        parseInt(endDateParts[2], 10),
        parseInt(endTimeParts[0], 10),
        parseInt(endTimeParts[1] || 59, 10),
        0, 0
      );

      if (now > endDate) {
        return false; // End date has passed
      }
    }

    // Check recurring days (if enabled)
    const scheduleRecurring = bar.getAttribute('data-schedule-recurring') === 'true';
    if (scheduleRecurring) {
      let recurringDays = [];
      try {
        recurringDays = JSON.parse(bar.getAttribute('data-schedule-recurring-days') || '[]');
      } catch (e) {
        recurringDays = [];
      }

      // If recurring days are set, check if today is an active day
      if (recurringDays.length > 0) {
        // Convert day names to day numbers for comparison
        const dayNameToNumber = {
          'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3,
          'thursday': 4, 'friday': 5, 'saturday': 6
        };

        const activeDayNumbers = recurringDays.map(function(day) {
          return typeof day === 'string' ? dayNameToNumber[day.toLowerCase()] : day;
        }).filter(function(num) {
          return num !== undefined;
        });

        if (activeDayNumbers.indexOf(currentDay) === -1) {
          return false; // Today is not an active day
        }
      }
    }

    // Check time targeting (if enabled)
    const timeTargeting = bar.getAttribute('data-schedule-time-targeting') === 'true';
    if (timeTargeting) {
      const timeStart = bar.getAttribute('data-schedule-time-start') || '00:00';
      const timeEnd = bar.getAttribute('data-schedule-time-end') || '23:59';

      // Parse start and end times
      const startParts = timeStart.split(':');
      const endParts = timeEnd.split(':');
      const startMinutes = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1] || 0, 10);
      const endMinutes = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1] || 0, 10);

      // Check if current time is within the allowed range
      if (endMinutes >= startMinutes) {
        // Normal range (e.g., 09:00 to 17:00)
        if (currentTimeMinutes < startMinutes || currentTimeMinutes > endMinutes) {
          return false;
        }
      } else {
        // Overnight range (e.g., 22:00 to 06:00)
        if (currentTimeMinutes < startMinutes && currentTimeMinutes > endMinutes) {
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Main initialization function
   */
  function initializeAnnouncementBars() {
    const bars = document.querySelectorAll('.hashbar-announcement-bar');

    if (bars.length === 0) {
      return;
    }

    bars.forEach(function(bar) {
      const barId = bar.getAttribute('data-bar-id');

      // Check visitor timezone schedule before showing bar
      if (!checkVisitorTimezoneSchedule(bar)) {
        bar.style.display = 'none';
        return; // Don't initialize this bar - it shouldn't show based on visitor timezone schedule
      }

      // Initialize CTA button with first message content
      initializeCTAButton(bar, barId);

      // Initialize time-on-site behavioral targeting
      if (bar.getAttribute('data-time-on-site-enabled') === 'true') {
        initializeTimeOnSiteTargeting(bar, barId);
      } else {
        // If time-on-site targeting is not enabled, show bar immediately
        showBar(bar);
      }

      // Initialize close button
      initializeCloseButton(bar, barId);

      // Initialize reopen button
      initializeReopenButton(bar, barId);

      // Initialize countdown timer
      if (bar.getAttribute('data-countdown-enabled') === 'true') {
        initializeCountdown(bar, barId);
      }

      // Initialize coupon copy
      initializeCouponCopy(bar, barId);

      // Initialize message rotation
      if (bar.getAttribute('data-message-rotation-enabled') === 'true') {
        initializeMessageRotation(bar, barId);
      }

      // Initialize exit animation
      setupExitAnimation(bar);

      // Track A/B test variant (impression tracking happens when bar is displayed)
      if (bar.getAttribute('data-ab-test-enabled') === 'true') {
        // Delay tracking impression until bar is actually visible
        setTimeout(function() {
          trackABTestVariant(bar, barId);
        }, 100);
      }
    });
  }

  /**
   * Initialize time-on-site behavioral targeting
   *
   * This function tracks the elapsed time a visitor has spent on the page
   * and shows the announcement bar only after the configured minimum time threshold is reached.
   */
  function initializeTimeOnSiteTargeting(bar, barId) {
    const minimumTimeSeconds = parseInt(bar.getAttribute('data-minimum-time-on-site'), 10) || 0;

    // If minimum time is 0 or not set, show immediately
    if (minimumTimeSeconds <= 0) {
      showBar(bar);
      return;
    }

    // Get or create session start time
    const sessionStartKey = 'hashbar_session_start_' + barId;
    let sessionStart = sessionStorage.getItem(sessionStartKey);

    if (!sessionStart) {
      // Store current time as session start
      sessionStart = Date.now();
      sessionStorage.setItem(sessionStartKey, sessionStart);
    }

    // Check if we've already shown this bar
    const barShownKey = 'hashbar_shown_' + barId;
    const barAlreadyShown = sessionStorage.getItem(barShownKey);

    if (barAlreadyShown === 'true') {
      // Bar was already shown in this session, show it immediately
      showBar(bar);
      return;
    }

    // Calculate elapsed time
    const sessionStart_ms = parseInt(sessionStart, 10);
    const elapsedSeconds = Math.floor((Date.now() - sessionStart_ms) / 1000);


    if (elapsedSeconds >= minimumTimeSeconds) {
      // Minimum time has been reached, show the bar
      showBar(bar);
      sessionStorage.setItem(barShownKey, 'true');
    } else {
      // Hide the bar initially, will show when time threshold is reached
      bar.style.display = 'none';

      // Calculate time remaining until bar should be shown
      const timeRemaining = minimumTimeSeconds - elapsedSeconds;

      // Set timeout to show bar after remaining time elapses
      setTimeout(function() {
        showBar(bar);
        sessionStorage.setItem(barShownKey, 'true');
      }, timeRemaining * 1000);
    }
  }

  /**
   * Initialize close button functionality
   */
  function initializeCloseButton(bar, barId) {
    const closeButtons = bar.querySelectorAll('.hashbar-announcement-close');

    closeButtons.forEach(function(button) {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        closeAnnouncement(bar, barId);
      });
    });
  }

  /**
   * Close announcement bar with animation and smooth height collapse
   */
  function closeAnnouncement(bar, barId) {
    var exitAnimation = bar.getAttribute('data-animation-exit');
    var animationDuration = parseInt(bar.getAttribute('data-animation-duration'), 10) || 500;
    var wrapper = bar.closest('.hashbar-announcement-bar-wrapper') || bar;

    var sticky = isBarSticky(bar);
    var durSec = animationDuration / 1000;

    if (sticky) {
      // For top-sticky (position:sticky in PHP) the bar is in document flow, so shrinking its
      // own height reflows the page and the header rises in perfect lockstep — a single
      // browser-driven motion. For bottom-sticky (position:fixed) we additionally shrink the
      // body padding-bottom spacer with matching duration + easing.
      // PHP renders inline min-height + padding; both must be overridden so height:0 actually
      // takes effect, otherwise the bar stays visible while everything around it animates.
      beginStickySpacerCollapse(bar, animationDuration, 'ease-in');
      var currentHeight = bar.offsetHeight;
      bar.style.overflow = 'hidden';
      bar.style.minHeight = '0';
      bar.style.height = currentHeight + 'px';
      bar.offsetHeight; // force reflow
      bar.style.transition = 'height ' + durSec + 's ease-in, opacity ' + durSec + 's ease-in, padding ' + durSec + 's ease-in';
      bar.style.height = '0px';
      bar.style.paddingTop = '0';
      bar.style.paddingBottom = '0';
      bar.style.opacity = '0';
      if (exitAnimation && exitAnimation !== 'none') {
        // Layered visual effect (e.g. slideUp) on top of the height collapse.
        bar.style.animation = exitAnimation + ' ' + durSec + 's ease-in forwards';
      }
    } else {
      // Non-sticky: bar is in flow inside the wrapper, so collapse wrapper height as before.
      wrapper.style.overflow = 'hidden';
      wrapper.style.height = wrapper.offsetHeight + 'px';
      wrapper.offsetHeight; // force reflow
      wrapper.style.transition = 'height ' + durSec + 's ease';
      wrapper.style.height = '0px';

      if (exitAnimation && exitAnimation !== 'none') {
        bar.style.animation = exitAnimation + ' ' + durSec + 's ease-in forwards';
      } else {
        bar.style.transition = 'opacity ' + durSec + 's ease';
        bar.style.opacity = '0';
      }
    }

    // After animation completes, hide bar and set cookie
    setTimeout(function() {
      bar.style.display = 'none';
      removeStickySpacer(bar);

      var cookieDays = parseFloat(bar.getAttribute('data-cookie-duration'));

      // -1 means "show on reload" - don't set any cookie, just hide the bar
      if (cookieDays === -1) {
        var reopenBtnReload = document.querySelector('.hashbar-reopen-button[data-bar-id="' + barId + '"]');
        if (reopenBtnReload) {
          reopenBtnReload.style.display = 'block';
        }
        return;
      }

      // Store close timestamp so PHP can compare against current setting
      var closeTimestamp = Date.now().toString();

      // 0 means "session only" - set session cookie (no expiration)
      if (cookieDays === 0) {
        setCookie('hashbar_announcement_closed_' + barId, closeTimestamp, null);
        var reopenBtn0 = document.querySelector('.hashbar-reopen-button[data-bar-id="' + barId + '"]');
        if (reopenBtn0) {
          reopenBtn0.style.display = 'block';
        }
        return;
      }

      // Set cookie with 1-year expiry - PHP handles the real duration check
      setCookie('hashbar_announcement_closed_' + barId, closeTimestamp, 365);

      // Show reopen button if enabled
      var reopenButton = document.querySelector('.hashbar-reopen-button[data-bar-id="' + barId + '"]');
      if (reopenButton) {
        reopenButton.style.display = 'block';
      }
    }, animationDuration);
  }

  /**
   * Show bar - entry animation is handled by PHP inline styles
   */
  function showBar(bar) {
    bar.style.display = 'flex';
    applyStickySpacer(bar);
  }

  // Registry of sticky bar heights by position, keyed by bar id.
  // Lets us push body content down by the combined sticky-bar height,
  // so a position:fixed bar never overlaps the site header on first paint.
  var hashbarStickyRegistry = { top: {}, bottom: {} };
  var hashbarStickyObservers = {};

  function isBarSticky(bar) {
    var v = bar.getAttribute('data-sticky');
    return v === '1' || v === 'true';
  }

  function getBarPosition(bar) {
    return bar.getAttribute('data-position') === 'bottom' ? 'bottom' : 'top';
  }

  // Body-padding spacer is only needed for bottom-sticky bars (position:fixed, out of flow).
  // Top-sticky bars use position:sticky in PHP, which keeps them in flow — the document layout
  // already reserves their space, so no manual spacer is required.
  function needsBodySpacer(bar) {
    return isBarSticky(bar) && getBarPosition(bar) === 'bottom';
  }

  function applyStickySpacer(bar) {
    if (!bar || !needsBodySpacer(bar)) return;
    var barId = bar.getAttribute('data-bar-id');
    if (!barId) return;
    var position = getBarPosition(bar);
    var height = bar.offsetHeight;
    if (!height) return;
    hashbarStickyRegistry[position][barId] = height;
    updateBodySpacer(position);

    if (!hashbarStickyObservers[barId] && typeof ResizeObserver !== 'undefined') {
      var ro = new ResizeObserver(function() {
        if (bar.style.display === 'none') return;
        hashbarStickyRegistry[position][barId] = bar.offsetHeight;
        updateBodySpacer(position);
      });
      ro.observe(bar);
      hashbarStickyObservers[barId] = ro;
    }
  }

  // Called the moment the close button is clicked. Mirrors the non-sticky wrapper-collapse
  // pattern: pin the current computed padding inline, force a reflow, then transition to the
  // new (post-removal) total. The PHP-emitted pre-paint <style> is dropped first so it can't
  // override the inline transition target back up to its baked-in value. Easing must match the
  // bar's own collapse easing or the two motions visually finish at different times.
  function beginStickySpacerCollapse(bar, durationMs, easing) {
    if (!bar || !needsBodySpacer(bar)) return;
    var barId = bar.getAttribute('data-bar-id');
    if (!barId) return;
    var position = getBarPosition(bar);
    if (!(barId in hashbarStickyRegistry[position])) return;
    delete hashbarStickyRegistry[position][barId];
    if (hashbarStickyObservers[barId]) {
      hashbarStickyObservers[barId].disconnect();
      delete hashbarStickyObservers[barId];
    }
    var preStyle = document.getElementById('hashbar-pre-spacer-' + barId);
    if (preStyle && preStyle.parentNode) preStyle.parentNode.removeChild(preStyle);
    var prop = position === 'bottom' ? 'paddingBottom' : 'paddingTop';
    var transProp = position === 'bottom' ? 'padding-bottom' : 'padding-top';
    var total = 0, reg = hashbarStickyRegistry[position];
    for (var k in reg) total += reg[k];
    var current = parseFloat(window.getComputedStyle(document.body)[prop]) || 0;
    document.body.style.transition = '';
    document.body.style[prop] = current + 'px';
    document.body.offsetHeight; // force reflow before applying transition
    document.body.style.transition = transProp + ' ' + (durationMs / 1000) + 's ' + (easing || 'ease');
    document.body.style[prop] = total + 'px';
  }

  function removeStickySpacer(bar) {
    if (!bar) return;
    var barId = bar.getAttribute('data-bar-id');
    if (!barId) return;
    var position = getBarPosition(bar);
    delete hashbarStickyRegistry[position][barId];
    if (hashbarStickyObservers[barId]) {
      hashbarStickyObservers[barId].disconnect();
      delete hashbarStickyObservers[barId];
    }
    var preStyle = document.getElementById('hashbar-pre-spacer-' + barId);
    if (preStyle && preStyle.parentNode) preStyle.parentNode.removeChild(preStyle);
    document.body.style.transition = '';
    updateBodySpacer(position);
  }

  function updateBodySpacer(position) {
    var prop = position === 'bottom' ? 'paddingBottom' : 'paddingTop';
    var total = 0;
    var heights = hashbarStickyRegistry[position];
    for (var k in heights) {
      if (Object.prototype.hasOwnProperty.call(heights, k)) total += heights[k];
    }
    if (total > 0) {
      document.body.style[prop] = total + 'px';
    } else {
      document.body.style[prop] = '';
    }
  }

  window.addEventListener('resize', function() {
    document.querySelectorAll('.hashbar-announcement-bar').forEach(function(bar) {
      if (bar.style.display === 'none') return;
      applyStickySpacer(bar);
    });
  });

  /**
   * Initialize reopen button functionality
   */
  function initializeReopenButton(bar, barId) {
    const reopenButton = document.querySelector('.hashbar-reopen-button[data-bar-id="' + barId + '"]');

    if (!reopenButton) {
      return;
    }

    const reopenBtn = reopenButton.querySelector('.hashbar-reopen-btn');

    if (reopenBtn) {
      reopenBtn.addEventListener('click', function(e) {
        e.preventDefault();
        reopenAnnouncement(bar, barId, reopenButton);
      });
    }
  }

  /**
   * Reopen a closed announcement bar
   */
  function reopenAnnouncement(bar, barId, reopenButton) {
    // Clear the closed cookie
    setCookie('hashbar_announcement_closed_' + barId, '', -1);

    // Reset bar and wrapper for re-display
    var wrapper = bar.closest('.hashbar-announcement-bar-wrapper') || bar;
    wrapper.style.overflow = '';
    wrapper.style.height = '';
    wrapper.style.transition = '';
    bar.style.display = 'flex';
    bar.style.opacity = '1';
    bar.style.animation = 'none';

    // Show bar with entry animation
    showBar(bar);

    // Hide reopen button
    reopenButton.style.display = 'none';
  }

  /**
   * Initialize countdown timer - handles fixed, recurring, and evergreen types
   */
  function initializeCountdown(bar, barId) {
    // Prevent multiple initializations on the same bar
    if (bar.hasAttribute('data-countdown-initialized')) {
      return;
    }
    bar.setAttribute('data-countdown-initialized', 'true');

    const countdownType = bar.getAttribute('data-countdown-type');
    const countdownDate = bar.getAttribute('data-countdown-date');
    const timezoneStr = bar.getAttribute('data-countdown-timezone');

    // Track countdown view for A/B testing (before any early returns)
    if (bar.getAttribute('data-ab-test-enabled') === 'true') {
      const abTestVariantId = bar.getAttribute('data-ab-test-variant-id');
      trackABTestEvent(barId, abTestVariantId, 'countdown_view');
    }

    // Find the bar wrapper
    const barWrapper = bar.parentElement && bar.parentElement.classList.contains('hashbar-announcement-bar-wrapper')
      ? bar.parentElement
      : bar;

    // Query all countdown timers
    const timerElements = barWrapper.querySelectorAll('.hashbar-countdown-timer-wrapper, .hashbar-countdown-timer-text');

    if (timerElements.length === 0) {
      return;
    }

    // Handle FIXED type
    if (countdownType === 'fixed') {
      if (!countdownDate) return;

      // Build timer configs with display options for each timer element
      const timerConfigs = [];
      timerElements.forEach(function(timerElement) {
        timerConfigs.push({
          element: timerElement,
          style: timerElement.getAttribute('data-countdown-style') || 'simple',
          showDays: timerElement.getAttribute('data-show-days') !== 'false',
          showHours: timerElement.getAttribute('data-show-hours') !== 'false',
          showMinutes: timerElement.getAttribute('data-show-minutes') !== 'false',
          showSeconds: timerElement.getAttribute('data-show-seconds') !== 'false',
          lastDisplayState: null
        });
      });

      function updateFixedIfChanged() {
        const timezone = timezoneStr || 'site';
        const timezoneOffset = getTimezoneOffset(timezone);
        const now = new Date();
        const utcMs = now.getTime();

        // Parse the countdown date
        const endDateParts = countdownDate.split('T')[0].split('-');
        const endYear = parseInt(endDateParts[0], 10);
        const endMonth = parseInt(endDateParts[1], 10) - 1;
        const endDate = parseInt(endDateParts[2], 10);

        const timeParts = countdownDate.split('T')[1] || '00:00:00';
        const [endHours, endMinutes] = timeParts.split(':').map(Number);

        let remaining;

        if (timezone === 'visitor') {
          // Selected end time only has minute resolution; expire at end of that minute (HH:MM:59.999).
          const endTimeLocal = new Date(endYear, endMonth, endDate, endHours, endMinutes, 59, 999);
          remaining = endTimeLocal.getTime() - utcMs;
        } else {
          // For site timezone or other timezones, calculate with offset
          const targetOffsetMs = timezoneOffset * 60 * 60 * 1000;
          const endTimeUtc = new Date(Date.UTC(endYear, endMonth, endDate, endHours, endMinutes, 59, 999));
          // The actual UTC time is: input time minus the timezone offset
          const actualEndTimeUtc = endTimeUtc.getTime() - targetOffsetMs;
          remaining = actualEndTimeUtc - utcMs;
        }

        // Update each timer element with its own display options
        timerConfigs.forEach(function(config) {
          updateCountdownOptimized(config, remaining, countdownType);
        });
      }

      // Delay first update by 100ms to ensure all data is fully initialized
      // This prevents flash of wrong values (like "Expired") on initial load
      setTimeout(function() {
        updateFixedIfChanged();
        setInterval(updateFixedIfChanged, 1000);
      }, 100);
    } else if (countdownType === 'recurring') {
      // Recurring countdown - resets at specific time each day
      const resetTime = bar.getAttribute('data-countdown-reset-time') || '00:00';
      const resetDaysStr = bar.getAttribute('data-countdown-reset-days') || '[]';
      let resetDays = [];
      try {
        resetDays = JSON.parse(resetDaysStr);
      } catch (e) {
        resetDays = [];
      }

      const timerConfigs = [];
      timerElements.forEach(function(timerElement) {
        timerConfigs.push({
          element: timerElement,
          style: timerElement.getAttribute('data-countdown-style') || 'simple',
          showDays: timerElement.getAttribute('data-show-days') !== 'false',
          showHours: timerElement.getAttribute('data-show-hours') !== 'false',
          showMinutes: timerElement.getAttribute('data-show-minutes') !== 'false',
          showSeconds: timerElement.getAttribute('data-show-seconds') !== 'false',
          lastDisplayState: null
        });
      });

      function updateRecurringTimers() {
        const now = new Date().getTime();
        const timezone = timezoneStr || 'site';
        const timezoneOffset = getTimezoneOffset(timezone);

        // If no active days set, show 0
        if (resetDays.length === 0) {
          timerConfigs.forEach(function(config) {
            updateCountdownOptimized(config, 0, countdownType);
          });
          return;
        }

        // Parse reset time
        const [resetHours, resetMinutes] = resetTime.split(':').map(Number);

        let distance;

        if (timezone === 'visitor') {
          // For visitor timezone, work directly in local time
          const nowLocal = new Date();
          let nextReset = new Date(nowLocal);
          nextReset.setHours(resetHours, resetMinutes, 0, 0);

          // If reset time has already passed today, move to next day
          if (nextReset <= nowLocal) {
            nextReset.setDate(nextReset.getDate() + 1);
          }

          // Check if next reset is on an active day
          let daysToCheck = 0;
          while (daysToCheck < 7 && resetDays.indexOf(nextReset.getDay()) === -1) {
            nextReset.setDate(nextReset.getDate() + 1);
            daysToCheck++;
          }

          distance = nextReset.getTime() - now;
        } else {
          // For site timezone, work in UTC and apply offset
          const targetOffsetMs = timezoneOffset * 60 * 60 * 1000;

          // Get current UTC time
          const nowUtc = now;
          // Calculate current time in site timezone (as UTC timestamp)
          const nowInSiteTz = nowUtc + targetOffsetMs;
          const nowInSiteTzDate = new Date(nowInSiteTz);

          // Create reset time for today in site timezone (using UTC methods to avoid local timezone interference)
          let nextResetUtc = Date.UTC(
            nowInSiteTzDate.getUTCFullYear(),
            nowInSiteTzDate.getUTCMonth(),
            nowInSiteTzDate.getUTCDate(),
            resetHours,
            resetMinutes,
            0, 0
          );

          // Convert reset time from site timezone to UTC
          nextResetUtc = nextResetUtc - targetOffsetMs;

          // If reset time has already passed, move to next day
          if (nextResetUtc <= nowUtc) {
            nextResetUtc += 24 * 60 * 60 * 1000; // Add 24 hours
          }

          // Check if next reset is on an active day (in site timezone)
          let nextResetDate = new Date(nextResetUtc + targetOffsetMs);
          let daysToCheck = 0;
          while (daysToCheck < 7 && resetDays.indexOf(nextResetDate.getUTCDay()) === -1) {
            nextResetUtc += 24 * 60 * 60 * 1000; // Add 24 hours
            nextResetDate = new Date(nextResetUtc + targetOffsetMs);
            daysToCheck++;
          }

          distance = nextResetUtc - nowUtc;
        }

        timerConfigs.forEach(function(config) {
          updateCountdownOptimized(config, distance, countdownType);
        });
      }

      // Delay first update by 100ms to ensure all data is fully initialized
      setTimeout(function() {
        updateRecurringTimers();
        setInterval(updateRecurringTimers, 1000);
      }, 100);
    } else if (countdownType === 'evergreen') {
      // Evergreen countdown - each visitor sees their own timer
      const duration = parseInt(bar.getAttribute('data-countdown-duration'), 10) || 24;

      const timerConfigs = [];
      timerElements.forEach(function(timerElement) {
        timerConfigs.push({
          element: timerElement,
          style: timerElement.getAttribute('data-countdown-style') || 'simple',
          showDays: timerElement.getAttribute('data-show-days') !== 'false',
          showHours: timerElement.getAttribute('data-show-hours') !== 'false',
          showMinutes: timerElement.getAttribute('data-show-minutes') !== 'false',
          showSeconds: timerElement.getAttribute('data-show-seconds') !== 'false',
          lastDisplayState: null,
          sessionStartTime: null  // Track when timer started for this session
        });
      });

      function updateEvergreenTimers() {
        const now = new Date().getTime();
        const durationMs = duration * 60 * 60 * 1000;

        timerConfigs.forEach(function(config) {
          // Initialize session start time on first call
          if (config.sessionStartTime === null) {
            config.sessionStartTime = now;
          }

          // Calculate time remaining from session start
          const elapsed = now - config.sessionStartTime;
          const distance = durationMs - elapsed;

          updateCountdownOptimized(config, distance, countdownType);
        });
      }

      // Delay first update by 100ms to ensure all data is fully initialized
      setTimeout(function() {
        updateEvergreenTimers();
        setInterval(updateEvergreenTimers, 1000);
      }, 100);
    }
  }

  /**
   * Update countdown display
   */
  function updateCountdownOptimized(config, distance, countdownType) {
    const timerElement = config.element;
    const style = config.style;
    const showDays = config.showDays;
    const showHours = config.showHours;
    const showMinutes = config.showMinutes;
    const showSeconds = config.showSeconds;

    // Calculate time units
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Build the display text/state string for change detection
    let displayState;

    // Handle 'Ended' state only if countdown has finished
    if (distance < 0) {
      displayState = 'ENDED';
    } else if (style === 'simple') {
      // Simple text format: "5d 3h 45m 30s"
      const parts = [];
      if (showDays && days > 0) parts.push(days + 'd');
      if (showHours && hours > 0) parts.push(hours + 'h');
      if (showMinutes && minutes > 0) parts.push(minutes + 'm');
      if (showSeconds) parts.push(seconds + 's');

      displayState = parts.length > 0 ? parts.join(' ') : '0s';
    } else if (style === 'digital') {
      // Digital format: "05:03:45:30"
      let displayText = '';
      if (showDays) displayText += padZero(days) + ':';
      if (showHours) displayText += padZero(hours) + ':';
      if (showMinutes) displayText += padZero(minutes) + ':';
      if (showSeconds) displayText += padZero(seconds);

      displayState = displayText.replace(/:$/, '');
    } else if (style === 'circular' || style === 'box') {
      // For circular/box, create a state string from the time values
      displayState = (showDays ? days : '?') + '|' + (showHours ? hours : '?') + '|' + (showMinutes ? minutes : '?') + '|' + (showSeconds ? seconds : '?');
    } else if (countdownType === 'compact') {
      // Legacy compact format
      if (days > 0) {
        displayState = days + 'd ' + hours + 'h';
      } else if (hours > 0) {
        displayState = hours + 'h ' + minutes + 'm';
      } else {
        displayState = minutes + 'm ' + seconds + 's';
      }
    } else {
      // Legacy detailed format
      displayState = padZero(days) + ':' + padZero(hours) + ':' + padZero(minutes) + ':' + padZero(seconds);
    }

    // Check if display state has changed
    if (config.lastDisplayState === displayState) {
      return;  // Nothing changed, skip DOM updates
    }

    // Store new state
    config.lastDisplayState = displayState;

    // NOW perform the actual DOM updates
    if (distance < 0) {
      // Only set textContent if this is NOT a box-style countdown
      if (style !== 'circular' && style !== 'box') {
        timerElement.textContent = 'Ended';
      } else {
        // For box style, set all number boxes to show ended status
        updateBoxCountdown(timerElement, 0, 0, 0, 0, showDays, showHours, showMinutes, showSeconds);
      }
      return;
    }

    if (style === 'simple') {
      // Simple text format: "5d 3h 45m 30s"
      const parts = [];
      if (showDays && days > 0) parts.push(days + 'd');
      if (showHours && hours > 0) parts.push(hours + 'h');
      if (showMinutes && minutes > 0) parts.push(minutes + 'm');
      if (showSeconds) parts.push(seconds + 's');

      timerElement.textContent = parts.length > 0 ? parts.join(' ') : '0s';
    } else if (style === 'digital') {
      // Digital format: "05:03:45:30"
      let displayText = '';
      if (showDays) displayText += padZero(days) + ':';
      if (showHours) displayText += padZero(hours) + ':';
      if (showMinutes) displayText += padZero(minutes) + ':';
      if (showSeconds) displayText += padZero(seconds);

      // Remove trailing colon if no seconds are shown
      timerElement.textContent = displayText.replace(/:$/, '');
    } else if (style === 'circular' || style === 'box') {
      // Circular or box format - update boxes only (NEVER set textContent on wrapper)
      updateBoxCountdown(timerElement, days, hours, minutes, seconds, showDays, showHours, showMinutes, showSeconds);
    } else if (countdownType === 'compact') {
      // Legacy compact format
      if (days > 0) {
        timerElement.textContent = days + 'd ' + hours + 'h';
      } else if (hours > 0) {
        timerElement.textContent = hours + 'h ' + minutes + 'm';
      } else {
        timerElement.textContent = minutes + 'm ' + seconds + 's';
      }
    } else {
      // Legacy detailed format
      timerElement.textContent = padZero(days) + ':' + padZero(hours) + ':' + padZero(minutes) + ':' + padZero(seconds);
    }
  }

  /**
   * Update circular countdown display
   */
  function updateCircularCountdown(timerElement, days, hours, minutes, seconds, showDays, showHours, showMinutes, showSeconds, countdownDate) {
    const circle = timerElement.querySelector('.countdown-circle-progress');
    const textElement = timerElement.querySelector('.countdown-circle-text');

    if (!circle || !textElement) {
      return;
    }

    // Calculate total time and elapsed time
    const endDate = new Date(countdownDate).getTime();
    const now = new Date().getTime();
    const startDate = new Date(countdownDate.split('T')[0] + 'T00:00').getTime();

    const totalTime = endDate - startDate;
    const timeLeft = endDate - now;
    const percentage = Math.max(0, (timeLeft / totalTime) * 100);

    // Update circle progress (circumference of circle with radius 45 is 2 * π * 45 ≈ 282.7)
    const circumference = 282.7;
    const dashOffset = circumference - (percentage / 100) * circumference;

    circle.style.strokeDashoffset = dashOffset;

    // Update text based on display options
    let displayText = '';
    if (showDays && days > 0) {
      displayText = days + 'd';
    } else if (showHours && hours > 0) {
      displayText = hours + 'h';
    } else if (showMinutes && minutes > 0) {
      displayText = minutes + 'm';
    } else if (showSeconds) {
      displayText = seconds + 's';
    }

    textElement.textContent = displayText || '0s';
  }

  /**
   * Update box-style or circular countdown display
   */
  function updateBoxCountdown(timerElement, days, hours, minutes, seconds, showDays, showHours, showMinutes, showSeconds) {
    // Determine if this is circular or box style
    const isCircular = timerElement.querySelector('.hb-countdown-circular') !== null;

    // Update days
    if (showDays) {
      let daysWrapper = timerElement.querySelector('.hb-countdown-days');

      if (!daysWrapper) {
        daysWrapper = document.createElement('div');
        if (isCircular) {
          daysWrapper.className = 'hb-countdown-unit hb-countdown-days hb-countdown-circular';
          daysWrapper.innerHTML = '<div class="countdown-number">00</div><div class="countdown-label">D</div>';
        } else {
          daysWrapper.className = 'hb-countdown-unit hb-countdown-days';
          daysWrapper.innerHTML = '<div class="hb-countdown-box"><div class="countdown-number">00</div></div><div class="countdown-label">Day</div>';
        }
        timerElement.appendChild(daysWrapper);
      }

      const numberDiv = daysWrapper.querySelector('.countdown-number');
      if (numberDiv) {
        const newText = String(days).padStart(2, '0');
        if (numberDiv.textContent !== newText) {
          numberDiv.textContent = newText;
        }
      }
    }

    // Update hours
    if (showHours) {
      let hoursWrapper = timerElement.querySelector('.hb-countdown-hours');

      if (!hoursWrapper) {
        hoursWrapper = document.createElement('div');
        if (isCircular) {
          hoursWrapper.className = 'hb-countdown-unit hb-countdown-hours hb-countdown-circular';
          hoursWrapper.innerHTML = '<div class="countdown-number">00</div><div class="countdown-label">H</div>';
        } else {
          hoursWrapper.className = 'hb-countdown-unit hb-countdown-hours';
          hoursWrapper.innerHTML = '<div class="hb-countdown-box"><div class="countdown-number">00</div></div><div class="countdown-label">Hour</div>';
        }
        timerElement.appendChild(hoursWrapper);
      }

      const numberDiv = hoursWrapper.querySelector('.countdown-number');
      if (numberDiv) {
        const newText = String(hours).padStart(2, '0');
        if (numberDiv.textContent !== newText) {
          numberDiv.textContent = newText;
        }
      }
    }

    // Update minutes
    if (showMinutes) {
      let minutesWrapper = timerElement.querySelector('.hb-countdown-minutes');

      if (!minutesWrapper) {
        minutesWrapper = document.createElement('div');
        if (isCircular) {
          minutesWrapper.className = 'hb-countdown-unit hb-countdown-minutes hb-countdown-circular';
          minutesWrapper.innerHTML = '<div class="countdown-number">00</div><div class="countdown-label">M</div>';
        } else {
          minutesWrapper.className = 'hb-countdown-unit hb-countdown-minutes';
          minutesWrapper.innerHTML = '<div class="hb-countdown-box"><div class="countdown-number">00</div></div><div class="countdown-label">Minute</div>';
        }
        timerElement.appendChild(minutesWrapper);
      }

      const numberDiv = minutesWrapper.querySelector('.countdown-number');
      if (numberDiv) {
        const newText = String(minutes).padStart(2, '0');
        if (numberDiv.textContent !== newText) {
          numberDiv.textContent = newText;
        }
      }
    }

    // Update seconds
    if (showSeconds) {
      let secondsWrapper = timerElement.querySelector('.hb-countdown-seconds');

      if (!secondsWrapper) {
        secondsWrapper = document.createElement('div');
        if (isCircular) {
          secondsWrapper.className = 'hb-countdown-unit hb-countdown-seconds hb-countdown-circular';
          secondsWrapper.innerHTML = '<div class="countdown-number">00</div><div class="countdown-label">S</div>';
        } else {
          secondsWrapper.className = 'hb-countdown-unit hb-countdown-seconds';
          secondsWrapper.innerHTML = '<div class="hb-countdown-box"><div class="countdown-number">00</div></div><div class="countdown-label">Second</div>';
        }
        timerElement.appendChild(secondsWrapper);
      }

      const numberDiv = secondsWrapper.querySelector('.countdown-number');
      if (numberDiv) {
        const newText = String(seconds).padStart(2, '0');
        if (numberDiv.textContent !== newText) {
          numberDiv.textContent = newText;
        }
      }
    }
  }

  /**
   * Pad number with leading zero
   */
  function padZero(num) {
    return (num < 10 ? '0' : '') + num;
  }

  /**
   * Initialize coupon copy functionality
   */
  function initializeCouponCopy(bar, barId) {

    const couponDisplay = bar.querySelector('.hashbar-coupon-display');

    if (couponDisplay) {
      // Get coupon code from display element or bar element
      const couponCode = couponDisplay.getAttribute('data-coupon-code') || bar.getAttribute('data-coupon-code');

      // Get custom button texts from data attributes
      const copiedButtonText = couponDisplay.getAttribute('data-copied-text') || 'Copied!';
      const autocopyOnClick = couponDisplay.getAttribute('data-autocopy-on-click') === 'true';

      if (couponCode) {
        const codeElement = couponDisplay.querySelector('code');
        const copyButton = couponDisplay.querySelector('.hashbar-coupon-copy');


        // Helper function to show copy feedback on code element
        function showCodeFeedback() {
          if (codeElement) {
            const originalText = codeElement.textContent;
            codeElement.textContent = copiedButtonText;
            setTimeout(function() {
              codeElement.textContent = originalText;
            }, 2000);
          }
        }

        // Helper function to show copy feedback on button
        function showButtonFeedback() {
          if (copyButton) {
            const originalText = copyButton.textContent;
            copyButton.textContent = copiedButtonText;
            setTimeout(function() {
              copyButton.textContent = originalText;
            }, 2000);
          }
        }

        // Make coupon code clickable to copy (if autocopy is enabled)
        if (codeElement && autocopyOnClick) {
          codeElement.style.cursor = 'pointer';
          codeElement.title = 'Click to copy';

          codeElement.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            copyToClipboard(couponCode);
            showCodeFeedback();

            // Track coupon copy for A/B testing
            if (bar.getAttribute('data-ab-test-enabled') === 'true') {
              const abTestVariantId = bar.getAttribute('data-ab-test-variant-id');
              trackABTestEvent(barId, abTestVariantId, 'coupon_copy', couponCode);
            }
          });
        }

        // Make copy button clickable
        if (copyButton) {
          copyButton.style.cursor = 'pointer';

          copyButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            copyToClipboard(couponCode);
            showButtonFeedback();

            // Track coupon copy for A/B testing
            if (bar.getAttribute('data-ab-test-enabled') === 'true') {
              const abTestVariantId = bar.getAttribute('data-ab-test-variant-id');
              trackABTestEvent(barId, abTestVariantId, 'coupon_copy', couponCode);
            }
          });

          // Hover effect for copy button
          copyButton.addEventListener('mouseover', function() {
            copyButton.style.opacity = '1';
          });

          copyButton.addEventListener('mouseout', function() {
            copyButton.style.opacity = '0.7';
          });
        }
      } else {
      }
    }

    // Handle inline coupon code click (when countdown is inline)
    const inlineCoupon = bar.querySelector('.hashbar-coupon-inline');
    if (inlineCoupon) {
      const inlineCouponCode = inlineCoupon.getAttribute('data-coupon-code');
      const inlineCopiedText = inlineCoupon.getAttribute('data-copied-text') || 'Copied!';
      const inlineAutocopyOnClick = inlineCoupon.getAttribute('data-autocopy-on-click') === 'true';

      // Only add click handler if autocopy on click is enabled
      if (inlineAutocopyOnClick) {
        inlineCoupon.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();

          // Perform the copy
          copyToClipboard(inlineCouponCode);

          // Track coupon copy for A/B testing
          if (bar.getAttribute('data-ab-test-enabled') === 'true') {
            const abTestVariantId = bar.getAttribute('data-ab-test-variant-id');
            trackABTestEvent(barId, abTestVariantId, 'coupon_copy', inlineCouponCode);
          }

          // Store original HTML to restore after feedback
          const originalHTML = inlineCoupon.innerHTML;

          // Show feedback
          inlineCoupon.textContent = inlineCopiedText;
          inlineCoupon.style.opacity = '0.7';

          // Restore after delay
          setTimeout(function() {
            inlineCoupon.innerHTML = originalHTML;
            inlineCoupon.style.opacity = '';
          }, 2000);
        });

        // Set cursor to pointer when autocopy is enabled
        inlineCoupon.style.cursor = 'pointer';
        inlineCoupon.title = 'Click to copy';
      }

      // Also make the span selectable with better touch support
      inlineCoupon.style.userSelect = 'none';
    }
  }

  /**
   * Initialize CTA button with first message content
   */
  function initializeCTAButton(bar, barId) {
    const messagesData = bar.getAttribute('data-messages');
    const ctaButton = bar.querySelector('.hashbar-announcement-cta');

    if (!ctaButton) {
      return;
    }

    // If no messages data, try to use legacy CTA fields
    if (!messagesData) {
      const ctaText = bar.getAttribute('data-cta-text');
      const ctaUrl = bar.getAttribute('data-cta-url');
      const ctaTarget = bar.getAttribute('data-cta-target');
      const ctaEnabled = bar.getAttribute('data-cta-enabled');

      if (ctaText) {
        ctaButton.textContent = ctaText;
      }
      if (ctaUrl) {
        ctaButton.href = ctaUrl;
      }
      if (ctaTarget) {
        ctaButton.target = ctaTarget;
      }
      ctaButton.style.display = ctaEnabled === 'false' ? 'none' : 'inline-block';
      return;
    }

    let messages = [];
    try {
      messages = JSON.parse(messagesData);
    } catch (e) {
      return;
    }

    if (!Array.isArray(messages) || messages.length === 0) {
      return;
    }

    // Initialize button with first message content
    const firstMessage = messages[0];

    if (firstMessage.cta_text) {
      ctaButton.textContent = firstMessage.cta_text;
    }

    if (firstMessage.cta_url) {
      ctaButton.href = firstMessage.cta_url;
    }

    if (firstMessage.cta_target) {
      ctaButton.target = firstMessage.cta_target;
    }

    // Show/hide button based on cta_enabled flag
    ctaButton.style.display = firstMessage.cta_enabled !== false ? 'inline-block' : 'none';
  }

  /**
   * Initialize message rotation
   */
  function initializeMessageRotation(bar, barId) {
    const messagesData = bar.getAttribute('data-messages');
    const rotationInterval = parseInt(bar.getAttribute('data-message-rotation-interval'), 10) || 5;

    if (!messagesData) {
      return;
    }

    let messages = [];
    try {
      messages = JSON.parse(messagesData);
    } catch (e) {
      return;
    }

    if (!Array.isArray(messages) || messages.length <= 1) {
      return;
    }

    let currentIndex = 0;
    const contentElement = bar.querySelector('.hashbar-announcement-content');
    const messageElement = contentElement ? contentElement.querySelector('p') : null;
    const ctaButton = bar.querySelector('.hashbar-announcement-cta');

    if (!messageElement) {
      return;
    }

    // Setup interval to rotate messages (only if there are multiple messages)
    if (messages.length > 1) {
      let lastMessageText = messages[0] ? messages[0].text : '';

      setInterval(function() {
        currentIndex = (currentIndex + 1) % messages.length;
        const currentMessage = messages[currentIndex];

        if (currentMessage && currentMessage.text && currentMessage.text !== lastMessageText) {
          // CRITICAL: Preserve countdown timer when rotating messages
          // The countdown timer can be in 4 positions: before, inline, after, below
          // Only 'inline' position timers are inside the message element and get destroyed

          // Find inline countdown timers (those inside the message element)
          const inlineTimers = Array.from(messageElement.querySelectorAll('.hashbar-countdown-timer-wrapper, .hashbar-countdown-timer-text'));

          // Store references to the original timer elements
          // We'll reuse them instead of cloning to preserve the complete wrapper structure
          const originalTimers = inlineTimers.map(function(timerElement) {
            // Create a wrapper for each timer so we can easily find and restore it
            const wrapper = document.createElement('div');
            wrapper.setAttribute('data-timer-placeholder', 'true');
            timerElement.parentNode.insertBefore(wrapper, timerElement);
            wrapper.appendChild(timerElement);
            return wrapper;
          });

          // Update message text
          messageElement.innerHTML = currentMessage.text;
          lastMessageText = currentMessage.text;

          // Restore the original timer elements to the message
          // Check if the message contains {countdown} placeholder
          if (currentMessage.text && currentMessage.text.includes('{countdown}')) {
            // Replace the {countdown} text with the actual timer element
            const textNodes = Array.from(messageElement.childNodes).filter(function(node) {
              return node.nodeType === Node.TEXT_NODE && node.textContent.includes('{countdown}');
            });

            if (textNodes.length > 0) {
              // Replace the placeholder text node with the timer
              const placeholderNode = textNodes[0];
              const beforeText = placeholderNode.textContent.split('{countdown}')[0];
              const afterText = placeholderNode.textContent.split('{countdown}')[1];

              if (beforeText) {
                placeholderNode.textContent = beforeText;
              } else {
                placeholderNode.remove();
              }

              // Insert timer after the "before" text
              originalTimers.forEach(function(timerWrapper) {
                const timer = timerWrapper.querySelector('.hashbar-countdown-timer-wrapper, .hashbar-countdown-timer-text');
                if (timer) {
                  timerWrapper.parentNode.removeChild(timerWrapper);
                  placeholderNode.parentNode.insertBefore(timer, placeholderNode.nextSibling);
                }
              });

              // Add the "after" text
              if (afterText) {
                const afterNode = document.createTextNode(afterText);
                placeholderNode.parentNode.insertBefore(afterNode, placeholderNode.nextSibling);
              }
            }
          } else {
            // If no placeholder, just append the timers at the end
            originalTimers.forEach(function(timerWrapper) {
              const timer = timerWrapper.querySelector('.hashbar-countdown-timer-wrapper, .hashbar-countdown-timer-text');
              if (timer && timer.parentNode) {
                timer.parentNode.removeChild(timer);
                messageElement.appendChild(timer);
              }
            });
          }

        // Update CTA button if it exists and per-message CTA is configured
        if (ctaButton && currentMessage.cta_text) {
          ctaButton.textContent = currentMessage.cta_text;

          // Update CTA URL if different per message
          if (currentMessage.cta_url) {
            ctaButton.href = currentMessage.cta_url;
          }

          // Update CTA target if different per message
          if (currentMessage.cta_target) {
            ctaButton.target = currentMessage.cta_target;
          }

          // Show/hide button based on cta_enabled flag
          ctaButton.style.display = currentMessage.cta_enabled !== false ? 'inline-block' : 'none';
        }
      }
      }, rotationInterval * 1000);
    }
  }

  /**
   * Copy text to clipboard
   */
  function copyToClipboard(text) {
    // Modern browsers
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function() {
        // Success
      }).catch(function(err) {
        fallbackCopyToClipboard(text);
      });
    } else {
      fallbackCopyToClipboard(text);
    }
  }

  /**
   * Fallback copy to clipboard for older browsers
   */
  function fallbackCopyToClipboard(text) {
    const textarea = document.createElement('textarea');
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
  }

  /**
   * Setup exit animation trigger
   */
  function setupExitAnimation(bar) {
    // Exit animation is applied when bar is closed
    // This is handled in the closeAnnouncement function
  }

  /**
   * Track A/B test variant impression and clicks
   */
  function trackABTestVariant(bar, barId) {
    // Use provided bar element or find it
    if (!bar) {
      bar = document.querySelector('.hashbar-announcement-bar[data-bar-id="' + barId + '"]');
    }
    if (!bar) {
      return;
    }

    // Get variant ID from data attribute (set by backend)
    const variantId = bar.getAttribute('data-ab-test-variant-id');

    if (!variantId) {
      // Not an A/B test variant - could be control variant
      // Control variant doesn't have variant_id, so we use 'control' or empty string
      const testEnabled = bar.getAttribute('data-ab-test-enabled');
      if (testEnabled === 'true') {
        // Track control variant with 'control' as variant_id
        trackABTestEvent(barId, 'control', 'impression');
      }
      return;
    }

    // Track impression for test variant
    trackABTestEvent(barId, variantId, 'impression');

    // Track CTA button clicks
    const ctaButton = bar.querySelector('.hashbar-announcement-cta');
    if (ctaButton) {
      ctaButton.addEventListener('click', function(e) {
        trackABTestEvent(barId, variantId, 'click', ctaButton.href || ctaButton.getAttribute('href'));
      });
    }

    // Track coupon copy as conversion
    const couponElements = bar.querySelectorAll('.hashbar-coupon-inline, .hashbar-coupon-display');
    couponElements.forEach(function(element) {
      element.addEventListener('click', function(e) {
        trackABTestEvent(barId, variantId, 'conversion', 'coupon_copy');
      });
    });
  }

  /**
   * Track A/B test event via REST API
   */
  function trackABTestEvent(barId, variantId, eventType, eventValue) {
    // Get REST API URL and nonce from localized data
    const restUrl = (window.HashbarAnnouncementData && window.HashbarAnnouncementData.restUrl) || '/wp-json/hashbar/v1/';
    const nonce = (window.HashbarAnnouncementData && window.HashbarAnnouncementData.nonce) || '';

    // Prevent duplicate tracking within the same page load
    // Use a simple key that tracks what we've already sent in this page view
    const trackingKey = 'hashbar_ab_tracked_' + barId + '_' + variantId + '_' + eventType;

    if (sessionStorage.getItem(trackingKey) === 'true') {
      // Already tracked in this page view
      return;
    }

    sessionStorage.setItem(trackingKey, 'true');

    // Send tracking request
    const payload = {
      bar_id: barId,
      variant_id: variantId,
      event_type: eventType,
    };

    // Only include event_value if it has a value
    if (eventValue) {
      payload.event_value = eventValue;
    }

    fetch(restUrl + 'ab-test/track', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(payload),
    })
    .then(function(response) {
      return response.json().catch(() => ({}));
    })
    .then(function(data) {
      // Tracking completed
    })
    .catch(function(error) {
      // Silently handle tracking errors
    });
  }

  /**
   * Cookie utilities
   */
  function setCookie(name, value, days) {
    let expires = '';

    if (days) {
      const date = new Date();
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = '; expires=' + date.toUTCString();
    }

    document.cookie = name + '=' + (value || '') + expires + '; path=/';
  }

  function getCookie(name) {
    const nameEQ = name + '=';
    const cookies = document.cookie.split(';');

    for (let i = 0; i < cookies.length; i++) {
      let cookie = cookies[i].trim();

      if (cookie.indexOf(nameEQ) === 0) {
        return cookie.substring(nameEQ.length);
      }
    }

    return null;
  }

  /**
   * Get timezone offset in hours
   */
  function getTimezoneOffset(timezone) {
    // Handle new simplified timezone options
    if (timezone === 'site') {
      // Use the site timezone offset passed from PHP
      return typeof HashbarAnnouncementData !== 'undefined' && HashbarAnnouncementData.siteTimezoneOffset !== undefined
        ? parseFloat(HashbarAnnouncementData.siteTimezoneOffset)
        : 0;
    }

    if (timezone === 'visitor') {
      // Use visitor's local timezone (browser timezone)
      // getTimezoneOffset() returns minutes with reversed sign, so we negate and convert to hours
      return -(new Date().getTimezoneOffset() / 60);
    }

    // Legacy timezone abbreviations (for backward compatibility)
    const timezoneMap = {
      // UTC & GMT
      'UTC': 0,
      'GMT': 0,

      // North America
      'EST': -5,
      'EDT': -4,
      'CST': -6,
      'CDT': -5,
      'MST': -7,
      'MDT': -6,
      'PST': -8,
      'PDT': -7,
      'AKST': -9,
      'AKDT': -8,
      'HST': -10,
      'HADT': -9,

      // Europe
      'CET': 1,
      'CEST': 2,
      'EET': 2,
      'EEST': 3,
      'WET': 0,
      'WEST': 1,

      // Asia
      'IST': 5.5,
      'IDT': 6.5,
      'JST': 9,
      'HKT': 8,
      'MYT': 8,
      'PHT': 8,
      'KST': 9,
      'WITA': 8,
      'WIB': 7,
      'ICT': 7,
      'BDT': 6,
      'PKT': 5,

      // Middle East & Africa
      'GST': 4,
      'EAT': 3,
      'CAT': 2,
      'WAT': 1,
      'SAST': 2,

      // Oceania
      'AEST': 10,
      'AEDT': 11,
      'ACST': 9.5,
      'ACDT': 10.5,
      'AWST': 8,
      'NZST': 12,
      'NZDT': 13,

      // South America
      'ART': -3,
      'BRT': -3,
      'VET': -4,
      'CLT': -3,
      'PET': -5,
    };

    return timezoneMap[timezone] !== undefined ? timezoneMap[timezone] : 0;
  }

  /**
   * Update countdown display for all timer styles
   */
  function updateCountdownDisplay(timerElement, displayText) {
    const style = timerElement.getAttribute('data-countdown-style') || 'simple';

    if (style === 'simple' || style === 'digital') {
      // Simple and digital styles: update text content directly
      if (timerElement.textContent !== displayText) {
        timerElement.textContent = displayText;
      }
    } else if (style === 'circular' || style === 'box') {
      // Circular and box styles: update individual countdown-number elements
      if (displayText === 'Expired') {
        if (timerElement.textContent !== displayText) {
          timerElement.textContent = displayText;
        }
        return;
      }

      // Parse the display text to extract days, hours, minutes, seconds
      // Format: "1d 2h 3m 4s" or variations like "1d 2h" or "2h 3m 4s"
      const parts = displayText.split(/\s+/);
      const values = {};

      parts.forEach(function(part) {
        if (part.endsWith('d')) {
          values.days = part.slice(0, -1);
        } else if (part.endsWith('h')) {
          values.hours = part.slice(0, -1);
        } else if (part.endsWith('m')) {
          values.minutes = part.slice(0, -1);
        } else if (part.endsWith('s')) {
          values.seconds = part.slice(0, -1);
        }
      });

      // Update the countdown-number divs - only if changed
      const daysUnit = timerElement.querySelector('.hb-countdown-days');
      if (daysUnit) {
        const dayNumber = daysUnit.querySelector('.countdown-number');
        const newDayText = String(values.days || 0).padStart(2, '0');
        if (dayNumber && dayNumber.textContent !== newDayText) {
          dayNumber.textContent = newDayText;
        }
      }

      const hoursUnit = timerElement.querySelector('.hb-countdown-hours');
      if (hoursUnit) {
        const hourNumber = hoursUnit.querySelector('.countdown-number');
        const newHourText = String(values.hours || 0).padStart(2, '0');
        if (hourNumber && hourNumber.textContent !== newHourText) {
          hourNumber.textContent = newHourText;
        }
      }

      const minutesUnit = timerElement.querySelector('.hb-countdown-minutes');
      if (minutesUnit) {
        const minuteNumber = minutesUnit.querySelector('.countdown-number');
        const newMinuteText = String(values.minutes || 0).padStart(2, '0');
        if (minuteNumber && minuteNumber.textContent !== newMinuteText) {
          minuteNumber.textContent = newMinuteText;
        }
      }

      const secondsUnit = timerElement.querySelector('.hb-countdown-seconds');
      if (secondsUnit) {
        const secondNumber = secondsUnit.querySelector('.countdown-number');
        const newSecondText = String(values.seconds || 0).padStart(2, '0');
        if (secondNumber && secondNumber.textContent !== newSecondText) {
          secondNumber.textContent = newSecondText;
        }
      }
    }
  }

})();
