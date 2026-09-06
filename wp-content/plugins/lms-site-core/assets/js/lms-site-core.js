
function lmsSiteCoreInit() {
	'use strict';

	var state = window.lmsSiteCore || {};
	var loginModal = document.getElementById('lms-login-modal');
	var accessModal = document.getElementById('lms-access-modal');
	var supportModal = document.getElementById('lms-support-modal');
	var accessTitle = document.getElementById('lms-access-title');
	var accessMessage = document.querySelector('[data-lms-access-message]');
	var loginForm = document.getElementById('lms-site-core-login-form');
	var loginMessage = document.querySelector('[data-lms-login-message]');
	var accountToggle = document.querySelector('.lms-site-core-account-link[aria-haspopup="true"]');

	function openModal(modal) {
		if (!modal) {
			return;
		}

		modal.hidden = false;
		document.body.classList.add('lms-site-core-modal-open');
		var firstInput = modal.querySelector('input');
		if (firstInput) {
			window.setTimeout(function () {
				firstInput.focus();
			}, 0);
		}
	}

	function closeModal(modal) {
		if (!modal) {
			return;
		}

		modal.hidden = true;

		if (loginModal && loginModal.hidden && accessModal && accessModal.hidden && supportModal && supportModal.hidden) {
			document.body.classList.remove('lms-site-core-modal-open');
		}
	}

	function showLogin(courseId) {
		var normalizedCourseId = parseInt(courseId, 10);
		if (normalizedCourseId > 0) {
			window.sessionStorage.setItem('lmsPendingCourseId', String(normalizedCourseId));
		}

		if (loginMessage) {
			loginMessage.hidden = true;
			loginMessage.textContent = '';
		}

		openModal(loginModal);
	}

	function showAccess(contactOnly) {
		if (accessTitle) {
			accessTitle.textContent = contactOnly ? 'Đăng ký nhận khóa học' : 'Chưa được cấp quyền học';
		}
		if (accessMessage) {
			accessMessage.textContent = contactOnly
				? 'Đây là khóa học hỗ trợ cộng đồng. Hãy liên hệ quản trị viên để nhận quyền học.'
				: 'Tài khoản của bạn chưa được cấp quyền cho khóa học này.';
		}
		openModal(accessModal);
	}

	function showSupport() {
		openModal(supportModal);
	}

	function showRestrictedAccessFromRedirect() {
		var params = new URLSearchParams(window.location.search);

		if ('restricted' !== params.get('lms_access') || !state.isLoggedIn) {
			return;
		}

		showAccess(false);
		params.delete('lms_access');

		var cleanQuery = params.toString();
		var cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '') + window.location.hash;
		window.history.replaceState({}, document.title, cleanUrl);
	}

	showRestrictedAccessFromRedirect();

	function closeAllModals() {
		closeModal(loginModal);
		closeModal(accessModal);
		closeModal(supportModal);
	}

	document.addEventListener('click', function (event) {
		var loginTrigger = event.target.closest('[data-lms-login-trigger]');
		var courseRequest = event.target.closest('[data-lms-course-request]');
		var closeTrigger = event.target.closest('[data-lms-modal-close]');
		var supportTrigger = event.target.closest('[data-lms-support-trigger]');
		var accountButton = event.target.closest('.lms-site-core-account-link[aria-haspopup="true"]');

		if (loginTrigger) {
			event.preventDefault();
			showLogin(state.currentCourseId || 0);
			return;
		}

		if (supportTrigger) {
			event.preventDefault();
			showSupport();
			return;
		}

		if (courseRequest) {
			event.preventDefault();
			var courseId = courseRequest.getAttribute('data-course-id') || state.currentCourseId || 0;
			var contactOnly = courseRequest.getAttribute('data-lms-contact-only') === '1';

			if (contactOnly || state.isLoggedIn) {
				showAccess(contactOnly);
			} else {
				showLogin(courseId);
			}
			return;
		}

		if (closeTrigger) {
			closeAllModals();
			return;
		}

		if (event.target === loginModal) {
			closeModal(loginModal);
			return;
		}

		if (event.target === accessModal) {
			closeModal(accessModal);
			return;
		}

		if (event.target === supportModal) {
			closeModal(supportModal);
			return;
		}

		if (accountButton) {
			var expanded = accountButton.getAttribute('aria-expanded') === 'true';
			accountButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			accountButton.parentElement.classList.toggle('is-open', !expanded);
			return;
		}

		if (!event.target.closest('.lms-site-core-account-item, .lms-site-core-header-account')) {
			document.querySelectorAll('.lms-site-core-account-item.is-open, .lms-site-core-header-account.is-open').forEach(function (accountItem) {
				accountItem.classList.remove('is-open');
				var openButton = accountItem.querySelector('[aria-haspopup="true"]');
				if (openButton) {
					openButton.setAttribute('aria-expanded', 'false');
				}
			});
		}
	});

	document.addEventListener('keydown', function (event) {
		if ('Escape' === event.key) {
			closeAllModals();
		}
	});

	if (loginForm) {
		loginForm.addEventListener('submit', function (event) {
			event.preventDefault();

			var submitButton = loginForm.querySelector('button[type="submit"]');
			var formData = new FormData(loginForm);
			formData.append('action', 'lms_site_core_login');
			formData.append('nonce', state.nonce || '');

			if (submitButton) {
				submitButton.disabled = true;
				submitButton.textContent = 'Đang đăng nhập...';
			}

			fetch(state.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload.success) {
						throw new Error(payload.data && payload.data.message ? payload.data.message : state.loginError);
					}

					window.location.reload();
				})
				.catch(function (error) {
					if (loginMessage) {
						loginMessage.textContent = error.message || state.networkError;
						loginMessage.hidden = false;
					}

					if (submitButton) {
						submitButton.disabled = false;
						submitButton.textContent = 'Đăng nhập';
					}
				});
		});
	}

	var defaultGridAttempts = 0;

	function setDefaultCourseGrid() {
		var gridToggle = document.getElementById('lp-switch-layout-btn-grid');

		if (gridToggle) {
			if (!gridToggle.checked) {
				gridToggle.click();
			}
			return;
		}

		if (defaultGridAttempts < 20) {
			defaultGridAttempts += 1;
			window.setTimeout(setDefaultCourseGrid, 100);
		}
	}

	window.setTimeout(setDefaultCourseGrid, 0);

	if (
		state.isLoggedIn &&
		state.currentCourseId &&
		!state.currentCourseAccess &&
		window.sessionStorage.getItem('lmsPendingCourseId') === String(state.currentCourseId)
	) {
		window.sessionStorage.removeItem('lmsPendingCourseId');
		showAccess(false);
	}
}

if ('loading' === document.readyState) {
	document.addEventListener('DOMContentLoaded', lmsSiteCoreInit);
} else {
	lmsSiteCoreInit();
}
