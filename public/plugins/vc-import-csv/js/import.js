/**
 * VC Import CSV - Import Controller
 * Handles chunked import flow with progress display.
 * Calls the backend `next` endpoint repeatedly until import is finished.
 */

class ImportController {

	constructor() {
		this.provider = '';
		this.column = [];
		this.nextUrl = '';
		this.position = 0;
		this.count = 0;
		this.request = null;
	}

	init(provider, column, nextUrl) {
		this.provider = provider || '';
		this.column = column || [];
		this.nextUrl = nextUrl || '';
		this.position = 0;
		this.count = 0;

		this.updateStatus('Đang khởi tạo...');

		if (this.nextUrl) {
			window.ajaxStack.add(this.request.bind(this));
		} else {
			this.updateStatus('Không có URL xử lý.', 'danger');
		}
	}

	request() {
		if (!this.nextUrl) {
			return;
		}

		let params = { csrf: document.querySelector('[name=csrf]')?.value };
		let body = new URLSearchParams(params);

		// Append column mapping as array
		if (this.column && Array.isArray(this.column)) {
			this.column.forEach(item => {
				body.append('column[]', item);
			});
		}

		let self = this;
		return fetch(this.nextUrl, {
			method: "POST",
			headers: {
				"X-Requested-With": "XMLHttpRequest",
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: body
		})
		.then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					return Promise.reject([response, text]);
				});
			}
			return response.json();
		})
		.then((json) => {
			this.handleResponse(json);
		})
		.catch((error) => {
			let [response, responseInText] = error;
			let message = response.statusText ?? "Error!";

			if (typeof error.json === "function") {
				error.json().then(jsonError => {
					message = jsonError.error || jsonError.message || JSON.stringify(jsonError);
				}).catch(() => {
					message = response.statusText + " " + message;
				});
			}

			if (typeof displayToast === 'function') {
				displayToast("danger", "Lỗi import", message);
			}
			this.updateStatus('<div class="text-danger">' + message + '</div>', 'danger');
			document.getElementById('progress-bar')?.classList.add('bg-danger');
			document.getElementById('progress-bar')?.classList.remove('bg-primary');
		});
	}

	handleResponse(json) {
		// Clear previous status html
		let statusEl = document.getElementById('progress-status');
		if (statusEl) statusEl.innerHTML = '';

		// Error from backend
		if (json.error) {
			let bar = document.getElementById('progress-bar');
			if (bar) {
				bar.classList.add('bg-danger');
				bar.classList.remove('bg-primary');
			}
			if (statusEl) {
				statusEl.innerHTML = '<div class="text-danger">' + json.error + '</div>';
			}
			if (typeof displayToast === 'function') {
				displayToast("danger", "Lỗi import", json.error);
			}
			return;
		}

		// Success message
		if (json.success) {
			if (statusEl) {
				statusEl.innerHTML = '<div class="text-success">' + json.success + '</div>';
			}
			if (typeof displayToast === 'function') {
				displayToast("success", "Import", json.success);
			}
		}

		// Table info
		if (json.table) {
			if (statusEl) {
				statusEl.innerHTML = json.table;
			}
		}

		// File name
		if (json.file) {
			let fileEl = document.getElementById('progress-file');
			if (fileEl) fileEl.innerHTML = json.file;
		}

		// Position (current row)
		if (json.position !== undefined) {
			this.position = json.position;
			let posEl = document.getElementById('progress-position');
			if (posEl) {
				posEl.innerHTML = json.position;
				posEl.classList.remove('d-none');
			}
		}

		// Count (total rows processed)
		if (json.count !== undefined) {
			this.count = json.count;
			let countEl = document.getElementById('progress-count');
			if (countEl) countEl.innerHTML = json.count;
		}

		// Page
		if (json.page) {
			let pageEl = document.getElementById('progress-page');
			if (pageEl) pageEl.innerHTML = json.page;
		}

		// Calculate percentage
		let percent = 0;
		if (this.count > 0) {
			percent = (this.position * 100) / this.count;
			if (percent > 100) percent = 100;
		}

		// Continue or finish
		if (json.url && (!json.count || !json.last)) {
			this.nextUrl = json.url;
			window.ajaxStack.add(this.request.bind(this));
		} else {
			// Import finished
			percent = 100;
			let bar = document.getElementById('progress-bar');
			if (bar) {
				bar.classList.add('bg-success');
				bar.classList.remove('bg-primary', 'progress-bar-animated');
				bar.style.width = '100%';
			}
			this.updateStatus('Hoàn tất import.', 'success');
			if (typeof displayToast === 'function') {
				displayToast("success", "Hoàn tất", json.success || 'Import đã hoàn tất!');
			}
		}

		// Update progress bar width
		let bar = document.getElementById('progress-bar');
		if (bar) {
			bar.style.width = percent + '%';
			bar.setAttribute('aria-valuenow', percent);
		}
	}

	updateStatus(message, type = '') {
		let el = document.getElementById('progress-status');
		if (!el) return;
		if (type) {
			el.className = '';
			if (type === 'success') el.classList.add('text-success');
			if (type === 'danger') el.classList.add('text-danger');
			if (type === 'warning') el.classList.add('text-warning');
		}
		el.innerHTML = message;
	}

}

export default ImportController;
