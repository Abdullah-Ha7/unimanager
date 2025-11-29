// Minimal JS to support navbar collapse and dropdowns without Bootstrap
(function(){
  // Navbar collapse (custom lightweight fallback if Bootstrap JS fails)
  document.addEventListener('click', function(e){
    var toggler = e.target.closest('.navbar-toggler');
    if (toggler) {
      var targetSel = toggler.getAttribute('data-bs-target');
      if (targetSel) {
        var target = document.querySelector(targetSel);
        if (target) target.classList.toggle('show');
      }
    }
  });

  // Dropdown fallback: manual toggle preserving aria-expanded
  document.addEventListener('click', function(e){
    var toggle = e.target.closest('#adminDropdown.dropdown-toggle, .dropdown-toggle');
    if (toggle) {
      // If Bootstrap is present, let it handle the toggle
      if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Dropdown) {
        return; // avoid double-toggling
      }
      // If Bootstrap handled it (has .show on parent already), let it be; else fallback.
      var parentLi = toggle.closest('.dropdown');
      var menu = parentLi ? parentLi.querySelector('.dropdown-menu') : null;
      if (!menu) return;
      // Prevent default so anchor/button doesn't navigate if fallback active
      e.preventDefault();
      var willShow = !menu.classList.contains('show');
      menu.classList.toggle('show', willShow);
      toggle.classList.toggle('show', willShow);
      toggle.setAttribute('aria-expanded', willShow ? 'true' : 'false');
    }
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e){
    var openMenus = document.querySelectorAll('.dropdown-menu.show');
    openMenus.forEach(function(menu){
      var parent = menu.closest('.dropdown');
      if (!parent) return;
      var toggle = parent.querySelector('.dropdown-toggle');
      if (!parent.contains(e.target)) {
        menu.classList.remove('show');
        if (toggle) {
          toggle.classList.remove('show');
          toggle.setAttribute('aria-expanded','false');
        }
      }
    });
  });
})();
