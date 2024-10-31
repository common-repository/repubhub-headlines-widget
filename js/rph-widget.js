function initSelects() {
	jQuery(".rph-widget-blacklist").select2({
	  placeholder: 'None',
	  ajax: {
	    url: publications_url.url,
	    dataType: 'json',
	    delay: 250,
	    data: function (params) {
	      return {
	        query: params.term, // search term
	        page: params.page
	      };
	    },
	    processResults: function (data, params) {
	      // parse the results into the format expected by Select2
	      // since we are using custom formatting functions we do not need to
	      // alter the remote JSON data, except to indicate that infinite
	      // scrolling can be used
	      params.page = params.page || 1;
	
	      jQuery.map(data.results, function (obj) {
	    	  obj.id = obj.id + '-' + obj.title;
	    	  obj.text = obj.title; // replace name with the property used for the text
	
	    	  return obj;
	    	});
	      
	      return {
	        results: data.results,
	        pagination: {
	          more: (params.page * 30) < data.total_count
	        }
	      };
	    },
	    cache: true
	  },
	  minimumInputLength: 2
	});		
	
	jQuery(".rph-widget-whitelist").select2({
		placeholder: 'All Publications',
		  ajax: {
		    url: publications_url.url,
		    dataType: 'json',
		    delay: 250,
		    data: function (params) {
		      return {
		        query: params.term, // search term
		        page: params.page
		      };
		    },
		    processResults: function (data, params) {
		      // parse the results into the format expected by Select2
		      // since we are using custom formatting functions we do not need to
		      // alter the remote JSON data, except to indicate that infinite
		      // scrolling can be used
		      params.page = params.page || 1;
		
		      jQuery.map(data.results, function (obj) {
		    	  obj.id = obj.id + '-' + obj.title;
		    	  obj.text = obj.title; // replace name with the property used for the text
		
		    	  return obj;
		    	});
		      
		      return {
		        results: data.results,
		        pagination: {
		          more: (params.page * 30) < data.total_count
		        }
		      };
		    },
		    cache: true
		  },
		  minimumInputLength: 2
		});		
	
	
	jQuery(".rph-widget-featuredlist").select2({
		placeholder: 'All Publications',
		multiple: true
		});	
	
	
	// Remove extra junk that shows up for some reason when using select2 and dragging widget to sidebar
	var sib = jQuery('p.rph_widget_select2 span[class="select2 select2-container select2-container--default"]').next();
	if (jQuery(sib).length != 0) {
		console.log("removing");
		jQuery(sib).remove();
		
		jQuery("p.rph_widget_select2").next("ul[class='select2-selection__rendered']").remove();
		jQuery("p.rph_widget_select2").next("span[class='dropdown-wrapper']").remove();
	}
}

function initRemoval() {
  jQuery("ul.rph_widget a#rph_widget_remove_article").click(function(event) {
	  event.preventDefault();	  
	  var conf = confirm("Remove this headline?");
	  
	  if (conf == true) {
		  var widgetId = jQuery(this).data("widgetid");
		  jQuery("li[data-widgetid='" + widgetId + "'][data-articleid='" + jQuery(this).data('articleid') + "']").fadeOut(500, function() {
			var el = jQuery("li.rph_widget_extra[data-widgetid='" + widgetId + "']").first(); 
			jQuery(el).show("highlight", 2000);
			jQuery(el).removeClass("rph_widget_extra");
		  });	  
		  
	      jQuery.ajax({
	          url : admin_ajax_url.url,
	          type : "post",
	          data : {action: "rph_widget_delete_article", articleId: jQuery(this).data("articleid"), widgetId: widgetId},
	          success:function(result){

	          }
	      });
	  }
      
      
  });
  
  jQuery("ul.rph_widget a#rph_widget_remove_publication").click(function(event) {
	  event.preventDefault();	  
	  var conf = confirm(jQuery(this).text() + " headlines?");
	  var pubId = jQuery(this).data("publicationid");
	  
	  if (conf == true) {
	    var widgetId = jQuery(this).data("widgetid");
		jQuery("li[data-widgetid='" + widgetId + "'][data-publicationid='" + pubId + "']").fadeOut(500, function() {
		  var el = jQuery("li.rph_widget_extra[data-widgetid='" + widgetId + "']:not([data-publicationid=" + pubId + "])").first();
		  jQuery(el).removeClass("rph_widget_extra");
		  jQuery(el).show("highlight", 2000);
			
		  jQuery(this).remove();
		});	  
		  
	      jQuery.ajax({
	          url : admin_ajax_url.url,
	          type : "post",
	          data : {action: "rph_widget_exclude_publication", publicationId: pubId, publication: jQuery(this).data("publication"), widgetId: widgetId},
	          success:function(result){

	          }
	      });
	  }
      
      
  });  
}


jQuery(document).ready(function() {
	if (typeof jQuery.fn.select2 == "function") {
	  initSelects();
	}
	
	initRemoval();
	
	jQuery(document).on("change", "input.icx_widget_checkbox", function() {
	  var checked = this.checked;
	  var opacity = checked ? "opacity: 1.0;" : "opacity: .5;";
	  
	  jQuery(this).nextAll('label').first().attr('style', opacity);
	  jQuery(this).nextAll('input').first().prop('disabled', !checked);
	});
});