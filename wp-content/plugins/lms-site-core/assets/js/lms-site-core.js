
(function () {
	'use strict';

	var state = window.lmsSiteCore || {};
	var loginModal = document.getElementById('lms-login-modal');
	var accessModal = document.getElementById('lms-access-modal');
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

		if (loginModal && loginModal.hidden && accessModal && accessModal.hidden) {
			document.body.classList.remove('lms-site-core-modal-open');
		}
	}

	function showLogin(courseId) {
		if (courseId) {
			window.sessionStorage.setItem('lmsPendingCourseId', String(courseId));
		}

		if (loginMessage) {
			loginMessage.hidden = true;
			loginMessage.textContent = '';
		}

		openModal(loginModal);
	}

	function showAccess() {
		openModal(accessModal);
	}

	function closeAllModals() {
		closeModal(loginModal);
		closeModal(accessModal);
	}

	document.addEventListener('click', function (event) {
		var loginTrigger = event.target.closest('[data-lms-login-trigger]');
		var courseRequest = event.target.closest('[data-lms-course-request]');
		var closeTrigger = event.target.closest('[data-lms-modal-close]');
		var accountButton = event.target.closest('.lms-site-core-account-link[aria-haspopup="true"]');

		if (loginTrigger) {
			event.preventDefault();
			showLogin(state.currentCourseId || 0);
			return;
		}

		if (courseRequest) {
			event.preventDefault();
			var courseId = courseRequest.getAttribute('data-course-id') || state.currentCourseId || 0;

			if (state.isLoggedIn) {
				showAccess();
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

		if (accountButton) {
			var expanded = accountButton.getAttribute('aria-expanded') === 'true';
			accountButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			accountButton.parentElement.classList.toggle('is-open', !expanded);
			return;
		}

		if (!event.target.closest('.lms-site-core-account-item')) {
			var accountItem = document.querySelector('.lms-site-core-account-item.is-open');
			if (accountItem) {
				accountItem.classList.remove('is-open');
				var openButton = accountItem.querySelector('[aria-haspopup="true"]');
				if (openButton) {
					openButton.setAttribute('aria-expanded', 'false');
				}
			}
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

	if (
		state.isLoggedIn &&
		state.currentCourseId &&
		!state.currentCourseAccess &&
		window.sessionStorage.getItem('lmsPendingCourseId') === String(state.currentCourseId)
	) {
		window.sessionStorage.removeItem('lmsPendingCourseId');
		showAccess();
	}
})();
