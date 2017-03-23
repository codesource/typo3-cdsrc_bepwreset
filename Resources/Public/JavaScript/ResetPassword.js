/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/CdsrcBepwreset/ResetPassword
 * JavaScript module for the backend reset password form
 */
define(['jquery', 'TYPO3/CMS/Backend/jquery.clearable', 'bootstrap'], function($) {
	'use strict';

	/**
	 *
	 * @type {{options: {resetPasswordForm: string, submitButton: string, passwordFields: string, error: string, errorNoCookies: string, errorPasswordNotEquals: string, formFields: string, submitHandler: null}}}
	 * @exports TYPO3/CMS/CdsrcBepwreset/ResetPassword
	 */
	var BackendResetPassword = {
		options: {
			resetPasswordForm: '#typo3-reset-password-form',
			submitButton: '.t3js-reset-password-submit',
			error: '.t3js-login-error',
			errorNoCookies: '.t3js-login-error-nocookies',
			errorPasswordNotEquals: '.t3js-login-error-password-not-equals',
			formFields: '.t3js-login-formfields',
			submitHandler: null
		}
	},
	options = BackendResetPassword.options;

	/**
	 * Hide all form fields and show a progress message and icon
	 */
	BackendResetPassword.showLoginProcess = function() {
		BackendResetPassword.showLoadingIndicator();
		$(options.error).addClass('hidden');
		$(options.errorNoCookies).addClass('hidden');
	};

	/**
	 * Show the loading spinner in the submit button
	 */
	BackendResetPassword.showLoadingIndicator = function() {
		$(options.submitButton).button('loading');
	};

	/**
	 * Pass on to registered submit handler
	 *
	 * @param {Event} event
	 */
	BackendResetPassword.handleSubmit = function(event) {
		BackendResetPassword.showLoginProcess();

		if (BackendResetPassword.options.submitHandler) {
			BackendResetPassword.options.submitHandler(event);
		}
	};

	/**
	 * Hides input fields and shows cookie warning
	 */
	BackendResetPassword.showCookieWarning = function() {
		$(options.formFields).addClass('hidden');
		$(options.errorNoCookies).removeClass('hidden');
	};

	/**
	 * Hides cookie warning and shows input fields
	 */
	BackendResetPassword.hideCookieWarning = function() {
		$(options.formFields).removeClass('hidden');
		$(options.errorNoCookies).addClass('hidden');
	};

	/**
	 * Checks browser's cookie support
	 * see http://stackoverflow.com/questions/8112634/jquery-detecting-cookies-enabled
	 */
	BackendResetPassword.checkCookieSupport = function() {
		var cookieEnabled = navigator.cookieEnabled;

		// when cookieEnabled flag is present and false then cookies are disabled.
		if (cookieEnabled === false) {
			BackendResetPassword.showCookieWarning();
		} else {
			// try to set a test cookie if we can't see any cookies and we're using
			// either a browser that doesn't support navigator.cookieEnabled
			// or IE (which always returns true for navigator.cookieEnabled)
			if (!document.cookie && (cookieEnabled === null || /*@cc_on!@*/false)) {
				document.cookie = 'typo3-login-cookiecheck=1';

				if (!document.cookie) {
					BackendResetPassword.showCookieWarning();
				} else {
					// unset the cookie again
					document.cookie = 'typo3-login-cookiecheck=; expires=' + new Date(0).toUTCString();
				}
			}
		}
	};

	/**
	 * Registers listeners for the Login Interface
	 */
	BackendResetPassword.initializeEvents = function() {
		$(document).ajaxStart(BackendResetPassword.showLoadingIndicator);
		$(options.resetPasswordForm).on('submit', BackendResetPassword.handleSubmit);

		$('.t3js-clearable').clearable();

		// carousel news height transition
		$('.t3js-login-news-carousel').on('slide.bs.carousel', function(e) {
			var nextH = $(e.relatedTarget).height();
			$(this).find('div.active').parent().animate({ height: nextH }, 500);
		});
	};

	// initialize and return the BackendResetPassword object
	$(function() {
		BackendResetPassword.checkCookieSupport();
		BackendResetPassword.initializeEvents();
	});

	// prevent opening the login form in the backend frameset
	if (top.location.href !== location.href) {
		top.location.href = location.href;
	}

	return BackendResetPassword;
});
