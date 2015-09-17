/*jshint devel:true */

var j = jQuery.noConflict();

var OptinoidAdmin = {
	win: null,
	init: function() {
		this.win = j(window);
		
		this.onLoad();
	},
	onLoad: function() {
		var self = this;
		
		// init tabs
		j('#optinoid-tabs').tabs();
		
		// preload lists for selected value
		if(j('#optinoid_integration').length && j('#optinoid_integration').val().length !== 0) {
			this.getLists(j('#optinoid_integration').val());	
		}
		
		j('#optinoid_integration').change(function(){
			//
			self.getLists(j(this).val());
		});
		
		// check optinoid_type
		if(j('#optinoid_type').val() == 'inline') {
			j('#optinoid-shortcode').removeClass('hidden');
		}
		
		if(j('#optinoid_type').val() == 'welcomemat-fb' || j('#optinoid_type').val() == 'floating-bar') {
			j('.floating-bar').removeClass('hidden');	
		}
		
		j('#optinoid_type').change(function(){
			if(j(this).val() == 'inline') {
				j('#optinoid-shortcode').removeClass('hidden');
			} else {
				j('#optinoid-shortcode').addClass('hidden');
			}
			
			if(j(this).val() == 'welcomemat-fb' || j(this).val() == 'floating-bar') {
				j('.floating-bar').removeClass('hidden');
			} else {
				j('.floating-bar').addClass('hidden');
			}
		});
		
		
		// load lists on change
	},
	getLists: function(integration) {
		
		j('#list-select select').addClass('hidden');
		j('#list-select input[type=hidden]').val('');
		j('#list-select label').html('<strong>&nbsp;</strong>');
		
		if(integration.length === 0) return;
		
		// get json
		j.getJSON(ajaxurl, {
			action: 'optinoid_'+integration+'_lists'
		}, function(response) {
		
				if(response.error) {
					j('#list-select label').html('<strong>'+response.error+'</strong>').css('color', '#a00');
					return;
				}
				
				if(response.length !== 0) {
					var options = '';
					
					j.each(response, function(k,v){
						options += '<option value="'+v.id+'">'+v.name+'</option>';
					});
					// remove existing options
					j('#list-select select option').not(':first').remove();
					//
					j('#list-select select').append(options).removeClass('hidden');
					j('#list-select label').html('<strong>Select list</strong>');
					
					// preselect default
					j('#list-select select').val(j('#list-select input[type=hidden]').val());
					
				}

		});
	}
};

jQuery(document).ready(function($) {

	OptinoidAdmin.init();
	
	if(!jQuery('#load_globally').is(':checked')) {
		jQuery('.optin-not-globally').show();
	}
	
	jQuery('#load_globally').change(function(){
		if(!this.checked) {
			jQuery('.optin-not-globally').show();
		} else {
			jQuery('.optin-not-globally').hide();
		}
	});
	
	jQuery('.optin_pages').on('change', 'input[type=checkbox]', function(e) {
		if(!jQuery(this).is(':checked')) {
			jQuery(this).closest('li').remove();
		}
	});
	
	jQuery('#optin_page_search').autocomplete({
		source: ajaxurl+'?action=page_search',
		select: function(event, ui) {
			if(!jQuery('.optin_pages .page-'+ui.item.id).length) {
				jQuery('.optin_pages').append('<li class="page-'+ui.item.id+'"><label><input type="checkbox" name="optin_pages[]" value="'+ui.item.id+'" checked="checked" /> '+ui.item.label+'</label></li>');
			}
			jQuery(this).val('');
			return false;
		}
	});
	
	jQuery('#optinoid-options-metabox').on('change', '.optinoid-theme input[type=radio]', function(){
		if(!jQuery(this).hasClass('active')) {
			jQuery('.optinoid-theme.active').removeClass('active');
			jQuery(this).closest('.optinoid-theme').addClass('active');
		}
	});
	
});


