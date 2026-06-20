/**
 * Car detail page - image gallery
 */
(function () {
  'use strict';

  var mainImage = document.getElementById('main-image');
  var thumbs = document.querySelectorAll('.gallery__thumb');

  if (!mainImage || !thumbs.length) return;

  thumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var src = thumb.getAttribute('data-src');
      if (src) {
        mainImage.src = src;
        thumbs.forEach(function (t) { t.classList.remove('active'); });
        thumb.classList.add('active');
      }
    });
  });
})();
