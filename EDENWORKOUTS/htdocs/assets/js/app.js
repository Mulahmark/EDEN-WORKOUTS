(function () {
  var toggle = document.querySelector('[data-toggle-side]');
  if (toggle) {
    toggle.addEventListener('click', function () {
      document.querySelector('.dash-wrap').classList.toggle('side-open');
    });
  }
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (ev) {
      if (!window.confirm(el.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });
  // Charts: only draw when data is present; markup already contains the numbers.
  function draw(id, config) {
    var canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas, config);
  }
  if (window.EDEN_CHARTS) {
    window.EDEN_CHARTS.forEach(function (c) { if (c && c.id && c.config) draw(c.id, c.config); });
  }
})();
