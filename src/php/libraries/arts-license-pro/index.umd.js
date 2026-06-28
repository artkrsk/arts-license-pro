/*!
 * Arts License Pro v1.0.0
 * https://artemsemkin.com
 * https://github.com/artkrsk/arts-license-pro
 * © 2025 Artem Semkin
 * License: GPL-3.0
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["ArtsLicensePro"] = factory();
	else
		root["ArtsLicensePro"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/ts/core/api/LicenseAPIAdapter.ts":
/*!**********************************************!*\
  !*** ./src/ts/core/api/LicenseAPIAdapter.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LicenseAPIAdapter: () => (/* binding */ LicenseAPIAdapter)
/* harmony export */ });
/** AJAX adapter implementing ILicenseAPI */
class LicenseAPIAdapter {
    productSlug;
    ajaxUrl;
    nonce;
    constructor(productSlug, ajaxUrl, nonce) {
        this.productSlug = productSlug;
        this.ajaxUrl = ajaxUrl;
        this.nonce = nonce;
    }
    /** Activate license with given key */
    async activate(key) {
        const formData = new URLSearchParams({
            action: `${this.productSlug}_license_activate`,
            _wpnonce: this.nonce,
            license_key: key
        });
        const response = await fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        const result = (await response.json());
        if (!result.success) {
            throw new Error(result.data?.message || 'Activation failed');
        }
        return result.data;
    }
    /** Deactivate current license */
    async deactivate() {
        const formData = new URLSearchParams({
            action: `${this.productSlug}_license_deactivate`,
            _wpnonce: this.nonce
        });
        const response = await fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        const result = (await response.json());
        if (!result.success) {
            throw new Error(result.data?.message || 'Deactivation failed');
        }
    }
    /** Refresh license status */
    async check() {
        const formData = new URLSearchParams({
            action: `${this.productSlug}_license_check`,
            _wpnonce: this.nonce
        });
        const response = await fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        const result = (await response.json());
        if (!result.success) {
            if (result.data?.message === 'No license found') {
                return null;
            }
            throw new Error(result.data?.message || 'Check failed');
        }
        return result.data;
    }
}


/***/ }),

/***/ "./src/ts/core/auto-mount.ts":
/*!***********************************!*\
  !*** ./src/ts/core/auto-mount.ts ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   autoMountComponents: () => (/* binding */ autoMountComponents)
/* harmony export */ });
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components */ "./src/ts/core/components/LicensePanel.tsx");
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components */ "./src/ts/core/components/ProBadge.tsx");
/* harmony import */ var _api_LicenseAPIAdapter__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./api/LicenseAPIAdapter */ "./src/ts/core/api/LicenseAPIAdapter.ts");



const { createElement, render } = wp.element;
/** Auto-mount license panels and pro badges on page load */
function autoMountComponents() {
    // Check if artsLicenseProInstances exists
    if (!window.artsLicenseProInstances) {
        return;
    }
    // Mount license panels
    const licensePanels = document.querySelectorAll('.arts-license-pro-license-panel-root');
    licensePanels.forEach((element) => {
        const productSlug = element.getAttribute('data-product');
        const instanceData = window.artsLicenseProInstances[productSlug];
        if (!instanceData) {
            console.warn(`No instance data found for product: ${productSlug}`);
            return;
        }
        const api = new _api_LicenseAPIAdapter__WEBPACK_IMPORTED_MODULE_0__.LicenseAPIAdapter(instanceData.productSlug, instanceData.ajaxUrl, instanceData.nonce);
        render(createElement(_components__WEBPACK_IMPORTED_MODULE_1__.LicensePanel, {
            initialLicense: instanceData.initialLicense,
            licenseConfig: instanceData.config,
            licenseAPI: api
        }), element);
    });
    // Mount pro badges
    const proBadges = document.querySelectorAll('.arts-license-pro-badge-root');
    proBadges.forEach((element) => {
        const configData = element.getAttribute('data-config');
        if (!configData)
            return;
        try {
            const config = JSON.parse(configData);
            render(createElement(_components__WEBPACK_IMPORTED_MODULE_2__.ProBadge, config), element);
        }
        catch (err) {
            console.error('Failed to parse pro badge config:', err);
        }
    });
}


/***/ }),

/***/ "./src/ts/core/components/LicenseForm.tsx":
/*!************************************************!*\
  !*** ./src/ts/core/components/LicenseForm.tsx ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LicenseForm: () => (/* binding */ LicenseForm)
/* harmony export */ });
/* harmony import */ var _ProBadge__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProBadge */ "./src/ts/core/components/ProBadge.tsx");

const { useState, useEffect } = wp.element;
const { Button, TextControl } = wp.components;
const { dateI18n } = wp.date;
const { __ } = wp.i18n;
/** License activation form component */
function LicenseForm({ onActivate, onDeactivate, onRefresh, isLoading, licenseKey: initialKey, isActivated, license, apiError, licenseConfig, shouldRefreshPageOnLicenseChange = false, shouldAutoRefreshLicenseOnMount = false }) {
    const [licenseKey, setLicenseKey] = useState(initialKey || '');
    const [validationError, setValidationError] = useState(null);
    const [showingError, setShowingError] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);
    useEffect(() => {
        if (shouldAutoRefreshLicenseOnMount && isActivated) {
            handleRefreshClick();
        }
    }, []);
    const validateKey = (key) => {
        if (!key || key.trim().length === 0) {
            return __('License key is required', 'arts-license-pro');
        }
        return null;
    };
    const handleRefreshClick = async () => {
        setIsRefreshing(true);
        try {
            await onRefresh();
        }
        catch (err) {
            setShowingError(true);
        }
        finally {
            setIsRefreshing(false);
        }
    };
    const handleActivateClick = async () => {
        const error = validateKey(licenseKey);
        if (error) {
            setValidationError(error);
            return;
        }
        setValidationError(null);
        try {
            await onActivate(licenseKey);
            if (shouldRefreshPageOnLicenseChange) {
                window.location.reload();
            }
        }
        catch (err) {
            setShowingError(true);
            /** Error handled by parent */
        }
    };
    const handleDeactivateClick = async () => {
        if (!confirm(__('Are you sure you want to deactivate this license?', 'arts-license-pro'))) {
            return;
        }
        try {
            await onDeactivate();
            setLicenseKey('');
            if (shouldRefreshPageOnLicenseChange) {
                window.location.reload();
            }
        }
        catch (err) {
            setShowingError(true);
            /** Error handled by parent */
        }
    };
    const renderError = (message) => (React.createElement("span", { className: "arts-license-pro-error" },
        React.createElement("span", { className: "arts-license-pro-error__icon" }, "\u2715"),
        message));
    let helpContent = null;
    if (validationError) {
        helpContent = renderError(validationError);
    }
    else if (showingError && apiError) {
        helpContent = renderError(apiError);
    }
    else if (isActivated && license) {
        const listItems = [];
        /** Use API is_local flag to determine environment label */
        const statusText = license.is_local
            ? __('Activated (staging domain)', 'arts-license-pro')
            : __('Activated', 'arts-license-pro');
        /** Clickable status badge */
        listItems.push(React.createElement("li", { key: "status" },
            React.createElement("div", { className: `arts-license-pro-status-badge arts-license-pro-status-badge_valid ${isRefreshing ? 'arts-license-pro-status-badge_refreshing' : 'arts-license-pro-status-badge_clickable'}`, onClick: isRefreshing ? undefined : handleRefreshClick, title: isRefreshing ? '' : __('Click to refresh', 'arts-license-pro') },
                React.createElement("span", { className: "arts-license-pro-status-badge__icon" }, isRefreshing ? '⟳' : '✓'),
                React.createElement("span", { className: "arts-license-pro-status-badge__text" }, isRefreshing ? __('Checking license...', 'arts-license-pro') : statusText))));
        /** Purchase date */
        if (license.date_purchased) {
            const dateString = license.date_purchased.split(' ')[0] || license.date_purchased;
            const formattedPurchaseDate = dateI18n('M d, Y', dateString);
            listItems.push(React.createElement("li", { key: "purchased" },
                "\u2713 ",
                __('Purchased on', 'arts-license-pro'),
                " ",
                formattedPurchaseDate));
        }
        /** Support - show as list item with inline badge */
        if (license.date_supported_until) {
            const dateString = license.date_supported_until.split(' ')[0] || license.date_supported_until;
            const supportDate = new Date(dateString);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isExpired = supportDate < today;
            const formattedDate = dateI18n('M d, Y', dateString);
            const supportStatus = isExpired ? 'warning' : 'success';
            const supportUrl = isExpired ? licenseConfig.renewSupportUrl : licenseConfig.supportUrl;
            const supportText = isExpired
                ? __('Renew Support', 'arts-license-pro')
                : __('Get Support', 'arts-license-pro');
            if (isExpired) {
                listItems.push(React.createElement("li", { key: "support", className: "arts-license-pro-error" },
                    React.createElement("span", { className: "arts-license-pro-error__icon" }, "\u2715"),
                    __('Support Expired Since', 'arts-license-pro'),
                    " ",
                    formattedDate,
                    ' ',
                    React.createElement(_ProBadge__WEBPACK_IMPORTED_MODULE_0__.ProBadge, { showWrapper: false, renderAsLink: true, href: supportUrl, text: supportText, status: supportStatus })));
            }
            else {
                listItems.push(React.createElement("li", { key: "support" },
                    "\u2713 ",
                    __('Supported Until', 'arts-license-pro'),
                    " ",
                    formattedDate,
                    ' ',
                    React.createElement(_ProBadge__WEBPACK_IMPORTED_MODULE_0__.ProBadge, { showWrapper: false, renderAsLink: true, href: supportUrl, text: supportText, status: supportStatus })));
            }
        }
        /** Updates - show lifetime or expiration date */
        if (license.date_updates_provided_until) {
            if (license.date_updates_provided_until === 'lifetime') {
                listItems.push(React.createElement("li", { key: "updates" },
                    "\u2713 ",
                    __('Lifetime Updates', 'arts-license-pro')));
            }
            else {
                const dateString = license.date_updates_provided_until.split(' ')[0] || license.date_updates_provided_until;
                const updatesDate = new Date(dateString);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const isExpired = updatesDate < today;
                const formattedUpdatesDate = dateI18n('M d, Y', dateString);
                if (isExpired) {
                    listItems.push(React.createElement("li", { key: "updates", className: "arts-license-pro-error" },
                        React.createElement("span", { className: "arts-license-pro-error__icon" }, "\u2715"),
                        __('Updates Expired on', 'arts-license-pro'),
                        " ",
                        formattedUpdatesDate));
                }
                else {
                    listItems.push(React.createElement("li", { key: "updates" },
                        "\u2713 ",
                        __('Updates Provided Till', 'arts-license-pro'),
                        " ",
                        formattedUpdatesDate));
                }
            }
        }
        /** Expiration */
        if (license.expires) {
            if (license.expires === 'lifetime') {
                listItems.push(React.createElement("li", { key: "expires" },
                    "\u2713 ",
                    __('Lifetime License', 'arts-license-pro')));
            }
            else {
                listItems.push(React.createElement("li", { key: "expires" },
                    "\u2713 ",
                    __('Expires', 'arts-license-pro'),
                    ": ",
                    license.expires));
            }
        }
        /** Activations - show unlimited or current usage */
        if (license.site_count !== undefined && license.license_limit !== undefined) {
            const limit = Number(license.license_limit);
            if (limit === 0 || license.activations_left === 'unlimited') {
                listItems.push(React.createElement("li", { key: "activations" },
                    "\u2713 ",
                    __('Unlimited Activations', 'arts-license-pro')));
            }
            else {
                listItems.push(React.createElement("li", { key: "activations" },
                    "\u2713 ",
                    __('Activations', 'arts-license-pro'),
                    ": ",
                    license.site_count,
                    "/",
                    license.license_limit));
            }
        }
        /** Render license info list */
        helpContent = (React.createElement("div", { className: "arts-license-pro-info" },
            React.createElement("ul", { className: "arts-license-pro-info__list" }, listItems)));
    }
    else {
        helpContent = (React.createElement("span", null,
            __('Enter Your License Key', 'arts-license-pro'),
            ".",
            ' ',
            React.createElement(_ProBadge__WEBPACK_IMPORTED_MODULE_0__.ProBadge, { showWrapper: false, renderAsLink: true, href: licenseConfig.purchaseUrl, text: __('Get License Key', 'arts-license-pro') })));
    }
    return (React.createElement("div", { className: "arts-license-pro-form" },
        React.createElement("div", { className: "arts-license-pro-form__input-wrapper" },
            React.createElement(TextControl, { value: licenseKey, onChange: (value) => {
                    setLicenseKey(value);
                    setValidationError(null);
                    setShowingError(false);
                }, onKeyDown: (e) => {
                    if (e.key === 'Enter' && !isActivated && licenseKey.trim()) {
                        e.preventDefault();
                        handleActivateClick();
                    }
                }, placeholder: "XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX", help: helpContent, className: validationError || (showingError && apiError) ? 'has-error' : '', disabled: isLoading || isRefreshing || isActivated, readOnly: isActivated }),
            React.createElement(Button, { variant: isActivated ? 'secondary' : 'primary', type: "button", onClick: isActivated ? handleDeactivateClick : handleActivateClick, isBusy: isLoading || isRefreshing, disabled: isLoading || isRefreshing || (!isActivated && !licenseKey.trim()), isDestructive: isActivated && !isRefreshing, className: "arts-license-pro-form__button" }, isRefreshing
                ? __('Refreshing...', 'arts-license-pro')
                : isLoading
                    ? isActivated
                        ? __('Deactivating...', 'arts-license-pro')
                        : __('Activating...', 'arts-license-pro')
                    : isActivated
                        ? __('Deactivate', 'arts-license-pro')
                        : __('Activate', 'arts-license-pro')))));
}


/***/ }),

/***/ "./src/ts/core/components/LicensePanel.tsx":
/*!*************************************************!*\
  !*** ./src/ts/core/components/LicensePanel.tsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LicensePanel: () => (/* binding */ LicensePanel)
/* harmony export */ });
/* harmony import */ var _LicenseForm__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LicenseForm */ "./src/ts/core/components/LicenseForm.tsx");
/* harmony import */ var _hooks_useLicense__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../hooks/useLicense */ "./src/ts/core/hooks/useLicense.ts");


/** License panel container component */
function LicensePanel({ initialLicense, licenseConfig, licenseAPI, shouldRefreshPageOnLicenseChange = true, shouldAutoRefreshLicenseOnMount = false, }) {
    const { license, isLoading, error, activateLicense, deactivateLicense, refreshLicense } = (0,_hooks_useLicense__WEBPACK_IMPORTED_MODULE_0__.useLicense)({
        initialLicense,
        api: licenseAPI,
    });
    const isActivated = license && license.status === 'valid';
    const licenseKey = license?.license_key || null;
    return (React.createElement("div", { className: "arts-license-pro-panel" },
        React.createElement(_LicenseForm__WEBPACK_IMPORTED_MODULE_1__.LicenseForm, { onActivate: activateLicense, onDeactivate: deactivateLicense, onRefresh: refreshLicense, isLoading: isLoading, licenseKey: licenseKey, isActivated: !!isActivated, license: license, apiError: error, licenseConfig: licenseConfig, shouldRefreshPageOnLicenseChange: shouldRefreshPageOnLicenseChange, shouldAutoRefreshLicenseOnMount: shouldAutoRefreshLicenseOnMount })));
}


/***/ }),

/***/ "./src/ts/core/components/ProBadge.tsx":
/*!*********************************************!*\
  !*** ./src/ts/core/components/ProBadge.tsx ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ProBadge: () => (/* binding */ ProBadge)
/* harmony export */ });
const { __ } = wp.i18n;
/** Pro feature badge component */
const ProBadge = ({ label, icon, showWrapper = true, renderAsLink = true, href, text, status = 'default', openInNewWindow = true }) => {
    const badgeText = text || __('Get Pro', 'arts-license-pro');
    /** Determine CSS class based on status */
    const statusClass = status !== 'default' ? ` arts-license-pro-badge_${status}` : '';
    const badgeClassName = `arts-license-pro-badge${statusClass}`;
    // Only render as link if renderAsLink is true AND href is provided
    const shouldRenderAsLink = renderAsLink && href;
    const badge = shouldRenderAsLink ? (React.createElement("a", { href: href, className: badgeClassName, ...(openInNewWindow && { target: '_blank', rel: 'noopener noreferrer' }) }, badgeText)) : (React.createElement("span", { className: badgeClassName }, badgeText));
    if (!showWrapper) {
        return badge;
    }
    return (React.createElement("span", { className: "arts-license-pro-badge-wrapper" },
        icon && React.createElement("span", { className: `dashicons ${icon}` }),
        label && React.createElement("span", { className: "arts-license-pro-badge-wrapper__label" }, label),
        badge));
};


/***/ }),

/***/ "./src/ts/core/hooks/useLicense.ts":
/*!*****************************************!*\
  !*** ./src/ts/core/hooks/useLicense.ts ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useLicense: () => (/* binding */ useLicense)
/* harmony export */ });
const { useState } = wp.element;
/** License management hook */
function useLicense(config) {
    const [license, setLicense] = useState(config.initialLicense);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const activateLicense = async (key) => {
        setIsLoading(true);
        setError(null);
        try {
            const result = await config.api.activate(key);
            setLicense(result);
        }
        catch (err) {
            const message = err instanceof Error ? err.message : 'Activation failed';
            setError(message);
            throw err;
        }
        finally {
            setIsLoading(false);
        }
    };
    const deactivateLicense = async () => {
        setIsLoading(true);
        setError(null);
        try {
            await config.api.deactivate();
            setLicense(null);
        }
        catch (err) {
            const message = err instanceof Error ? err.message : 'Deactivation failed';
            setError(message);
            throw err;
        }
        finally {
            setIsLoading(false);
        }
    };
    const refreshLicense = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const result = await config.api.check();
            setLicense(result);
        }
        catch (err) {
            const message = err instanceof Error ? err.message : 'Failed to refresh license';
            setError(message);
            throw err;
        }
        finally {
            setIsLoading(false);
        }
    };
    return {
        license,
        isLoading,
        error,
        activateLicense,
        deactivateLicense,
        refreshLicense
    };
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************!*\
  !*** ./src/ts/index.ts ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LicenseAPIAdapter: () => (/* reexport safe */ _core_api_LicenseAPIAdapter__WEBPACK_IMPORTED_MODULE_4__.LicenseAPIAdapter),
/* harmony export */   LicenseForm: () => (/* reexport safe */ _core_components__WEBPACK_IMPORTED_MODULE_2__.LicenseForm),
/* harmony export */   LicensePanel: () => (/* reexport safe */ _core_components__WEBPACK_IMPORTED_MODULE_1__.LicensePanel),
/* harmony export */   ProBadge: () => (/* reexport safe */ _core_components__WEBPACK_IMPORTED_MODULE_3__.ProBadge),
/* harmony export */   useLicense: () => (/* reexport safe */ _core_hooks_useLicense__WEBPACK_IMPORTED_MODULE_5__.useLicense)
/* harmony export */ });
/* harmony import */ var _core_auto_mount__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./core/auto-mount */ "./src/ts/core/auto-mount.ts");
/* harmony import */ var _core_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./core/components */ "./src/ts/core/components/LicensePanel.tsx");
/* harmony import */ var _core_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./core/components */ "./src/ts/core/components/LicenseForm.tsx");
/* harmony import */ var _core_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./core/components */ "./src/ts/core/components/ProBadge.tsx");
/* harmony import */ var _core_api_LicenseAPIAdapter__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./core/api/LicenseAPIAdapter */ "./src/ts/core/api/LicenseAPIAdapter.ts");
/* harmony import */ var _core_hooks_useLicense__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./core/hooks/useLicense */ "./src/ts/core/hooks/useLicense.ts");

/** Initialize on DOM ready */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _core_auto_mount__WEBPACK_IMPORTED_MODULE_0__.autoMountComponents);
}
else {
    (0,_core_auto_mount__WEBPACK_IMPORTED_MODULE_0__.autoMountComponents)();
}
/** Export components for programmatic use */






})();

__webpack_exports__ = __webpack_exports__["default"];
/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=index.umd.js.map