// Debounce: submit form ketika user mengetik pada search input global (jika ada)
document.addEventListener('DOMContentLoaded', function(){
	const globalSearch = document.querySelector('#global-search-input');
	if (globalSearch) {
		const form = globalSearch.closest('form');
		let t = null;
		globalSearch.addEventListener('input', function(){
			clearTimeout(t);
			t = setTimeout(() => { if (form) form.submit(); }, 500);
		});
	}

	// Fokuskan input offcanvas pencarian saat offcanvas ditampilkan (Bootstrap 5)
	var off = document.getElementById('offcanvasSearch');
	if (off) {
		off.addEventListener('shown.bs.offcanvas', function () {
			var input = document.getElementById('offcanvas-search-input');
			if (input) input.focus();
		});
	}

	// Progressive image loader for images with data-unsplash
		// load images inside carousels (and generic data-unsplash) progressively
		var imgs = document.querySelectorAll('img[data-unsplash]');
		imgs.forEach(function(img){
			var url = img.getAttribute('data-unsplash');
			if (!url) return;
			var pre = new Image();
			var timeout = setTimeout(function(){ try{ pre.src=''; }catch(e){} }, 4000);
			pre.onload = function(){ clearTimeout(timeout); if (pre.width>20 && pre.height>20) img.src = url; };
			pre.onerror = function(){ clearTimeout(timeout); };
			pre.src = url;
		});

		// initialize bootstrap carousels (if any) so controls work on dynamically added carousels
		var carousels = document.querySelectorAll('.carousel');
		carousels.forEach(function(c){
			try { var bs = bootstrap.Carousel.getOrCreateInstance(c); } catch(e) { /* ignore */ }
		});
});
