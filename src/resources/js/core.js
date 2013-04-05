$(function() {

    $.nette.ext("fm-inactive", {
        start: function() {
            $(".file-manager").each(function() {
                $("<div>", {
                    class: "fm-inactive",
                    css: {
                        top: $(this).position().top,
                        left: $(this).position().left,
                        width: $(this).outerWidth(),
                        height: $(this).outerHeight()
                    }
                }).appendTo($(this));
            });
        },
        complete: function() {
            $(".file-manager .fm-inactive").remove();
            initScripts();
        }
    });

    // Define scripts to initializate after page loaded or snippets refreshed
    function initScripts() {

        var mb = $(".fm-alert").stop(true, true).fadeIn();
        if (mb.data("delay"))
            clearTimeout(mb.data("delay"));
        mb.data("delay", setTimeout(function() {
            mb.fadeOut(500);
        }, 15000));

        $(".fm-draggable").draggable({
            revert: true,
            cursor: "pointer",
            helper: "clone",
            scroll: false,
            opacity: 0.6
        });

        $(".fm-droppable").droppable({
            hoverClass: "fm-state-highlight",
            drop: function(event, ui) {
                var filename = ui.draggable.data("filename");
                var targetdir = $(this).data("targetdir");

                $(this).addClass("fm-state-highlight");
                $.nette.ajax({
                    url: $(this).data("move-url"),
                    data: {
                        filename: filename,
                        targetdir: targetdir
                    }
                }, this).done(function(payload) {
                    if (payload.result === "success") {
                        ui.draggable.remove();
                    }
                });
                $(this).removeClass("fm-state-highlight");
            }
        });

        /** Clipboard */
        $(".fm-clipboard").css({
            top: $(".fm-toolbar").outerHeight()
        });


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
    }

    initScripts();

    $(".file-manager").on("click", ".fm-show-messages", function(event) {
        event.preventDefault()
        $(".fm-alert-message-text").toggleClass("fm-hide");
    });

    $(".file-manager").on("click", ".fm-close", function(event) {
        event.preventDefault()
        $(".fm-alert").remove();
    });

    $(".file-manager").on("dblclick", ".fm-ajax-dbl", function(event) {
        event.preventDefault();
        $.nette.ajax({
            url: this.href
        });
    }).on("click", "a.fm-ajax-dbl", function(event) {
        event.preventDefault()
    });



    /* Navigation */
    $("body").on("focusin", ".fm-location input", function() {
        $(".fm-navigation").hide();
        $(this).addClass("active");
    });

    $("body").on("focusout", ".fm-location input", function() {
        $(this).removeClass("active");
        $('.fm-navigation').show();
    });


    /* Treeview */
    $(".file-manager .fm-treeview").mouseenter(function() {
        $(this).find(".hitarea").stop(true, true).show("fade");
    }).mouseleave(function() {
        $(".file-manager .hitarea").hide("fade", 700);
    });


    /** Clipboard */
    $(document).on("click", "#fm-clipboard-hide", function(event) {
        event.preventDefault();
        $(".fm-clipboard").slideToggle("slow");
    });

    $(document).on("click", "#show-clipboard", function(event) {
        event.preventDefault();
        $(".fm-clipboard").slideToggle("slow");
    });

    /** Toolbar */
    $(".file-manager").on("change", ".ajax-select", function() {
        $(this).closest("form").submit();
    });
});


/**
 * Custom functions & plugins
 */
(function($) {

    /*
     * Based on Arron Bailiss <arron@arronbailiss.com> jQuery Shift-click Plugin
     */
    $.fn.shiftClick = function(tag, clickedClass) {
        var lastSelected;
        var parents = $(this),
                childs = $(this).children(tag);
        this.children(tag).each(function() {
            parents.attr("unselectable", "on");
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
            if (!args)
                args = []; // IE barks when args is null
            if (e.keyCode == key.charCodeAt(0) && e.ctrlKey) {
                callback.apply(this, args);
                return false;
            }
        });
    };


})(jQuery);