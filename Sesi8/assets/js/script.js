// Simple JS: debounce search submit
console.log('e_commerce script loaded');

(function(){
	const searchInput = document.querySelector('#search-input');
	if (!searchInput) return;
	const form = searchInput.closest('form');
	let timer = null;
	searchInput.addEventListener('input', function(){
		clearTimeout(timer);
		timer = setTimeout(() => {
			if (form) form.submit();
		}, 500);
	});
})();

// Focus offcanvas search input when the offcanvas is shown (Bootstrap 5)
document.addEventListener('DOMContentLoaded', function(){
  var off = document.getElementById('offcanvasSearch');
  if (!off) return;
  off.addEventListener('shown.bs.offcanvas', function () {
    var input = document.getElementById('offcanvas-search-input');
    if (input) input.focus();
  });
});

// Progressive replace for images using data-unsplash: try to load remote image and swap only on success
document.addEventListener('DOMContentLoaded', function(){
	var imgs = document.querySelectorAll('img[data-unsplash]');
	imgs.forEach(function(img){
		var url = img.getAttribute('data-unsplash');
		if (!url) return;
		// attempt to preload remote image
		var pre = new Image();
		pre.onload = function(){
			// only replace if width/height plausible
			if (pre.width > 20 && pre.height > 20) {
				img.src = url;
			}
		};
		// avoid long waits; set a short timeout to abort
		var done = false;
		var t = setTimeout(function(){ done = true; pre.src = ''; }, 3000);
		pre.onerror = function(){ clearTimeout(t); };
		pre.src = url;
	});
});
