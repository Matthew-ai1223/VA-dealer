/**
 * VA Auto Sales — Visitor and UTM Campaign Tracking
 */
(function () {
  'use strict';

  var base = (window.APP_BASE || '').replace(/\/$/, '');
  var API_URL = (base ? base + '/' : '/') + 'Backend/api/track.php';

  // Extract utm_source from query parameters
  var urlParams = new URLSearchParams(window.location.search);
  var utmSource = urlParams.get('utm_source');

  // Trigger pageview tracking
  function trackPageView() {
    var payload = { action: 'pageview' };
    if (utmSource) {
      payload.utm_source = utmSource;
    }

    fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success && data.visitor_token) {
          // Track session duration heartbeat every 15 seconds
          startHeartbeat();
        }
      })
      .catch(function () {});
  }

  function startHeartbeat() {
    setInterval(function () {
      fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'ping' }),
        keepalive: true
      }).catch(function () {});
    }, 15000); // 15 seconds
  }

  // Run on load
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    trackPageView();
  } else {
    document.addEventListener('DOMContentLoaded', trackPageView);
  }
})();
