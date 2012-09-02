/*
 * Copyright (c) 2011 Bronislav Sedlák <bronislav.sedlak@gmail.com>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

$(function() {

    $(".file-manager").bind("snippetUpdated", function() {

        /** Main */

        // http://stackoverflow.com/questions/2884221/how-to-start-stop-restart-jquery-animation
        var mb = $(".fm-alert").stop(true, true).fadeIn();
        if(mb.data("delay")) clearTimeout(mb.data("delay"));
        mb.data("delay", setTimeout(function() {
            mb.fadeOut(500);
        }, 15000));

        $(".fm-draggable").draggable({
            revert: true,
            cursor: "pointer",
            helper: "clone",
            scroll: false,
            opacity : 0.6
        });

        $(".fm-droppable").droppable({
            hoverClass: "fm-state-highlight",
            drop: function(event, ui) {
                var filename = ui.draggable.data("filename");
                var targetdir = $(this).data("targetdir");
                var moveUrl = $(this).data("move-url");
                $(this).addClass("fm-state-highlight");
                $.getJSON(moveUrl, {
                    filename: filename,
                    targetdir: targetdir
                }, function (payload) {
                    $.nette.success(payload);
                    if (payload.result === "success") {
                        ui.draggable.remove();
                    }
                });
                $.animateProgress(".file-manager", event);
                $(this).removeClass("fm-state-highlight");
            }
        });

        /** Clipboard */
        $('.fm-clipboard').css({
            position: 'absolute',
            top: $('.fm-toolbar').height()
        });


        /* Filter mask */
        $(".fm-filter input").blur();


        /* Treeview */
        $(".file-manager .filetree").treeview({
            persist: "cookie"
        });

        $(".file-manager .hitarea").hide();


        /* Content */
        $(".fm-content-files ul").shiftClick("li", "selected");

        $.ctrl("A", function() {
            $(".fm-content-file").addClass("selected");
        });

        $(".fm-content-files").selectable({
            filter: ".fm-content-file",
            selecting: function(event, ui) {
                $(ui.selecting).addClass("selected");
            },
            unselecting: function(event, ui) {
                $(ui.unselecting).removeClass("selected");
            }
        });
    });



    /** Main */
    // http://stackoverflow.com/questions/2884221/how-to-start-stop-restart-jquery-animation
    var mb = $(".fm-alert").stop(true, true).fadeIn();
    if(mb.data("delay")) clearTimeout(mb.data("delay"));
    mb.data("delay", setTimeout(function() {
        mb.fadeOut(500);
    }, 15000));

    $(".file-manager").delegate(".fm-show-messages", "click", function() {
        $(".fm-alert-message-text").toggleClass("fm-hide");
    });

    $(".file-manager").delegate(".fm-close", "click", function() {
        $(".fm-alert").remove();
    });

    $(".file-manager").delegate("a.fm-ajax", "click", function(e) {
        $.getJSON(this.href);
        $.animateProgress(".file-manager", e);
        return false;
    });

    $(".file-manager").delegate("a.fm-ajax-dbl", "dblclick", function(e) {
        $.getJSON(this.href);
        $.animateProgress(".file-manager", e);
        return false;
    }).delegate("a.fm-ajax-dbl", "click", function (e){
        return false;
    });

    $(".file-manager").delegate("form.fm-ajax", "submit", function(e) {
        $.animateProgress(".file-manager", e);
        $(this).ajaxSubmit();
        return false;
    });

    $(".file-manager").delegate("form.fm-ajax :submit", "click", function(e) {
        $(this).ajaxSubmit();
        $.animateProgress(".file-manager", e);
        return false;
    });

    $(".file-manager").delegate(".ajax-select-form", "change", function(event) {
        event.preventDefault();
        $(this).ajaxSubmit();
        $.animateProgress(".file-manager", event);
    });

    $(".fm-draggable").draggable({
        revert: true,
        cursor: "pointer",
        helper: "clone",
        scroll: false,
        opacity : 0.6
    });

    $(".fm-droppable").droppable({
        hoverClass: "fm-state-highlight",
        drop: function(event, ui) {
            var filename = ui.draggable.data("filename");
            var targetdir = $(this).data("targetdir");
            var moveUrl = $(this).data("move-url");
            $(this).addClass("fm-state-highlight");
            $.getJSON(moveUrl, {
                filename: filename,
                targetdir: targetdir
            }, function (payload) {
                $.nette.success(payload);
                if (payload.result === "success") {
                    ui.draggable.remove();
                }
            });
            $.animateProgress(".file-manager", event);
            $(this).removeClass("fm-state-highlight");
        }
    });

    /* Filter mask */
    $('body').delegate('.fm-filter input', 'focusin', function() {
        if ($(this).val() == $(this)[0].title) {
            $(this).removeClass('active');
            $(this).val("");
        }
    });

    $('body').delegate('.fm-filter input', 'focusout', function() {
        if ($(this).val() == '') {
            $(this).addClass('active');
            $(this).val($(this)[0].title);
        }
    });

    $(".fm-filter input").blur();


    /* Navigation */
    $('body').delegate('.fm-location input', 'focusin', function() {
        $(".fm-navigation").hide();
        $(this).addClass("active");
    });

    $('body').delegate('.fm-location input', 'focusout', function() {
        $(this).removeClass("active");
        $('.fm-navigation').show();
    });


    /* Treeview */
    $(".file-manager .filetree").treeview({
        persist: "cookie"
    });

    $(".file-manager .hitarea").hide();

    $(".file-manager").delegate('.fm-treeview', 'hover', function(e) {
        if( e.type === 'mouseenter' ) {
            $(".file-manager .hitarea").stop(true, true)
            $(".file-manager .hitarea").show('fade');
        } else
            $(".file-manager .hitarea").hide('fade', 700);
    });


    /** Clipboard */
    $('.fm-clipboard').css({
        position: 'absolute',
        top: $('.fm-toolbar').height()
    });

    $(document).delegate('#fm-clipboard-hide', 'click', function() {
        $('.fm-clipboard').slideToggle('slow');
    });

    $(document).delegate('#show-clipboard', 'click', function() {
        $('.fm-clipboard').slideToggle('slow');
        return false;
    });


    /* Content */
    $(".fm-content-files ul").shiftClick("li", "selected");

    $.ctrl("A", function() {
        $(".fm-content-file").addClass("selected");
    });

    $(".fm-content-files").selectable({
        filter: ".fm-content-file",
        selecting: function(event, ui) {
            $(ui.selecting).addClass("selected");
        },
        unselecting: function(event, ui) {
            $(ui.unselecting).removeClass("selected");
        }
    });
});


/**
 * Custom functions & plugins
 */
(function($) {

    /**
     * Based on AJAX Nette Framework plugin for jQuery
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
                if (payload === null || payload === undefined)
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

    // disable file manager and show ajax loader
    $.animateProgress = function(selector, e) {
        var fm = $(selector);
        $('<div class="fm-inactive"></div>').css({
            top: fm.position().top,
            left: fm.position().left,
            width: fm.width(),
            height: fm.height()
        }).ajaxStop(function() {
            $(this).remove();

        }).appendTo(fm);

        $('<div class="fm-ajax-loader"></div>').css({
            position: "absolute",
            left: e.pageX + 20,
            top: e.pageY + 40

        }).ajaxStop(function() {
            $(this).remove();

        }).appendTo("body");
    };


    /*
     * Based on Arron Bailiss <arron@arronbailiss.com> jQuery Shift-click Plugin
     */
    $.fn.shiftClick = function(tag, clickedClass) {
        var lastSelected;
        var parents = $(this),
        childs = $(this).children(tag);
        this.children(tag).each(function() {
            parents.attr('unselectable', 'on');
            $(this).click(function(ev) {
                if (ev.shiftKey) {
                    var first = parents.children().index(this);
                    var last = parents.children().index(lastSelected);

                    var start = Math.min(first, last);
                    var end = Math.max(first, last);

                    for (var i = start; i <= end; i++) {
                        childs.eq(i).addClass(clickedClass);
                    }
                }
                else {
                    $(this).toggleClass(clickedClass);
                    lastSelected = this;
                }
            });
        });
    };


    /*
     * CTRL + key combination plugin
     *
     * @author http://www.gmarwaha.com/blog/2009/06/16/ctrl-key-combination-simple-jquery-plugin/
     */
    $.ctrl = function(key, callback, args) {
        $(document).keydown(function(e) {
            if(!args) args=[]; // IE barks when args is null
            if(e.keyCode == key.charCodeAt(0) && e.ctrlKey) {
                callback.apply(this, args);
                return false;
            }
        });
    };


    /**
     * AJAX form plugin for jQuery
     *
     * @copyright  Copyright (c) 2009 Jan Marek
     * @license    MIT
     * @link       http://nettephp.com/cs/extras/ajax-form
     * @version    0.1
     */
    $.fn.ajaxSubmit = function (callback, ajaxOptions) {
        var form;
        var sendValues = {};

        if (typeof ajaxOptions === "undefined") {
            ajaxOptions = {};
        }

        // submit button
        if (this.is(":submit")) {
            form = this.parents("form");
            sendValues[this.attr("name")] = this.val() || "";

        // form
        } else if (this.is("form")) {
            form = this;

        // invalid element, do nothing
        } else {
            return null;
        }

        // validation
        if (form.get(0).onsubmit && !form.get(0).onsubmit()) {
            return null;
        }

        // get values
        var values = form.serializeArray();

        for (var i = 0; i < values.length; i++) {
            var name = values[i].name;

            // multi
            if (name in sendValues) {
                var val = sendValues[name];

                if (!(val instanceof Array)) {
                    val = [val];
                }

                val.push(values[i].value);
                sendValues[name] = val;
            } else {
                sendValues[name] = values[i].value;
            }
        }

        // send ajax request
        var ajaxOptions = {
            url: form.attr("action"),
            data: sendValues,
            type: form.attr("method") || "get"
        };

        if (callback) {
            ajaxOptions.success = callback;
        }

        return $.ajax(ajaxOptions);
    };

})(jQuery);