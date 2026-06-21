/**
 * Announcement & Popup Analytics Tracker
 * Handles analytics for announcement bars and popup campaigns
 * Separate from notification bar analytics (analytics.js)
 */
(function() {
  'use strict';

  // Configuration
  const config = {
    restUrl: window.HashbarAnalyticsConfig?.restUrl || '/wp-json/hashbar/v1/',
    nonce: window.HashbarAnalyticsConfig?.nonce || '',
    batchSize: 10,
    batchInterval: 5000, // Send batch every 5 seconds
    sessionKey: 'hashbar_announcement_session'
  };

  // Event queue for batching
  let eventQueue = [];
  let batchTimer = null;

  /**
   * Detect device type based on viewport width
   */
  function getDeviceType() {
    const width = window.innerWidth;
    if (width <= 768) return 'mobile';
    if (width <= 1024) return 'tablet';
    return 'desktop';
  }

  /**
   * Detect browser and OS information
   */
  function getBrowserInfo() {
    const ua = navigator.userAgent;
    let browser = 'Other';
    let os = 'Unknown';

    // Browser detection
    if (ua.includes('Edge') || ua.includes('Edg/')) {
      browser = 'Edge';
    } else if (ua.includes('Chrome') && !ua.includes('Edge')) {
      browser = 'Chrome';
    } else if (ua.includes('Safari') && !ua.includes('Chrome')) {
      browser = 'Safari';
    } else if (ua.includes('Firefox')) {
      browser = 'Firefox';
    } else if (ua.includes('Opera') || ua.includes('OPR')) {
      browser = 'Opera';
    } else if (ua.includes('Trident') || ua.includes('MSIE')) {
      browser = 'IE';
    }

    // OS detection
    if (ua.includes('Windows')) {
      os = 'Windows';
    } else if (ua.includes('Mac')) {
      os = 'MacOS';
    } else if (ua.includes('Linux')) {
      os = 'Linux';
    } else if (ua.includes('Android')) {
      os = 'Android';
    } else if (ua.includes('iPad') || ua.includes('iPhone') || ua.includes('iPod')) {
      os = 'iOS';
    }

    return { browser, os };
  }

  /**
   * Get or create a unique session ID
   */
  function getSessionId() {
    let sessionId = sessionStorage.getItem(config.sessionKey);
    if (!sessionId) {
      sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      sessionStorage.setItem(config.sessionKey, sessionId);
    }
    return sessionId;
  }

  /**
   * Track a single event
   */
  function trackEvent(campaignId, eventType, campaignType = 'announcement', variantId = null, conversionValue = null) {
    if (!campaignId || !eventType) {
      console.warn('Missing required tracking parameters');
      return;
    }

    const { browser, os } = getBrowserInfo();

    const event = {
      campaign_id: parseInt(campaignId),
      campaign_type: campaignType,
      variant_id: variantId,
      event_type: eventType,
      session_id: getSessionId(),
      device_type: getDeviceType(),
      browser: browser,
      os: os,
      user_agent: navigator.userAgent,
      page_url: window.location.href,
      referrer_url: document.referrer || '',
      conversion_value: conversionValue
    };

    eventQueue.push(event);

    // Send batch if queue is full
    if (eventQueue.length >= config.batchSize) {
      sendBatch();
    } else {
      // Schedule batch send
      if (batchTimer) {
        clearTimeout(batchTimer);
      }
      batchTimer = setTimeout(sendBatch, config.batchInterval);
    }
  }

  /**
   * Send batched events to server
   */
  function sendBatch() {
    if (eventQueue.length === 0) {
      return;
    }

    const batch = [...eventQueue];
    eventQueue = [];

    if (batchTimer) {
      clearTimeout(batchTimer);
      batchTimer = null;
    }

    fetch(config.restUrl + 'announcement-analytics/batch', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce
      },
      body: JSON.stringify({ events: batch })
    }).catch(err => console.error('Announcement analytics error:', err));
  }

  /**
   * Send remaining events before page unload
   */
  window.addEventListener('beforeunload', function() {
    if (eventQueue.length > 0) {
      // Use sendBeacon for reliable delivery
      const data = new Blob([JSON.stringify({ events: eventQueue })], { type: 'application/json' });
      navigator.sendBeacon(config.restUrl + 'announcement-analytics/batch', data);
      eventQueue = [];
    }
  });

  /**
   * Initialize announcement bar tracking
   * Looks for elements with class 'hashbar-announcement-bar' and tracks their interactions
   */
  function initAnnouncementTracking() {
    // For now, we'll use a more flexible selector
    // This can work with any announcement bar element
    const bars = document.querySelectorAll('[data-hashbar-announcement]');

    if (bars.length === 0) {
      // Fallback: try to find bars by looking for announcement bar specific elements
      return;
    }

    bars.forEach(bar => {
      const barId = bar.dataset.hashbarAnnouncement;
      const variantId = bar.dataset.variantId || null;

      if (!barId) {
        return;
      }

      // Track view
      trackEvent(barId, 'view', 'announcement', variantId);

      // Track CTA clicks
      const ctaButtons = bar.querySelectorAll('[data-hashbar-cta]');
      ctaButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          trackEvent(barId, 'click', 'announcement', variantId);
        });
      });

      // Track close
      const closeButtons = bar.querySelectorAll('[data-hashbar-close]');
      closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          trackEvent(barId, 'close', 'announcement', variantId);
        });
      });
    });
  }

  /**
   * Initialize on DOM ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnnouncementTracking);
  } else {
    initAnnouncementTracking();
  }

  /**
   * Expose public API for custom tracking
   */
  window.HashbarAnalytics = {
    /**
     * Track a custom event
     * @param {number} campaignId - ID of the announcement bar or campaign
     * @param {string} eventType - Type of event (view, click, conversion, close)
     * @param {string} campaignType - Type of campaign (announcement, popup, etc)
     * @param {string|null} variantId - A/B test variant ID if applicable
     * @param {number|null} conversionValue - Conversion value if applicable
     */
    track: trackEvent,

    /**
     * Track a conversion event with optional value
     * @param {number} campaignId - ID of the announcement bar or campaign
     * @param {number|null} value - Conversion value (optional)
     */
    trackConversion: function(campaignId, value = null) {
      trackEvent(campaignId, 'conversion', 'announcement', null, value);
    },

    /**
     * Send all pending events immediately
     */
    flush: sendBatch,

    /**
     * Get current session ID
     * @returns {string} Current session ID
     */
    getSessionId: getSessionId,

    /**
     * Get device type
     * @returns {string} Device type (desktop, tablet, mobile)
     */
    getDeviceType: getDeviceType,

    /**
     * Get browser info
     * @returns {object} Object with browser and os properties
     */
    getBrowserInfo: getBrowserInfo
  };
})();
