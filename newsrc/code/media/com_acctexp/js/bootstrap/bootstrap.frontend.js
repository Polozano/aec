/* ===================================================
 * bootstrap.js v2.0.0
 * http://twitter.github.com/bootstrap/javascript.html#transitions
 * ===================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */

!function(a){a(function(){"use strict";a.support.transition=function(){var b=document.body||document.documentElement,c=b.style,d=c.transition!==undefined||c.WebkitTransition!==undefined||c.MozTransition!==undefined||c.MsTransition!==undefined||c.OTransition!==undefined;return d&&{end:function(){var b="TransitionEnd";if(a.browser.webkit){b="webkitTransitionEnd"}else if(a.browser.mozilla){b="transitionend"}else if(a.browser.opera){b="oTransitionEnd"}return b}()}}()})}(window.jQuery);!function(a){function g(){var b=this;if(this.isShown&&this.options.keyboard){a(document).on("keyup.dismiss.modal",function(a){a.which==27&&b.hide()})}else if(!this.isShown){a(document).off("keyup.dismiss.modal")}}function f(){this.$backdrop.remove();this.$backdrop=null}function e(b){var c=this,d=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.options.backdrop){var e=a.support.transition&&d;this.$backdrop=a('<div class="modal-backdrop '+d+'" />').appendTo(document.body);if(this.options.backdrop!="static"){this.$backdrop.click(a.proxy(this.hide,this))}if(e)this.$backdrop[0].offsetWidth;this.$backdrop.addClass("in");e?this.$backdrop.one(a.support.transition.end,b):b()}else if(!this.isShown&&this.$backdrop){this.$backdrop.removeClass("in");a.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one(a.support.transition.end,a.proxy(f,this)):f.call(this)}else if(b){b()}}function d(a){this.$element.hide().trigger("hidden");e.call(this)}function c(){var b=this,c=setTimeout(function(){b.$element.off(a.support.transition.end);d.call(b)},500);this.$element.one(a.support.transition.end,function(){clearTimeout(c);d.call(b)})}"use strict";var b=function(b,c){this.options=a.extend({},a.fn.modal.defaults,c);this.$element=a(b).delegate('[data-dismiss="modal"]',"click.dismiss.modal",a.proxy(this.hide,this))};b.prototype={constructor:b,toggle:function(){return this[!this.isShown?"show":"hide"]()},show:function(){var b=this;if(this.isShown)return;a("body").addClass("modal-open");this.isShown=true;this.$element.trigger("show");g.call(this);e.call(this,function(){var c=a.support.transition&&b.$element.hasClass("fade");!b.$element.parent().length&&b.$element.appendTo(document.body);b.$element.show();if(c){b.$element[0].offsetWidth}b.$element.addClass("in");c?b.$element.one(a.support.transition.end,function(){b.$element.trigger("shown")}):b.$element.trigger("shown")})},hide:function(b){b&&b.preventDefault();if(!this.isShown)return;var e=this;this.isShown=false;a("body").removeClass("modal-open");g.call(this);this.$element.trigger("hide").removeClass("in");a.support.transition&&this.$element.hasClass("fade")?c.call(this):d.call(this)}};a.fn.modal=function(c){return this.each(function(){var d=a(this),e=d.data("modal"),f=typeof c=="object"&&c;if(!e)d.data("modal",e=new b(this,f));if(typeof c=="string")e[c]();else e.show()})};a.fn.modal.defaults={backdrop:true,keyboard:true};a.fn.modal.Constructor=b;a(function(){a("body").on("click.modal.data-api",'[data-toggle="modal"]',function(b){var c=a(this),d,e=a(c.attr("data-target")||(d=c.attr("href"))&&d.replace(/.*(?=#[^\s]+$)/,"")),f=e.data("modal")?"toggle":a.extend({},e.data(),c.data());b.preventDefault();e.modal(f)})})}(window.jQuery);!function(a){function b(a,b){var c=jQuery.proxy(this.process,this),d=jQuery(a).is("body")?jQuery(window):jQuery(a),e;this.options=jQuery.extend({},jQuery.fn.scrollspy.defaults,b);this.$scrollElement=d.on("scroll.scroll.data-api",c);this.selector=(this.options.target||(e=jQuery(a).attr("href"))&&e.replace(/.*(?=#[^\s]+$)/,"")||"")+" .nav li > a";this.$body=jQuery("body").on("click.scroll.data-api",this.selector,c);this.refresh();this.process()}"use strict";b.prototype={constructor:b,refresh:function(){this.targets=this.$body.find(this.selector).map(function(){var a=jQuery(this).attr("href");return/^#\w/.test(a)&&jQuery(a).length?a:null});this.offsets=jQuery.map(this.targets,function(a){return jQuery(a).position().top})},process:function(){var a=this.$scrollElement.scrollTop()+this.options.offset,b=this.offsets,c=this.targets,d=this.activeTarget,e;for(e=b.length;e--;){d!=c[e]&&a>=b[e]&&(!b[e+1]||a<=b[e+1])&&this.activate(c[e])}},activate:function(a){var b;this.activeTarget=a;this.$body.find(this.selector).parent(".active").removeClass("active");b=this.$body.find(this.selector+'[href="'+a+'"]').parent("li").addClass("active");if(b.parent(".dropdown-menu")){b.closest("li.dropdown").addClass("active")}}};jQuery.fn.scrollspy=function(a){return this.each(function(){var c=jQuery(this),d=c.data("scrollspy"),e=typeof a=="object"&&a;if(!d)c.data("scrollspy",d=new b(this,e));if(typeof a=="string")d[a]()})};jQuery.fn.scrollspy.Constructor=b;jQuery.fn.scrollspy.defaults={offset:10};jQuery(function(){jQuery('[data-spy="scroll"]').each(function(){var a=jQuery(this);a.scrollspy(a.data())})})}(window.jQuery);!function(a){"use strict";var b=function(b){this.element=a(b)};b.prototype={constructor:b,show:function(){var b=this.element,c=b.closest("ul:not(.dropdown-menu)"),d=b.attr("data-target"),e,f;if(!d){d=b.attr("href");d=d&&d.replace(/.*(?=#[^\s]*$)/,"")}if(b.parent("li").hasClass("active"))return;e=c.find(".active a").last()[0];b.trigger({type:"show",relatedTarget:e});f=a(d);this.activate(b.parent("li"),c);this.activate(f,f.parent(),function(){b.trigger({type:"shown",relatedTarget:e})})},activate:function(b,c,d){function g(){e.removeClass("active").find("> .dropdown-menu > .active").removeClass("active");b.addClass("active");if(f){b[0].offsetWidth;b.addClass("in")}else{b.removeClass("fade")}if(b.parent(".dropdown-menu")){b.closest("li.dropdown").addClass("active")}d&&d()}var e=c.find("> .active"),f=d&&a.support.transition&&e.hasClass("fade");f?e.one(a.support.transition.end,g):g();e.removeClass("in")}};a.fn.tab=function(c){return this.each(function(){var d=a(this),e=d.data("tab");if(!e)d.data("tab",e=new b(this));if(typeof c=="string")e[c]()})};a.fn.tab.Constructor=b;a(function(){a("body").on("click.tab.data-api",'[data-toggle="tab"], [data-toggle="pill"]',function(b){b.preventDefault();a(this).tab("show")})})}(window.jQuery);!function(a){"use strict";var b=function(a,b){this.init("tooltip",a,b)};b.prototype={constructor:b,init:function(b,c,d){var e,f;this.type=b;this.$element=a(c);this.options=this.getOptions(d);this.enabled=true;if(this.options.trigger!="manual"){e=this.options.trigger=="hover"?"mouseenter":"focus";f=this.options.trigger=="hover"?"mouseleave":"blur";this.$element.on(e,this.options.selector,a.proxy(this.enter,this));this.$element.on(f,this.options.selector,a.proxy(this.leave,this))}this.options.selector?this._options=a.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},getOptions:function(b){b=a.extend({},a.fn[this.type].defaults,b,this.$element.data());if(b.delay&&typeof b.delay=="number"){b.delay={show:b.delay,hide:b.delay}}return b},enter:function(b){var c=a(b.currentTarget)[this.type](this._options).data(this.type);if(!c.options.delay||!c.options.delay.show){c.show()}else{c.hoverState="in";setTimeout(function(){if(c.hoverState=="in"){c.show()}},c.options.delay.show)}},leave:function(b){var c=a(b.currentTarget)[this.type](this._options).data(this.type);if(!c.options.delay||!c.options.delay.hide){c.hide()}else{c.hoverState="out";setTimeout(function(){if(c.hoverState=="out"){c.hide()}},c.options.delay.hide)}},show:function(){var a,b,c,d,e,f,g;if(this.hasContent()&&this.enabled){a=this.tip();this.setContent();if(this.options.animation){a.addClass("fade")}f=typeof this.options.placement=="function"?this.options.placement.call(this,a[0],this.$element[0]):this.options.placement;b=/in/.test(f);a.remove().css({top:0,left:0,display:"block"}).appendTo(b?this.$element:document.body);c=this.getPosition(b);d=a[0].offsetWidth;e=a[0].offsetHeight;switch(b?f.split(" ")[1]:f){case"bottom":g={top:c.top+c.height,left:c.left+c.width/2-d/2};break;case"top":g={top:c.top-e,left:c.left+c.width/2-d/2};break;case"left":g={top:c.top+c.height/2-e/2,left:c.left-d};break;case"right":g={top:c.top+c.height/2-e/2,left:c.left+c.width};break}a.css(g).addClass(f).addClass("in")}},setContent:function(){var a=this.tip();a.find(".tooltip-inner").html(this.getTitle());a.removeClass("fade in top bottom left right")},hide:function(){function d(){var b=setTimeout(function(){c.off(a.support.transition.end).remove()},500);c.one(a.support.transition.end,function(){clearTimeout(b);c.remove()})}var b=this,c=this.tip();c.removeClass("in");a.support.transition&&this.$tip.hasClass("fade")?d():c.remove()},fixTitle:function(){var a=this.$element;if(a.attr("title")||typeof a.attr("data-original-title")!="string"){a.attr("data-original-title",a.attr("title")||"").removeAttr("title")}},hasContent:function(){return this.getTitle()},getPosition:function(b){return a.extend({},b?{top:0,left:0}:this.$element.offset(),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight})},getTitle:function(){var a,b=this.$element,c=this.options;a=b.attr("data-original-title")||(typeof c.title=="function"?c.title.call(b[0]):c.title);a=a.toString().replace(/(^\s*|\s*$)/,"");return a},tip:function(){return this.$tip=this.$tip||a(this.options.template)},validate:function(){if(!this.$element[0].parentNode){this.hide();this.$element=null;this.options=null}},enable:function(){this.enabled=true},disable:function(){this.enabled=false},toggleEnabled:function(){this.enabled=!this.enabled},toggle:function(){this[this.tip().hasClass("in")?"hide":"show"]()}};a.fn.tooltip=function(c){return this.each(function(){var d=a(this),e=d.data("tooltip"),f=typeof c=="object"&&c;if(!e)d.data("tooltip",e=new b(this,f));if(typeof c=="string")e[c]()})};a.fn.tooltip.Constructor=b;a.fn.tooltip.defaults={animation:true,delay:0,selector:false,placement:"top",trigger:"hover",title:"",template:'<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'}}(window.jQuery);!function(a){"use strict";var b=function(a,b){this.init("popover",a,b)};b.prototype=a.extend({},a.fn.tooltip.Constructor.prototype,{constructor:b,setContent:function(){var b=this.tip(),c=this.getTitle(),d=this.getContent();b.find(".popover-title")[a.type(c)=="object"?"append":"html"](c);b.find(".popover-content > *")[a.type(d)=="object"?"append":"html"](d);b.removeClass("fade top bottom left right in")},hasContent:function(){return this.getTitle()||this.getContent()},getContent:function(){var a,b=this.$element,c=this.options;a=b.attr("data-content")||(typeof c.content=="function"?c.content.call(b[0]):c.content);a=a.toString().replace(/(^\s*|\s*$)/,"");return a},tip:function(){if(!this.$tip){this.$tip=a(this.options.template)}return this.$tip}});a.fn.popover=function(c){return this.each(function(){var d=a(this),e=d.data("popover"),f=typeof c=="object"&&c;if(!e)d.data("popover",e=new b(this,f));if(typeof c=="string")e[c]()})};a.fn.popover.Constructor=b;a.fn.popover.defaults=a.extend({},a.fn.tooltip.defaults,{placement:"right",content:"",template:'<div class="popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'})}(window.jQuery);!function(a){"use strict";var b=function(b,c){this.$element=a(b);this.options=a.extend({},a.fn.button.defaults,c)};b.prototype={constructor:b,setState:function(a){var b="disabled",c=this.$element,d=c.data(),e=c.is("input")?"val":"html";a=a+"Text";d.resetText||c.data("resetText",c[e]());c[e](d[a]||this.options[a]);setTimeout(function(){a=="loadingText"?c.addClass(b).attr(b,b):c.removeClass(b).removeAttr(b)},0)},toggle:function(){var a=this.$element.parent('[data-toggle="buttons-radio"]');a&&a.find(".active").removeClass("active");this.$element.toggleClass("active")}};a.fn.button=function(c){return this.each(function(){var d=a(this),e=d.data("button"),f=typeof c=="object"&&c;if(!e)d.data("button",e=new b(this,f));if(c=="toggle")e.toggle();else if(c)e.setState(c)})};a.fn.button.defaults={loadingText:"loading..."};a.fn.button.Constructor=b;a(function(){a("body").on("click.button.data-api","[data-toggle^=button]",function(b){a(b.target).button("toggle")})})}(window.jQuery);!function(a){"use strict";var b=function(b,c){this.$element=a(b);this.options=a.extend({},a.fn.collapse.defaults,c);if(this.options["parent"]){this.$parent=a(this.options["parent"])}this.options.toggle&&this.toggle()};b.prototype={constructor:b,dimension:function(){var a=this.$element.hasClass("width");return a?"width":"height"},show:function(){var b=this.dimension(),c=a.camelCase(["scroll",b].join("-")),d=this.$parent&&this.$parent.find(".in"),e;if(d&&d.length){e=d.data("collapse");d.collapse("hide");e||d.data("collapse",null)}this.$element[b](0);this.transition("addClass","show","shown");this.$element[b](this.$element[0][c])},hide:function(){var a=this.dimension();this.reset(this.$element[a]());this.transition("removeClass","hide","hidden");this.$element[a](0)},reset:function(a){var b=this.dimension();this.$element.removeClass("collapse")[b](a||"auto")[0].offsetWidth;this.$element.addClass("collapse")},transition:function(b,c,d){var e=this,f=function(){if(c=="show")e.reset();e.$element.trigger(d)};this.$element.trigger(c)[b]("in");a.support.transition&&this.$element.hasClass("collapse")?this.$element.one(a.support.transition.end,f):f()},toggle:function(){this[this.$element.hasClass("in")?"hide":"show"]()}};a.fn.collapse=function(c){return this.each(function(){var d=a(this),e=d.data("collapse"),f=typeof c=="object"&&c;if(!e)d.data("collapse",e=new b(this,f));if(typeof c=="string")e[c]()})};a.fn.collapse.defaults={toggle:true};a.fn.collapse.Constructor=b;a(function(){a("body").on("click.collapse.data-api","[data-toggle=collapse]",function(b){var c=a(this),d,e=c.attr("data-target")||b.preventDefault()||(d=c.attr("href"))&&d.replace(/.*(?=#[^\s]+$)/,""),f=a(e).data("collapse")?"toggle":c.data();a(e).collapse(f)})})}(window.jQuery)