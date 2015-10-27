/* @codekit-prepend "vendor/js.cookie.js" */

var j = jQuery.noConflict();

var Optinoid = {
	isMobile: false,
	el: null,
	type: 'popup',
	win: null,
	cookies: [],
	init: function() {
		var self = this;
		
		// check cookies
		this.cookies = Cookies.getJSON('optinoid-closed-optins');
		
		// check if is mobile or not
		if (window.matchMedia("(max-width: 768px)").matches) {
			this.isMobile = true;
		}
		
		this.win = j(window);
		
		// get optins
		this.getOptins();
		
		
	},
	events: function() {
		var self = this;
		
		// click on close optin
		j('.optinoid-optin .close-optin').click(function(e) {
			self.close(true);
			e.preventDefault();
		});
		
		// click on ovelay
		j('#optinoid-overlay').click(function(e) {
//			console.log(e.target);
			if(e.target !== this) {
				return;
			}
			self.close(true);
			e.preventDefault();
		});
		
		// click on text on mobile, displays form
		j('.optinoid-text').click(function(e) {
			if(!j('form', j(this).parent()).hasClass('active')) {
				j('form', j(this).parent()).addClass('active');
			} else {
				j('form', j(this).parent()).removeClass('active');
			}
		});
		
		// if welcome mat, listen for scroll top to close
		if(this.type == 'welcomemat' || this.type == 'welcomemat-fb') {
			// window scroll
				this.win.scroll(function(){
					if(j('#optinoid-welcome').hasClass('active')) {
						if(self.win.scrollTop() > self.win.height()) {
							self.close();
						}
					}
				});

			// window resize
			this.win.resize(function(){
				j('#optinoid-welcome').height(self.win.height());
			});
		}
				
	},
	getOptins: function() {
		
		//
		var self = this;
		
		// optins class
		var optin_class = this.isMobile?'mobile':'desktop';
		
		if(j('.optinoid-optin.'+optin_class).length) {
			
			// loop through elements and open first that is not cookied
			j('.optinoid-optin.'+optin_class).each(function(){
				
				// populate this.el
				self.el = j(this);
				
				self.type = j(self.el).data('type');
				
				var id = j(self.el).data('id');
				
				if(j.inArray(id, self.cookies) === -1) {
				
					// form submit
					self.formSubmit();
					
					// events
					self.events();
					
					// open popup
					self.open();
					
					return false;
				}
				
			});
			
						
		}	
	},
	formSubmit: function() {
		var self = this;
		
		j('.optinoid-form').submit(function(e){
		
			if(j('input[type=email]', this).val().length !== 0) {
				// remove has error class
				j('input[type=email]', this).removeClass('has-error');
				
				// show preloader
				j(this).closest('.optinoid-optin').append('<div class="preloader"><div class="circle"></div></div>');
				
				// submit form				
				jQuery.post(optinoid.api_url, j(this).serialize(), function(response) {
//					var r = jQuery.parseJSON(response);
					if(response.success && response.success === true) {
					
						if(response.redirect === true) {
							window.location = response.url;
						}
					
						self.close(false);
					}
				});
			} else {
				j('input[type=email]', this).addClass('has-error');
			}
			
			e.preventDefault();
		});	
	},
	open: function() {
	
		var el = null;
		var self = this;
		
		if(this.type == 'popup') {
			el = '#optinoid-overlay';
		}		
		
		if(this.type == 'welcomemat' || this.type == 'welcomemat-fb') {
			el = '#optinoid-welcome';
		}
		
		if(this.type == 'floating-bar') {
			el = '#optinoid-floating-bar';
		}
		
		if(el) {
			j(el).delay(j(this.el).data('delay')).queue(function(){
				j(this).addClass('active').dequeue();
				
				// add body padding top if welcomemat
				if(el == '#optinoid-welcome') {
					j('body').addClass('optinoid-welcome-padding').css({
						'padding-top': self.win.height()
					});
				}
				
				// add body padding if floating bar top
				if(el == '#optinoid-floating-bar' && j(el).hasClass('optinoid-stick-top')) {
					j('body').addClass('optinoid-fb-padding').css('padding-top', j('#optinoid-floating-bar').outerHeight());
				}
				
			});
			
		}
		// open popup and add class active
		j(this.el).delay(j(this.el).data('delay')).queue(function(){
			j(this).addClass('active').dequeue();
			if(el == '#optinoid-welcome') {
				if(j(el).children('.content-over-split').length) {
					j(el).append('<div class="optinoid-scroll split-screen"></div');
				} else {
					j(el).append('<div class="optinoid-scroll"></div');
				}
			}
		});
		
		// update views
		jQuery.post(optinoid.api_url, {id: j(this.el).data('id'), action: 'view_optinoid', security: optinoid.nonce});
		
	},
	close: function(set_cookie) {
	
		// close overlay
		j('#optinoid-welcome, #optinoid-overlay').addClass('closing').delay(500).queue(function(){
			j(this).removeClass('active closing').dequeue();
		});
		
		// close active overlay
		j(this.el).addClass('closing').delay(500).queue(function(){
			j(this).removeClass('active closing');	
		});
		
		// activate floating bar
		if(j('#optinoid-floating-bar').length) {
			j('#optinoid-floating-bar').addClass('active');
			j('body').addClass('optinoid-fb-padding').css('padding-top', j('#optinoid-floating-bar').outerHeight());
//			window.scrollTo(0,0);
		}
		
		// reset body padding if needed
		if(j('body').hasClass('optinoid-welcome-padding')) {
			j('body').removeClass('optinoid-welcome-padding').css('padding-top', 0);
		}
		
		// set cookie to prevent this opening further
		if(set_cookie) {
			jQuery.post(optinoid.api_url, {id: j(this.el).data('id'), action: 'close_optinoid', security: optinoid.nonce});
		}
		
	}
};

window.matchMedia || (window.matchMedia = function() {
    "use strict";

    // For browsers that support matchMedium api such as IE 9 and webkit
    var styleMedia = (window.styleMedia || window.media);

    // For those that don't support matchMedium
    if (!styleMedia) {
        var style       = document.createElement('style'),
            script      = document.getElementsByTagName('script')[0],
            info        = null;

        style.type  = 'text/css';
        style.id    = 'matchmediajs-test';

        script.parentNode.insertBefore(style, script);

        // 'style.currentStyle' is used by IE <= 8 and 'window.getComputedStyle' for all other browsers
        info = ('getComputedStyle' in window) && window.getComputedStyle(style, null) || style.currentStyle;

        styleMedia = {
            matchMedium: function(media) {
                var text = '@media ' + media + '{ #matchmediajs-test { width: 1px; } }';

                // 'style.styleSheet' is used by IE <= 8 and 'style.textContent' for all other browsers
                if (style.styleSheet) {
                    style.styleSheet.cssText = text;
                } else {
                    style.textContent = text;
                }

                // Test if media query is true or false
                return info.width === '1px';
            }
        };
    }

    return function(media) {
        return {
            matches: styleMedia.matchMedium(media || 'all'),
            media: media || 'all'
        };
    };
}());

function isEmail(email) {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}

j(document).ready(function($) {
//	$('body').append('<div id="optinoid-overlay"></div>');
	Optinoid.init();
});