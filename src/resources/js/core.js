$(function() {

    $.nette.ext("ixtrum-file-manager", {
        before: function() {
            $("#file-context-menu").hide();
        },
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

        /* Content */
        $.ctrl("A", function() {
            $(".fm-content-file").addClass("ui-selected");
        });

        $(".fm-content-files").selectable({
            filter: ".fm-content-file"
        });

        /* Context menu */
        $(".file-manager .fm-content-file").on("contextmenu", function(event) {
            event.preventDefault();

            // Select item
            if ($(".fm-content-file.ui-selected").length === 1) {
                $(".fm-content-file").removeClass("ui-selected");
            }
            $(this).addClass("ui-selected");

            $("#file-context-menu").css({
                position: "fixed",
                top: event.clientY,
                left: event.clientX
            }).show();
            $("body").click(function() {
                $("#file-context-menu").hide();
            });
        });
    }

    initScripts();

    $("#file-context-menu li a").on("click", function(event) {
        event.preventDefault();
        var selected = {};
        $(".fm-content-files .ui-selected").each(function(i) {
            selected[i] = $(this).data("filename");
        });
        $.nette.ajax({
            type: "post",
            url: this.href,
            data: { files: selected }
        });
    });

    $(".file-manager").on("dblclick", ".fm-ajax-dbl", function(event) {
        event.preventDefault();
        $.nette.ajax({
            url: this.href
        });
    }).on("click", "a.fm-ajax-dbl", function(event) {
        event.preventDefault()
    });

    $(".file-manager").on("change", "select.ajax-select", function() {
        $(this).closest("form").submit();
    });
});


/**
 * Custom functions & plugins
 */
(function($) {

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