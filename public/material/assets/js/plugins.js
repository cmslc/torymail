/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Version: 4.3.0
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Common Plugins Js File
*/

//Common plugins
(function(){
  function loadScript(src) {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = src;
    document.head.appendChild(s);
  }
  if(document.querySelectorAll("[toast-list]").length || document.querySelectorAll('[data-choices]').length || document.querySelectorAll("[data-provider]").length){
    loadScript('https://cdn.jsdelivr.net/npm/toastify-js');
    loadScript('https://cdn.jsdelivr.net/npm/choices.js@11.1.0/public/assets/scripts/choices.min.js');
    loadScript('https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js');
  }
})();