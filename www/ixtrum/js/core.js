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

jQuery(document).ready(function() {

        $(".file-manager").bind("snippetUpdated", function() {



                /** Main */
                $('.fm-alert').width($(".file-manager").width());
                $('.fm-alert').fadeIn('slow')
                        .animate({opacity: 1.0}, 12000)
                        .fadeOut('slow', function() {
                            $(this).remove();
                            $(".fm-other-messages").remove();
                        });


                $('.fm-alert').delegate('.fm-icon-close', 'click', function() {
                        $(".fm-alert").remove();
                        $(".fm-other-messages").remove();
                });


                /** Clipboard */
                $('.fm-clipboard').css({
                        position: 'absolute',
                        top: $('.fm-toolbar').height()
                });


                /* Filter mask */
                $(".fm-filter-form input").focus(function(src)
                {
                    if ($(this).val() == $(this)[0].title)
                    {
                        $(this).removeClass("active");
                        $(this).val("");
                    }
                });

                $(".fm-filter-form input").blur(function()
                {
                    if ($(this).val() == "")
                    {
                        $(this).addClass("active");
                        $(this).val($(this)[0].title);
                    }
                });

                //$(".fm-filter-form input").blur();



                /* Navigation */
                $('.fm-navigation').removeClass("inactive");

                $('.fm-location input').focus(function() {
                        $(".fm-navigation").addClass("inactive");
                        $(this).addClass("active");
                });

                $('.fm-location input').blur(function() {
                        $(this).removeClass("active");
                        $('.fm-navigation').removeClass("inactive");
                });



                /* Treeview */
                $(".file-manager .filetree").treeview({
                    persist: "cookie"
                });

                $(".file-manager .hitarea").hide();

                $(".file-manager .fm-treeview").hover(function() {
                        $(".file-manager .hitarea").stop(true, true)
                        $(".file-manager .hitarea").show('fade');
                }, function() {
                        $(".file-manager .hitarea").hide('fade', 700);
                });

        });



        /** Main */
        $('.fm-alert').delegate('.fm-show-messages', 'click', function() {
                $(".fm-other-messages").toggleClass("fm-hide");
        });

        $(".file-manager .fm-body").noTextHighlight();

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

        $(".file-manager").delegate("form.fm-ajax", "submit", function() {
                $(this).ajaxSubmit();
                return false;
        });

        $(".file-manager").delegate("form.fm-ajax :submit", "click", function() {
                $(this).ajaxSubmit();
                return false;
        });

        $(".file-manager").delegate("#frm-fileManager-viewSelector-changeViewForm", "change", function() {
                $(this).ajaxSubmit();
                return false;
        });



        /* Filter mask */
        $(".fm-filter-form input").focus(function(src)
        {
            if ($(this).val() == $(this)[0].title)
            {
                $(this).removeClass("active");
                $(this).val("");
            }
        });

        $(".fm-filter-form input").blur(function()
        {
            if ($(this).val() == "")
            {
                $(this).addClass("active");
                $(this).val($(this)[0].title);
            }
        });

        $(".fm-filter-form input").blur();



        /* Navigation */
        $('.fm-navigation').removeClass("inactive");

        $('.fm-location input').focus(function() {
                $(".fm-navigation").addClass("inactive");
                $(this).addClass("active");
        });

        $('.fm-location input').blur(function() {
                $(this).removeClass("active");
                $('.fm-navigation').removeClass("inactive");
        });



        /* Treeview */
        $(".file-manager .filetree").treeview({
            persist: "cookie"
        });

        $(".file-manager .hitarea").hide();

        $(".file-manager .fm-treeview").hover(function() {
                $(".file-manager .hitarea").stop(true, true)
                $(".file-manager .hitarea").show('fade');
        }, function() {
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
  });
  
  
/** Custom functions */
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