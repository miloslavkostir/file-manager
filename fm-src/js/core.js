/*
 * Copyright (c) 2011 Bronislav Sedlák <bauer01@seznam.cz>
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

 (function($) {

        // disable file manager and show spinner
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

                $('<div id="fm-ajax-spinner"></div>').css({
                        position: "absolute",
                        left: e.pageX + 20,
                        top: e.pageY + 40

                }).ajaxStop(function() {
                            $(this).remove();

                }).appendTo("body");
        };


        /* Based on Arron Bailiss <arron@arronbailiss.com> jQuery Shift-click Plugin */
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


        $.fn.noTextHighlight = function() {
                return this.each(function() {
                        this.onselectstart = function() {
                            return false;
                        };
                        this.unselectable = "on";
                        $(this).css('user-select', 'none');
                        $(this).css('-o-user-select', 'none');
                        $(this).css('-moz-user-select', 'none');
                        $(this).css('-khtml-user-select', 'none');
                        $(this).css('-webkit-user-select', 'none');
                });
        }
        

        $.ctrl = function(key, callback, args) {
            $(document).keydown(function(e) {
                if(!args) args=[]; // IE barks when args is null
                if(e.keyCode == key.charCodeAt(0) && e.ctrlKey) {
                    callback.apply(this, args);
                    return false;
                }
            });
        };

        $.fn.markAll = function(tag, clickedClass) {
            this.children(tag).each(function() {
                $(this).addClass(clickedClass);
            });
        };
        
        $.fn.unmarkAll = function(tag, clickedClass) {
            this.children(tag).each(function() {
                $(this).removeClass(clickedClass);
            });
        };        
        
        $.getSelected = function() {
            var items = $("#fm-small-images .selected");          
            return items;
        };

})(jQuery);