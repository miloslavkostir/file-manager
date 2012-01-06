/**
 * AJAX Nette Framework plugin for jQuery
 *
 * @copyright   Copyright (c) 2009 Jan Marek
 * @license     MIT
 * @link        http://nettephp.com/cs/extras/jquery-ajax
 * @version     0.2
 */

jQuery.extend({
	nette: {
		updateSnippet: function (id, html) {
                        var el = $("#" + id);
                        jQuery.nette.changeContent(el, html);
                        el.trigger("snippetUpdated", [el]);
		},

                changeContent: function (element, content) {
                        element.html(content);
                },

		success: function (payload) {

                        // empty payload
			if (payload === null)
				return;

			// redirect
			if (payload.redirect) {
				window.location.href = payload.redirect;
				return;
			}

			// snippets
                        if (payload.snippets) {
                                for (var i in payload.snippets) {
                                        jQuery.nette.updateSnippet(i, payload.snippets[i]);
                                }
                        }
		}
	}
});

jQuery.ajaxSetup({
	success: jQuery.nette.success,
	dataType: "json"
});