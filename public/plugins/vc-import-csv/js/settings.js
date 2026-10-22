/**
 * VC Import CSV - Settings Controller
 * Handles CSV upload form, file list, and client-side column mapping preview.
 */

(function () {

	'use strict';

	// ------------------------------------------------------------
	// File input: show selected file name
	// ------------------------------------------------------------
	function initFileInput() {
		var fileInput = document.querySelector('input[type="file"][name="file"]');
		if (!fileInput) return;

		fileInput.addEventListener('change', function () {
			var name = '';
			if (this.files && this.files.length) {
				name = this.files[0].name;
			}
			var label = this.closest('.mb-3')?.querySelector('.form-text');
			if (label && name) {
				label.innerHTML = 'Đã chọn: <strong>' + escapeHtml(name) + '</strong>';
			}
		});
	}

	// ------------------------------------------------------------
	// Escape HTML
	// ------------------------------------------------------------
	function escapeHtml(text) {
		if (!text) return '';
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// ------------------------------------------------------------
	// AJAX upload (progressive enhancement, falls back to normal POST)
	// ------------------------------------------------------------
	function initUploadForm() {
		var form = document.querySelector('form[action*="action=upload"]');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			var fileInput = form.querySelector('input[type="file"][name="file"]');
			if (!fileInput || !fileInput.files || !fileInput.files.length) {
				return; // let normal submission handle validation
			}

			var file = fileInput.files[0];
			var ext = file.name.split('.').pop().toLowerCase();
			if (ext !== 'csv') {
				if (typeof displayToast === 'function') {
					displayToast("danger", "Lỗi", "Chỉ chấp nhận file .csv!");
				}
				e.preventDefault();
				return;
			}

			// Allow normal form submission (backend handles upload + redirect)
			// AJAX upload is intentionally deferred to backend redirect to config page.
		});
	}

	// ------------------------------------------------------------
	// Column mapping: client-side select enhancement
	// Adds a "Select all / Skip" quick action if needed.
	// ------------------------------------------------------------
	function initColumnMapping() {
		var mappingList = document.getElementById('columnMappingList');
		if (!mappingList) return;

		var selects = mappingList.querySelectorAll('select[name^="column"]');
		selects.forEach(function (select) {
			select.addEventListener('change', function () {
				var val = this.value;
				var row = this.closest('.list-group-item');
				if (val === '[Skip]') {
					row.classList.add('list-group-item-danger');
				} else {
					row.classList.remove('list-group-item-danger');
				}
			});
		});
	}

	// ------------------------------------------------------------
	// Initialize on DOM ready
	// ------------------------------------------------------------
	function init() {
		initFileInput();
		initUploadForm();
		initColumnMapping();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
