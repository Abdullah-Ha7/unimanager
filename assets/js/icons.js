(function(){
  // Add an entrance animation to icons when they come into view
  function enhanceIcons(){
    var icons = document.querySelectorAll('i.bi');
    if(!('IntersectionObserver' in window)){
      icons.forEach(function(el){ el.classList.add('icon-animated'); });
      return;
    }
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          entry.target.classList.add('icon-animated');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    icons.forEach(function(el){ io.observe(el); });
  }

  // Add subtle hover effect globally
  function addHoverDefaults(){
    document.querySelectorAll('a .bi, button .bi, .btn .bi').forEach(function(el){
      el.classList.add('icon-hover-pulse');
    });
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      enhanceIcons();
      addHoverDefaults();
    });
  } else {
    enhanceIcons();
    addHoverDefaults();
  }
})();
