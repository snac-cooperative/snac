(function(e, a) { for(var i in a) e[i] = a[i]; }(window, /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */,
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"vocab_select_replace\", function() { return vocab_select_replace; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"geoPlaceSearchResults\", function() { return geoPlaceSearchResults; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"geovocab_select_replace\", function() { return geovocab_select_replace; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"scm_source_select_replace\", function() { return scm_source_select_replace; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"select_replace_simple\", function() { return select_replace_simple; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"sayHi\", function() { return sayHi; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"sayBye\", function() { return sayBye; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"loadVocabSelectOptions\", function() { return loadVocabSelectOptions; });\n/**\n * Select Box Loaders\n *\n * Functions that can be used to replace select boxes on the edit page with\n * pretty-formatted versions using JQuery and Select2\n *\n * @author Robbie Hott\n * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause\n * @copyright 2015 the Rector and Visitors of the University of Virginia, and\n *            the Regents of the University of California\n */\n\n/**\n * Replace a select that is linked to a Vocabulary search\n *\n * Replaces the select with a select2 object capable of making AJAX queries\n *\n * @param  JQuery selectItem The JQuery item to replace\n * @param  string idMatch    ID string for the object on the page\n * @param  string type       The type of the vocabulary term\n * @param  int    minLength  The minimum required length of the autocomplete search\n */\nfunction vocab_select_replace(selectItem, idMatch, type, minLength) {\n    if (minLength === undefined) {\n        minLength = 2;\n    }\n\n    if (selectItem.attr('id').endsWith(idMatch) && !selectItem.attr('id').endsWith(\"ZZ\")) {\n        selectItem.select2({\n            ajax: {\n                url: function () {\n                    var query = snacUrl + \"/vocabulary?type=\" + type + \"&id=\";\n                    query += $(\"#constellationid\").val() + \"&version=\" + $(\"#version\").val();\n                    query += \"&entity_type=\" + $(\"#entityType\").val();\n                    return query;\n                },\n                dataType: 'json',\n                delay: 250,\n                data: function (params) {\n                    return {\n                        q: params.term,\n                        page: params.page\n                    };\n                },\n                processResults: function (data, page) {\n                    return { results: data.results };\n                },\n                cache: true\n            },\n            width: '100%',\n            minimumInputLength: minLength,\n            allowClear: true,\n            theme: 'bootstrap',\n            placeholder: 'Select'\n        });\n    }\n}\n\nvar geoPlaceSearchResults = null;\n\nfunction geovocab_select_replace(selectItem, idMatch) {\n    var minLength = 2;\n\n    if (selectItem.attr('id').endsWith(idMatch) && !selectItem.attr('id').endsWith(\"ZZ\")) {\n        selectItem.select2({\n            ajax: {\n                url: function () {\n                    var query = snacUrl + \"/vocabulary?type=geo_place&format=term\";\n                    query += \"&entity_type=\" + $(\"#entityType\").val();\n                    return query;\n                },\n                dataType: 'json',\n                delay: 250,\n                data: function (params) {\n                    return {\n                        q: params.term,\n                        page: params.page\n                    };\n                },\n                processResults: function (data, page) {\n                    if (data.results) {\n                        geoPlaceSearchResults = data.results;\n                        var selectResults = new Array();\n                        data.results.forEach(function (result) {\n                            selectResults.push({\n                                id: result.id,\n                                text: result.name + \" (\" + result.administrationCode + \", \" + result.countryCode + \")\"\n                            });\n                        });\n                        return { results: selectResults };\n                    }\n                    return { results: null };\n                },\n                cache: true\n            },\n            width: '100%',\n            minimumInputLength: minLength,\n            allowClear: true,\n            theme: 'bootstrap',\n            placeholder: 'Select'\n        });\n    }\n}\n\nvar lastSourceSearchResults = null;\n\n/**\n * Add <br> helper script\n *\n * Adds <br> to strings so that they can be shown to the user in HTML\n * after being input into a text-only field.\n */\nfunction addbr(str) {\n    if (typeof str !== 'undefined' && str !== null) {\n        return (str + '').replace(/(\\r\\n|\\n\\r|\\r|\\n)/g, '<br>' + '$1');\n    }\n    return '';\n}\n\n/**\n * Replace a select that is linked to a Constellation Source search\n *\n * Replaces the select with a select2 object capable of making AJAX queries\n *\n * @param  JQuery selectItem The JQuery item to replace\n * @param  string idMatch    ID string for the object on the page\n */\nfunction scm_source_select_replace(selectItem, idMatch) {\n    if (selectItem.attr('id').endsWith(idMatch) && !selectItem.attr('id').endsWith(\"ZZ\")) {\n        selectItem.select2({\n            ajax: {\n                url: function () {\n                    var query = snacUrl + \"/vocabulary?type=ic_sources&id=\";\n                    query += $(\"#constellationid\").val() + \"&version=\" + $(\"#version\").val();\n                    query += \"&entity_type=\" + $(\"#entityType\").val();\n                    return query;\n                },\n                dataType: 'json',\n                delay: 250,\n                data: function (params) {\n                    return {\n                        q: params.term,\n                        page: params.page\n                    };\n                },\n                processResults: function (data, page) {\n                    // Modify the results to be in the format we want\n                    lastSourceSearchResults = data.results;\n                    // need id, text\n                    var results = new Array();\n                    data.results.forEach(function (res) {\n                        results.push({ id: res.id, text: res.displayName });\n                    });\n                    return { results: results };\n                },\n                cache: true\n            },\n            width: '100%',\n            minimumInputLength: 0,\n            allowClear: true,\n            theme: 'bootstrap',\n            placeholder: 'Select'\n        });\n\n        selectItem.on('change', function (evt) {\n            // TODO: Get the current selected value and update the well in the page to reflect it!\n            // Note: all the selections are available in the global lastSourceSearchResults variable.\n            var sourceID = $(this).val();\n            var inPageID = $(this).attr(\"id\");\n            var idArray = inPageID.split(\"_\");\n            if (idArray.length >= 6) {\n                var i = idArray[5];\n                var j = idArray[4];\n                var shortName = idArray[1];\n                lastSourceSearchResults.forEach(function (source) {\n                    if (source.id == sourceID) {\n                        // Update the text of the source\n                        if (typeof source.text !== 'undefined') {\n                            $(\"#scm_\" + shortName + \"_source_text_\" + j + \"_\" + i).html(addbr(source.text)).removeClass('hidden');\n                            $(\"#scm_\" + shortName + \"_source_text_\" + j + \"_\" + i).closest(\".panel-body\").removeClass('hidden');\n                        } else {\n                            $(\"#scm_\" + shortName + \"_source_text_\" + j + \"_\" + i).text(\"\").addClass('hidden');\n                            $(\"#scm_\" + shortName + \"_source_text_\" + j + \"_\" + i).closest(\".panel-body\").addClass('hidden');\n                        }\n                        // Update the URI of the source\n                        if (typeof source.uri !== 'undefined') $(\"#scm_\" + shortName + \"_source_uri_\" + j + \"_\" + i).html('<a href=\"' + source.uri + '\" target=\"_blank\">' + source.uri + '</a>');else $(\"#scm_\" + shortName + \"_source_uri_\" + j + \"_\" + i).html('');\n                        // Update the URI of the source\n                        if (typeof source.citation !== 'undefined') $(\"#scm_\" + shortName + \"_source_citation_\" + j + \"_\" + i).html(source.citation).removeClass('hidden');else $(\"#scm_\" + shortName + \"_source_citation_\" + j + \"_\" + i).html('').addClass('hidden');\n                    }\n                });\n            }\n        });\n    }\n}\n\n/**\n * Replace a select that is linked to an affiliation search\n *\n * Replaces the select with a select2 object capable of making AJAX queries\n *\n * @param  JQuery selectItem The JQuery item to replace\n */\nfunction affiliation_select_replace(selectItem) {\n    $.get(snacUrl + \"/vocabulary?type=affiliation\").done(function (data) {\n        var options = data.results;\n        selectItem.select2({\n            data: options,\n            allowClear: true,\n            theme: \"bootstrap\",\n            placeholder: \"Select Affiliation\"\n        });\n    });\n}\n\nfunction reviewer_select_replace(selectItem) {\n    if (selectItem != null) {\n        selectItem.select2({\n            placeholder: \"Reviewer Name or Email...\",\n            ajax: {\n                url: function () {\n                    var query = snacUrl + \"/user_search?role=Reviewer\";\n                    return query;\n                },\n                dataType: 'json',\n                delay: 250,\n                data: function (params) {\n                    return {\n                        q: params.term,\n                        page: params.page\n                    };\n                },\n                processResults: function (data, page) {\n                    return { results: data.results };\n                },\n                cache: true\n            },\n            width: '100%',\n            minimumInputLength: 1,\n            allowClear: false,\n            theme: 'bootstrap'\n        });\n    }\n}\n\nfunction select_replace(selectItem, idMatch) {\n    if (selectItem.attr('id').endsWith(idMatch) && !selectItem.attr('id').endsWith(\"ZZ\")) {\n        selectItem.select2({\n            allowClear: true,\n            theme: 'bootstrap'\n        });\n    }\n}\n\nfunction select_replace_simple(selectItem) {\n    selectItem.select2({\n        width: '100%',\n        allowClear: true,\n        theme: 'bootstrap'\n    });\n}\n\nfunction sayHi(user) {\n    return `Hello, ${user}!`;\n}\n\nfunction sayBye(user) {\n    return `Bye bye, ${user}!`;\n}\n\n/**\n * Load Vocab Select Options\n *\n * Replaces the select with a select2 object preloaded with an array of options\n *\n * @param  JQuery selectItem The JQuery item to replace\n * @param  string type       The type of the vocabulary term\n * @param  string type       Text placeholder for select\n */\nfunction loadVocabSelectOptions(selectItem, type, placeholder) {\n    return $.get(snacUrl + \"/vocabulary?type=\" + type).done(function (data) {\n        var options = data.results;\n        selectItem.select2({\n            data: options,\n            allowClear: false,\n            theme: 'bootstrap',\n            placeholder: placeholder\n        });\n    });\n}\n\n/**\n * Replace all the selects that exist on the page when the page has finished loading\n */\n$(document).ready(function () {\n\n    // Use select2 to display the select dropdowns\n    // rather than the HTML default\n    $(\"select\").each(function () {\n        if (typeof $(this).attr('id') !== typeof undefined && $(this).attr('id') !== false) {\n            // Replace the subject selects\n            vocab_select_replace($(this), \"language_language_\", \"language_code\", 1);\n\n            // Replace the subject selects\n            vocab_select_replace($(this), \"language_script_\", \"script_code\", 1);\n\n            // Replace the subject selects\n            vocab_select_replace($(this), \"subject_\", \"subject\", 4);\n\n            // Replace the function selects\n            vocab_select_replace($(this), \"function_\", \"function\", 4);\n\n            // Replace the occupation selects\n            vocab_select_replace($(this), \"occupation_\", \"occupation\", 4);\n\n            // Replace the entityType select\n            vocab_select_replace($(this), \"entityType\", \"entity_type\", 0);\n        }\n    });\n\n    // Replace the Affiliation dropdowns, if one exists\n    if ($(\"#affiliationid\").exists()) affiliation_select_replace($(\"#affiliationid\"));\n\n    // Replace the User search dropdown, if one exists\n    if ($(\"#reviewersearchbox\").exists()) reviewer_select_replace($(\"#reviewersearchbox\"));\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiMS5qcyIsInNvdXJjZXMiOlsid2VicGFjazovLy9zcmMvdmlydHVhbGhvc3RzL3d3dy9qYXZhc2NyaXB0L3NyYy9zZWxlY3RfbG9hZGVycy5qcz8zOGJlIl0sInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogU2VsZWN0IEJveCBMb2FkZXJzXG4gKlxuICogRnVuY3Rpb25zIHRoYXQgY2FuIGJlIHVzZWQgdG8gcmVwbGFjZSBzZWxlY3QgYm94ZXMgb24gdGhlIGVkaXQgcGFnZSB3aXRoXG4gKiBwcmV0dHktZm9ybWF0dGVkIHZlcnNpb25zIHVzaW5nIEpRdWVyeSBhbmQgU2VsZWN0MlxuICpcbiAqIEBhdXRob3IgUm9iYmllIEhvdHRcbiAqIEBsaWNlbnNlIGh0dHBzOi8vb3BlbnNvdXJjZS5vcmcvbGljZW5zZXMvQlNELTMtQ2xhdXNlIEJTRCAzLUNsYXVzZVxuICogQGNvcHlyaWdodCAyMDE1IHRoZSBSZWN0b3IgYW5kIFZpc2l0b3JzIG9mIHRoZSBVbml2ZXJzaXR5IG9mIFZpcmdpbmlhLCBhbmRcbiAqICAgICAgICAgICAgdGhlIFJlZ2VudHMgb2YgdGhlIFVuaXZlcnNpdHkgb2YgQ2FsaWZvcm5pYVxuICovXG5cbi8qKlxuICogUmVwbGFjZSBhIHNlbGVjdCB0aGF0IGlzIGxpbmtlZCB0byBhIFZvY2FidWxhcnkgc2VhcmNoXG4gKlxuICogUmVwbGFjZXMgdGhlIHNlbGVjdCB3aXRoIGEgc2VsZWN0MiBvYmplY3QgY2FwYWJsZSBvZiBtYWtpbmcgQUpBWCBxdWVyaWVzXG4gKlxuICogQHBhcmFtICBKUXVlcnkgc2VsZWN0SXRlbSBUaGUgSlF1ZXJ5IGl0ZW0gdG8gcmVwbGFjZVxuICogQHBhcmFtICBzdHJpbmcgaWRNYXRjaCAgICBJRCBzdHJpbmcgZm9yIHRoZSBvYmplY3Qgb24gdGhlIHBhZ2VcbiAqIEBwYXJhbSAgc3RyaW5nIHR5cGUgICAgICAgVGhlIHR5cGUgb2YgdGhlIHZvY2FidWxhcnkgdGVybVxuICogQHBhcmFtICBpbnQgICAgbWluTGVuZ3RoICBUaGUgbWluaW11bSByZXF1aXJlZCBsZW5ndGggb2YgdGhlIGF1dG9jb21wbGV0ZSBzZWFyY2hcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIHZvY2FiX3NlbGVjdF9yZXBsYWNlKHNlbGVjdEl0ZW0sIGlkTWF0Y2gsIHR5cGUsIG1pbkxlbmd0aCkge1xuICAgIGlmIChtaW5MZW5ndGggPT09IHVuZGVmaW5lZCkge1xuICAgICAgICBtaW5MZW5ndGggPSAyO1xuICAgIH1cblxuICAgICAgICBpZihzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoaWRNYXRjaClcbiAgICAgICAgICAgICYmICFzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoXCJaWlwiKSkge1xuICAgICAgICAgICAgICAgIHNlbGVjdEl0ZW0uc2VsZWN0Mih7XG4gICAgICAgICAgICAgICAgICAgIGFqYXg6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHVybDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHF1ZXJ5ID0gc25hY1VybCArIFwiL3ZvY2FidWxhcnk/dHlwZT1cIit0eXBlK1wiJmlkPVwiO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBxdWVyeSArPSAkKFwiI2NvbnN0ZWxsYXRpb25pZFwiKS52YWwoKStcIiZ2ZXJzaW9uPVwiKyQoXCIjdmVyc2lvblwiKS52YWwoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcXVlcnkgKz0gXCImZW50aXR5X3R5cGU9XCIrJChcIiNlbnRpdHlUeXBlXCIpLnZhbCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gcXVlcnk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0YVR5cGU6ICdqc29uJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGRlbGF5OiAyNTAsXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcTogcGFyYW1zLnRlcm0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBhZ2U6IHBhcmFtcy5wYWdlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBwcm9jZXNzUmVzdWx0czogZnVuY3Rpb24gKGRhdGEsIHBhZ2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4geyByZXN1bHRzOiBkYXRhLnJlc3VsdHMgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBjYWNoZTogdHJ1ZVxuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICAgICAgICAgICAgICBtaW5pbXVtSW5wdXRMZW5ndGg6IG1pbkxlbmd0aCxcbiAgICAgICAgICAgICAgICAgICAgYWxsb3dDbGVhcjogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgdGhlbWU6ICdib290c3RyYXAnLFxuICAgICAgICAgICAgICAgICAgICBwbGFjZWhvbGRlcjogJ1NlbGVjdCdcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cbn1cblxuZXhwb3J0IHZhciBnZW9QbGFjZVNlYXJjaFJlc3VsdHMgPSBudWxsO1xuXG5leHBvcnQgZnVuY3Rpb24gZ2Vvdm9jYWJfc2VsZWN0X3JlcGxhY2Uoc2VsZWN0SXRlbSwgaWRNYXRjaCkge1xuICAgIHZhciBtaW5MZW5ndGggPSAyO1xuXG4gICAgaWYoc2VsZWN0SXRlbS5hdHRyKCdpZCcpLmVuZHNXaXRoKGlkTWF0Y2gpXG4gICAgICAgICYmICFzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoXCJaWlwiKSkge1xuICAgICAgICAgICAgc2VsZWN0SXRlbS5zZWxlY3QyKHtcbiAgICAgICAgICAgICAgICBhamF4OiB7XG4gICAgICAgICAgICAgICAgICAgIHVybDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgcXVlcnkgPSBzbmFjVXJsK1wiL3ZvY2FidWxhcnk/dHlwZT1nZW9fcGxhY2UmZm9ybWF0PXRlcm1cIjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBxdWVyeSArPSBcIiZlbnRpdHlfdHlwZT1cIiskKFwiI2VudGl0eVR5cGVcIikudmFsKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHF1ZXJ5O1xuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgICAgICBkZWxheTogMjUwLFxuICAgICAgICAgICAgICAgICAgICBkYXRhOiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHE6IHBhcmFtcy50ZXJtLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBhZ2U6IHBhcmFtcy5wYWdlXG4gICAgICAgICAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICBwcm9jZXNzUmVzdWx0czogZnVuY3Rpb24gKGRhdGEsIHBhZ2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChkYXRhLnJlc3VsdHMpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBnZW9QbGFjZVNlYXJjaFJlc3VsdHMgPSBkYXRhLnJlc3VsdHM7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNlbGVjdFJlc3VsdHMgPSBuZXcgQXJyYXkoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhLnJlc3VsdHMuZm9yRWFjaChmdW5jdGlvbihyZXN1bHQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0UmVzdWx0cy5wdXNoKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlkOiByZXN1bHQuaWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiByZXN1bHQubmFtZSArIFwiIChcIiArIHJlc3VsdC5hZG1pbmlzdHJhdGlvbkNvZGUgKyBcIiwgXCIgKyByZXN1bHQuY291bnRyeUNvZGUrIFwiKVwiXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHtyZXN1bHRzOiBzZWxlY3RSZXN1bHRzfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB7IHJlc3VsdHM6IG51bGwgfTtcbiAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgY2FjaGU6IHRydWVcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHdpZHRoOiAnMTAwJScsXG4gICAgICAgICAgICAgICAgbWluaW11bUlucHV0TGVuZ3RoOiBtaW5MZW5ndGgsXG4gICAgICAgICAgICAgICAgYWxsb3dDbGVhcjogdHJ1ZSxcbiAgICAgICAgICAgICAgICB0aGVtZTogJ2Jvb3RzdHJhcCcsXG4gICAgICAgICAgICAgICAgcGxhY2Vob2xkZXI6ICdTZWxlY3QnXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxufVxuXG52YXIgbGFzdFNvdXJjZVNlYXJjaFJlc3VsdHMgPSBudWxsO1xuXG4vKipcbiAqIEFkZCA8YnI+IGhlbHBlciBzY3JpcHRcbiAqXG4gKiBBZGRzIDxicj4gdG8gc3RyaW5ncyBzbyB0aGF0IHRoZXkgY2FuIGJlIHNob3duIHRvIHRoZSB1c2VyIGluIEhUTUxcbiAqIGFmdGVyIGJlaW5nIGlucHV0IGludG8gYSB0ZXh0LW9ubHkgZmllbGQuXG4gKi9cbmZ1bmN0aW9uIGFkZGJyKHN0cikge1xuICAgIGlmICh0eXBlb2Ygc3RyICE9PSAndW5kZWZpbmVkJyAmJiBzdHIgIT09IG51bGwpIHtcbiAgICAgICAgcmV0dXJuIChzdHIgKyAnJykucmVwbGFjZSgvKFxcclxcbnxcXG5cXHJ8XFxyfFxcbikvZywgJzxicj4nICsgJyQxJyk7XG4gICAgfVxuICAgIHJldHVybiAnJztcbn1cblxuLyoqXG4gKiBSZXBsYWNlIGEgc2VsZWN0IHRoYXQgaXMgbGlua2VkIHRvIGEgQ29uc3RlbGxhdGlvbiBTb3VyY2Ugc2VhcmNoXG4gKlxuICogUmVwbGFjZXMgdGhlIHNlbGVjdCB3aXRoIGEgc2VsZWN0MiBvYmplY3QgY2FwYWJsZSBvZiBtYWtpbmcgQUpBWCBxdWVyaWVzXG4gKlxuICogQHBhcmFtICBKUXVlcnkgc2VsZWN0SXRlbSBUaGUgSlF1ZXJ5IGl0ZW0gdG8gcmVwbGFjZVxuICogQHBhcmFtICBzdHJpbmcgaWRNYXRjaCAgICBJRCBzdHJpbmcgZm9yIHRoZSBvYmplY3Qgb24gdGhlIHBhZ2VcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIHNjbV9zb3VyY2Vfc2VsZWN0X3JlcGxhY2Uoc2VsZWN0SXRlbSwgaWRNYXRjaCkge1xuICAgICAgICBpZihzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoaWRNYXRjaClcbiAgICAgICAgICAgICYmICFzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoXCJaWlwiKSkge1xuICAgICAgICAgICAgICAgIHNlbGVjdEl0ZW0uc2VsZWN0Mih7XG4gICAgICAgICAgICAgICAgICAgIGFqYXg6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHVybDogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHF1ZXJ5ID0gc25hY1VybCtcIi92b2NhYnVsYXJ5P3R5cGU9aWNfc291cmNlcyZpZD1cIjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcXVlcnkgKz0gJChcIiNjb25zdGVsbGF0aW9uaWRcIikudmFsKCkrXCImdmVyc2lvbj1cIiskKFwiI3ZlcnNpb25cIikudmFsKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHF1ZXJ5ICs9IFwiJmVudGl0eV90eXBlPVwiKyQoXCIjZW50aXR5VHlwZVwiKS52YWwoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHF1ZXJ5O1xuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG4gICAgICAgICAgICAgICAgICAgICAgICBkZWxheTogMjUwLFxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0YTogZnVuY3Rpb24gKHBhcmFtcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHE6IHBhcmFtcy50ZXJtLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwYWdlOiBwYXJhbXMucGFnZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgcHJvY2Vzc1Jlc3VsdHM6IGZ1bmN0aW9uIChkYXRhLCBwYWdlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gTW9kaWZ5IHRoZSByZXN1bHRzIHRvIGJlIGluIHRoZSBmb3JtYXQgd2Ugd2FudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxhc3RTb3VyY2VTZWFyY2hSZXN1bHRzID0gZGF0YS5yZXN1bHRzO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIG5lZWQgaWQsIHRleHRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgcmVzdWx0cyA9IG5ldyBBcnJheSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEucmVzdWx0cy5mb3JFYWNoKGZ1bmN0aW9uKHJlcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXN1bHRzLnB1c2goe2lkOiByZXMuaWQsIHRleHQ6IHJlcy5kaXNwbGF5TmFtZX0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB7IHJlc3VsdHM6IHJlc3VsdHMgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBjYWNoZTogdHJ1ZVxuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICAgICAgICAgICAgICBtaW5pbXVtSW5wdXRMZW5ndGg6IDAsXG4gICAgICAgICAgICAgICAgICAgIGFsbG93Q2xlYXI6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHRoZW1lOiAnYm9vdHN0cmFwJyxcbiAgICAgICAgICAgICAgICAgICAgcGxhY2Vob2xkZXI6ICdTZWxlY3QnXG4gICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIHNlbGVjdEl0ZW0ub24oJ2NoYW5nZScsIGZ1bmN0aW9uIChldnQpIHtcbiAgICAgICAgICAgICAgICAvLyBUT0RPOiBHZXQgdGhlIGN1cnJlbnQgc2VsZWN0ZWQgdmFsdWUgYW5kIHVwZGF0ZSB0aGUgd2VsbCBpbiB0aGUgcGFnZSB0byByZWZsZWN0IGl0IVxuICAgICAgICAgICAgICAgIC8vIE5vdGU6IGFsbCB0aGUgc2VsZWN0aW9ucyBhcmUgYXZhaWxhYmxlIGluIHRoZSBnbG9iYWwgbGFzdFNvdXJjZVNlYXJjaFJlc3VsdHMgdmFyaWFibGUuXG4gICAgICAgICAgICAgICAgdmFyIHNvdXJjZUlEID0gJCh0aGlzKS52YWwoKTtcbiAgICAgICAgICAgICAgICB2YXIgaW5QYWdlSUQgPSAkKHRoaXMpLmF0dHIoXCJpZFwiKTtcbiAgICAgICAgICAgICAgICB2YXIgaWRBcnJheSA9IGluUGFnZUlELnNwbGl0KFwiX1wiKTtcbiAgICAgICAgICAgICAgICBpZiAoaWRBcnJheS5sZW5ndGggPj0gNikge1xuICAgICAgICAgICAgICAgICAgICB2YXIgaSA9IGlkQXJyYXlbNV07XG4gICAgICAgICAgICAgICAgICAgIHZhciBqID0gaWRBcnJheVs0XTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIHNob3J0TmFtZSA9IGlkQXJyYXlbMV07XG4gICAgICAgICAgICAgICAgICAgIGxhc3RTb3VyY2VTZWFyY2hSZXN1bHRzLmZvckVhY2goZnVuY3Rpb24oc291cmNlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoc291cmNlLmlkID09IHNvdXJjZUlEKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gVXBkYXRlIHRoZSB0ZXh0IG9mIHRoZSBzb3VyY2VcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIHNvdXJjZS50ZXh0ICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiI3NjbV9cIiArIHNob3J0TmFtZSArIFwiX3NvdXJjZV90ZXh0X1wiICsgaiArIFwiX1wiICsgaSkuaHRtbChhZGRicihzb3VyY2UudGV4dCkpLnJlbW92ZUNsYXNzKCdoaWRkZW4nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChcIiNzY21fXCIgKyBzaG9ydE5hbWUgKyBcIl9zb3VyY2VfdGV4dF9cIiArIGogKyBcIl9cIiArIGkpLmNsb3Nlc3QoXCIucGFuZWwtYm9keVwiKS5yZW1vdmVDbGFzcygnaGlkZGVuJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChcIiNzY21fXCIgKyBzaG9ydE5hbWUgKyBcIl9zb3VyY2VfdGV4dF9cIiArIGogKyBcIl9cIiArIGkpLnRleHQoXCJcIikuYWRkQ2xhc3MoJ2hpZGRlbicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiI3NjbV9cIiArIHNob3J0TmFtZSArIFwiX3NvdXJjZV90ZXh0X1wiICsgaiArIFwiX1wiICsgaSkuY2xvc2VzdChcIi5wYW5lbC1ib2R5XCIpLmFkZENsYXNzKCdoaWRkZW4nKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBVcGRhdGUgdGhlIFVSSSBvZiB0aGUgc291cmNlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBzb3VyY2UudXJpICE9PSAndW5kZWZpbmVkJylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJChcIiNzY21fXCIgKyBzaG9ydE5hbWUgKyBcIl9zb3VyY2VfdXJpX1wiICsgaiArIFwiX1wiICsgaSkuaHRtbCgnPGEgaHJlZj1cIicrc291cmNlLnVyaSsnXCIgdGFyZ2V0PVwiX2JsYW5rXCI+Jytzb3VyY2UudXJpKyc8L2E+Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxzZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiI3NjbV9cIiArIHNob3J0TmFtZSArIFwiX3NvdXJjZV91cmlfXCIgKyBqICsgXCJfXCIgKyBpKS5odG1sKCcnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBVcGRhdGUgdGhlIFVSSSBvZiB0aGUgc291cmNlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBzb3VyY2UuY2l0YXRpb24gIT09ICd1bmRlZmluZWQnKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiI3NjbV9cIiArIHNob3J0TmFtZSArIFwiX3NvdXJjZV9jaXRhdGlvbl9cIiArIGogKyBcIl9cIiArIGkpLmh0bWwoc291cmNlLmNpdGF0aW9uKS5yZW1vdmVDbGFzcygnaGlkZGVuJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxzZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKFwiI3NjbV9cIiArIHNob3J0TmFtZSArIFwiX3NvdXJjZV9jaXRhdGlvbl9cIiArIGogKyBcIl9cIiArIGkpLmh0bWwoJycpLmFkZENsYXNzKCdoaWRkZW4nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgfVxufVxuXG4vKipcbiAqIFJlcGxhY2UgYSBzZWxlY3QgdGhhdCBpcyBsaW5rZWQgdG8gYW4gYWZmaWxpYXRpb24gc2VhcmNoXG4gKlxuICogUmVwbGFjZXMgdGhlIHNlbGVjdCB3aXRoIGEgc2VsZWN0MiBvYmplY3QgY2FwYWJsZSBvZiBtYWtpbmcgQUpBWCBxdWVyaWVzXG4gKlxuICogQHBhcmFtICBKUXVlcnkgc2VsZWN0SXRlbSBUaGUgSlF1ZXJ5IGl0ZW0gdG8gcmVwbGFjZVxuICovXG5mdW5jdGlvbiBhZmZpbGlhdGlvbl9zZWxlY3RfcmVwbGFjZShzZWxlY3RJdGVtKSB7XG4gICAgJC5nZXQoc25hY1VybCArIFwiL3ZvY2FidWxhcnk/dHlwZT1hZmZpbGlhdGlvblwiKS5kb25lKGZ1bmN0aW9uKGRhdGEpIHtcbiAgICAgICAgdmFyIG9wdGlvbnMgPSBkYXRhLnJlc3VsdHM7XG4gICAgICAgIHNlbGVjdEl0ZW0uc2VsZWN0Mih7XG4gICAgICAgICAgICBkYXRhOiBvcHRpb25zLFxuICAgICAgICAgICAgYWxsb3dDbGVhcjogdHJ1ZSxcbiAgICAgICAgICAgIHRoZW1lOiBcImJvb3RzdHJhcFwiLFxuICAgICAgICAgICAgcGxhY2Vob2xkZXI6IFwiU2VsZWN0IEFmZmlsaWF0aW9uXCJcbiAgICAgICAgfSk7XG4gICAgfSk7XG59XG5cbmZ1bmN0aW9uIHJldmlld2VyX3NlbGVjdF9yZXBsYWNlKHNlbGVjdEl0ZW0pIHtcbiAgICAgICAgaWYoc2VsZWN0SXRlbSAhPSBudWxsKSB7XG4gICAgICAgICAgICAgICAgc2VsZWN0SXRlbS5zZWxlY3QyKHtcbiAgICAgICAgICAgICAgICAgICAgcGxhY2Vob2xkZXI6IFwiUmV2aWV3ZXIgTmFtZSBvciBFbWFpbC4uLlwiLFxuICAgICAgICAgICAgICAgICAgICBhamF4OiB7XG4gICAgICAgICAgICAgICAgICAgICAgICB1cmw6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBxdWVyeSA9IHNuYWNVcmwrXCIvdXNlcl9zZWFyY2g/cm9sZT1SZXZpZXdlclwiO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gcXVlcnk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0YVR5cGU6ICdqc29uJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGRlbGF5OiAyNTAsXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRhOiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcTogcGFyYW1zLnRlcm0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBhZ2U6IHBhcmFtcy5wYWdlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBwcm9jZXNzUmVzdWx0czogZnVuY3Rpb24gKGRhdGEsIHBhZ2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4geyByZXN1bHRzOiBkYXRhLnJlc3VsdHMgfTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBjYWNoZTogdHJ1ZVxuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICAgICAgICAgICAgICBtaW5pbXVtSW5wdXRMZW5ndGg6IDEsXG4gICAgICAgICAgICAgICAgICAgIGFsbG93Q2xlYXI6IGZhbHNlLFxuICAgICAgICAgICAgICAgICAgICB0aGVtZTogJ2Jvb3RzdHJhcCdcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cbn1cblxuZnVuY3Rpb24gc2VsZWN0X3JlcGxhY2Uoc2VsZWN0SXRlbSwgaWRNYXRjaCkge1xuICAgICAgICBpZihzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoaWRNYXRjaClcbiAgICAgICAgICAgICYmICFzZWxlY3RJdGVtLmF0dHIoJ2lkJykuZW5kc1dpdGgoXCJaWlwiKSkge1xuICAgICAgICAgICAgICAgIHNlbGVjdEl0ZW0uc2VsZWN0Mih7XG4gICAgICAgICAgICAgICAgICAgIGFsbG93Q2xlYXI6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHRoZW1lOiAnYm9vdHN0cmFwJ1xuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxufVxuXG5leHBvcnQgZnVuY3Rpb24gc2VsZWN0X3JlcGxhY2Vfc2ltcGxlKHNlbGVjdEl0ZW0pIHtcbiAgICBzZWxlY3RJdGVtLnNlbGVjdDIoe1xuICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICBhbGxvd0NsZWFyOiB0cnVlLFxuICAgICAgICB0aGVtZTogJ2Jvb3RzdHJhcCdcbiAgICB9KTtcbn1cblxuXG5cbmV4cG9ydCBmdW5jdGlvbiBzYXlIaSh1c2VyKSB7XG4gIHJldHVybiBgSGVsbG8sICR7dXNlcn0hYDtcbn1cblxuZXhwb3J0IGZ1bmN0aW9uIHNheUJ5ZSh1c2VyKSB7XG4gIHJldHVybiBgQnllIGJ5ZSwgJHt1c2VyfSFgO1xufVxuXG4vKipcbiAqIExvYWQgVm9jYWIgU2VsZWN0IE9wdGlvbnNcbiAqXG4gKiBSZXBsYWNlcyB0aGUgc2VsZWN0IHdpdGggYSBzZWxlY3QyIG9iamVjdCBwcmVsb2FkZWQgd2l0aCBhbiBhcnJheSBvZiBvcHRpb25zXG4gKlxuICogQHBhcmFtICBKUXVlcnkgc2VsZWN0SXRlbSBUaGUgSlF1ZXJ5IGl0ZW0gdG8gcmVwbGFjZVxuICogQHBhcmFtICBzdHJpbmcgdHlwZSAgICAgICBUaGUgdHlwZSBvZiB0aGUgdm9jYWJ1bGFyeSB0ZXJtXG4gKiBAcGFyYW0gIHN0cmluZyB0eXBlICAgICAgIFRleHQgcGxhY2Vob2xkZXIgZm9yIHNlbGVjdFxuICovXG5leHBvcnQgZnVuY3Rpb24gbG9hZFZvY2FiU2VsZWN0T3B0aW9ucyhzZWxlY3RJdGVtLCB0eXBlLCBwbGFjZWhvbGRlcikge1xuICAgIHJldHVybiAkLmdldChzbmFjVXJsICsgXCIvdm9jYWJ1bGFyeT90eXBlPVwiICsgdHlwZSlcbiAgICAuZG9uZShmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgIHZhciBvcHRpb25zID0gZGF0YS5yZXN1bHRzO1xuICAgICAgICBzZWxlY3RJdGVtLnNlbGVjdDIoe1xuICAgICAgICAgICAgZGF0YTogb3B0aW9ucyxcbiAgICAgICAgICAgIGFsbG93Q2xlYXI6IGZhbHNlLFxuICAgICAgICAgICAgdGhlbWU6ICdib290c3RyYXAnLFxuICAgICAgICAgICAgcGxhY2Vob2xkZXI6IHBsYWNlaG9sZGVyXG4gICAgICAgIH0pO1xuICAgIH0pO1xufVxuXG4vKipcbiAqIFJlcGxhY2UgYWxsIHRoZSBzZWxlY3RzIHRoYXQgZXhpc3Qgb24gdGhlIHBhZ2Ugd2hlbiB0aGUgcGFnZSBoYXMgZmluaXNoZWQgbG9hZGluZ1xuICovXG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHtcblxuICAgIC8vIFVzZSBzZWxlY3QyIHRvIGRpc3BsYXkgdGhlIHNlbGVjdCBkcm9wZG93bnNcbiAgICAvLyByYXRoZXIgdGhhbiB0aGUgSFRNTCBkZWZhdWx0XG4gICAgJChcInNlbGVjdFwiKS5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICBpZiAodHlwZW9mICQodGhpcykuYXR0cignaWQnKSAhPT0gdHlwZW9mIHVuZGVmaW5lZCAmJiAkKHRoaXMpLmF0dHIoJ2lkJykgIT09IGZhbHNlKSB7XG4gICAgICAgICAgICAvLyBSZXBsYWNlIHRoZSBzdWJqZWN0IHNlbGVjdHNcbiAgICAgICAgICAgIHZvY2FiX3NlbGVjdF9yZXBsYWNlKCQodGhpcyksIFwibGFuZ3VhZ2VfbGFuZ3VhZ2VfXCIsIFwibGFuZ3VhZ2VfY29kZVwiLCAxKTtcblxuICAgICAgICAgICAgLy8gUmVwbGFjZSB0aGUgc3ViamVjdCBzZWxlY3RzXG4gICAgICAgICAgICB2b2NhYl9zZWxlY3RfcmVwbGFjZSgkKHRoaXMpLCBcImxhbmd1YWdlX3NjcmlwdF9cIiwgXCJzY3JpcHRfY29kZVwiLCAxKTtcblxuICAgICAgICAgICAgLy8gUmVwbGFjZSB0aGUgc3ViamVjdCBzZWxlY3RzXG4gICAgICAgICAgICB2b2NhYl9zZWxlY3RfcmVwbGFjZSgkKHRoaXMpLCBcInN1YmplY3RfXCIsIFwic3ViamVjdFwiLCA0KTtcblxuICAgICAgICAgICAgLy8gUmVwbGFjZSB0aGUgZnVuY3Rpb24gc2VsZWN0c1xuICAgICAgICAgICAgdm9jYWJfc2VsZWN0X3JlcGxhY2UoJCh0aGlzKSwgXCJmdW5jdGlvbl9cIiwgXCJmdW5jdGlvblwiLCA0KTtcblxuICAgICAgICAgICAgLy8gUmVwbGFjZSB0aGUgb2NjdXBhdGlvbiBzZWxlY3RzXG4gICAgICAgICAgICB2b2NhYl9zZWxlY3RfcmVwbGFjZSgkKHRoaXMpLCBcIm9jY3VwYXRpb25fXCIsIFwib2NjdXBhdGlvblwiLCA0KTtcblxuICAgICAgICAgICAgLy8gUmVwbGFjZSB0aGUgZW50aXR5VHlwZSBzZWxlY3RcbiAgICAgICAgICAgIHZvY2FiX3NlbGVjdF9yZXBsYWNlKCQodGhpcyksIFwiZW50aXR5VHlwZVwiLCBcImVudGl0eV90eXBlXCIsIDApO1xuICAgICAgICB9XG4gICAgfSk7XG5cbiAgICAvLyBSZXBsYWNlIHRoZSBBZmZpbGlhdGlvbiBkcm9wZG93bnMsIGlmIG9uZSBleGlzdHNcbiAgICBpZiAoJChcIiNhZmZpbGlhdGlvbmlkXCIpLmV4aXN0cygpKVxuICAgICAgICBhZmZpbGlhdGlvbl9zZWxlY3RfcmVwbGFjZSgkKFwiI2FmZmlsaWF0aW9uaWRcIikpO1xuXG4gICAgLy8gUmVwbGFjZSB0aGUgVXNlciBzZWFyY2ggZHJvcGRvd24sIGlmIG9uZSBleGlzdHNcbiAgICBpZiAoJChcIiNyZXZpZXdlcnNlYXJjaGJveFwiKS5leGlzdHMoKSlcbiAgICAgICAgcmV2aWV3ZXJfc2VsZWN0X3JlcGxhY2UoJChcIiNyZXZpZXdlcnNlYXJjaGJveFwiKSk7XG59KTtcbiJdLCJtYXBwaW5ncyI6IkFBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7Ozs7Ozs7Ozs7OztBQVlBOzs7Ozs7Ozs7O0FBVUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUZBO0FBSUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWxCQTtBQW9CQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBekJBO0FBMkJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBRkE7QUFJQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFGQTtBQUlBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQTVCQTtBQThCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBbkNBO0FBcUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7O0FBTUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7Ozs7QUFRQTtBQUNBO0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUZBO0FBSUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBekJBO0FBMkJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFoQ0E7QUFDQTtBQWtDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUlBO0FBQ0E7QUFJQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBOzs7Ozs7O0FBT0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUpBO0FBTUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBRkE7QUFJQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBaEJBO0FBa0JBO0FBQ0E7QUFDQTtBQUNBO0FBdkJBO0FBeUJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFGQTtBQUlBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFIQTtBQUtBO0FBQ0E7QUFHQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7Ozs7OztBQVNBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFKQTtBQU1BO0FBQ0E7QUFDQTtBQUNBOzs7QUFHQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBRUE7QUFDQTtBQUVBIiwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///1\n");

/***/ })
/******/ ])));