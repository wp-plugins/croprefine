// TopShelf - Popover ~ Copyright (c) 2011 - 2012 TopShelf Solutions Limited, https://github.com/flashbackzoo/TopShelf-Popover
// Released under MIT license, http://www.opensource.org/licenses/mit-license.php
var cur;!function(t){t.fn.tsPopover=function(n,e,o){var r=[],a={fade:{tranIn:function(n){c.positionPopover(n),t(n.container).clearQueue().stop().fadeTo("slow",1,function(){c.openCallback(n)})},tranOut:function(n){t(n.container).clearQueue().stop().fadeTo("fast",0,function(){c.closeCallback(n)})}}},i={open:function(n){t("[data-ui*='popover-trigger'][href='"+n.container.id+"']").addClass("current"),t(n.container).addClass("current"),a[n.settings.transition].tranIn(n)},close:function(n){t("[data-ui*='popover-trigger'].current").removeClass("current"),t(n.container).removeClass("current");try{a[n.settings.transition].tranOut(n)}catch(e){}}},c={openCallback:function(t){void 0!==t.settings.callbacks.open&&t.settings.callbacks.open(t)},closeCallback:function(t){void 0!==t.settings.callbacks.close&&t.settings.callbacks.close(t)},getPopoverObjectById:function(n){var e={};return t(r).each(function(o,r){currentContainerId=t(r.container).attr("id"),currentContainerId===n&&(e=r)}),e},positionPopover:function(n){var e=t(cur).closest("td").offset();t(n.container).css({top:e.top+"px",left:e.left+"px",marginLeft:"-362px"})}};if(i[n]){var s={container:this,settings:{transition:e,callbacks:{open:o,close:o}}};return i[n].call(i,s,s)}if("object"==typeof n||void 0===n){var l=t.extend({transition:"simple",easyClose:!1,draggable:!1,mask:!1,callbacks:{open:function(){return!1},close:function(){return!1}}},n);return this.each(function(){var n={container:this,settings:l,triggers:t("a[href='"+this.id+"']"),close:t(this).find("[data-ui*='popover-close']")[0]},e=function(e){var o={};return function(){o.triggers=function(){n.triggers.length>0&&t(n.triggers).each(function(){t(this).click(function(t){t.preventDefault(),t.stopPropagation()}),t(this).hover(function(o){if(cur=t(this),o.preventDefault(),o.stopPropagation(),!t(n.container).hasClass("current")){var a=t("[data-ui*='popover-panel'][class='current']");a.length&&t(r).each(function(t,n){e.close(n)}),e.open(n)}},function(){e.close(n)})})}}(),o};!function(){var t=e(i,c);t.triggers(),r[r.length]=n}()})}t.error(this)}}(jQuery);


/* croprefine handlers */
var cropdata = {};
var cropitem = {};

jQuery(document).ready( function($) {

	$(".modal-cropper-hide").on("click", hideModal);
	
	//choose an image to refine
	function cropRefine(item){
		$.post(ajaxurl, {'action': 'getimage','id':item }, 
		    function(r){			//console.log(r);
				//remove old sizes & destroy old cropper
		        $("#sizes tr").remove();
		        $(".cropper, .upload").unbind();
		        $(".container img").cropper("destroy");
		        
				$.each( r.sizes, function( key, arr ) {
					var itemname = (typeof(arr[3])!="undefined" && arr[3]!="unknown"?arr[3]:arr[0]);
					var itemdesc = (typeof(arr[3])!="undefined" && arr[3]!="unknown"?
									arr[0]+" ("+arr[3]+")":
									arr[0]+" ("+arr[1]+'x'+arr[2]+")");
					var listitem = '<tr rel="crop_'+key+'"><td><a class="preview" rel="'+r.url+arr[0]+'" title="Preview of: '+itemdesc+'" href="popover" data-ui="popover-trigger">' + itemname + '</a></td>';
						listitem+= '<td>' + arr[1] + ' x ' + arr[2] + '</td>';
						listitem+= '<td><a class="cropper button button-primary button-large" rel="' + arr[0] + '" data-width="'+arr[1]+'" data-height="'+arr[2]+'">Re-crop</a> ';
						listitem+= '<a class="upload button button-large" rel="' + arr[0] + '" data-width="'+arr[1]+'" data-height="'+arr[2]+'">Upload</a></td></tr>';
					$("#sizes").append(listitem);
				});
				$("#available-sizes").show();

				$("div[data-ui='popover-panel']").tsPopover({
					"transition" : "fade"
					//, "callbacks" : { "open" : function () { return false; } , "close" : function () { return false; }}
				});
				
				$("#cropperimage").html("<img src="+r.image+" />");
				
				initCropper();

				//add event listeners
				addListeners();
				showModal();
		    }
		);
	}
	
	//adds listeners to the file & crop links
	function addListeners(){
		
		$(".upload").on("click",function(){
			if($(this).closest("tr").next("tr").hasClass("filefield")){
				$(this).html("Upload");
				$(".filefield").remove();
			}else{
				var w = $(this).data('width'),
					h = $(this).data('height'),
					item = $(this).attr('rel');
			
				$(this).html("Cancel");
				$(".filefield").remove();
				var formhtml = "<tr class='alternate filefield'><td colspan='2'>Please select a <strong>"+w+"</strong>px (width) x <strong>"+h+"</strong>px (height) image to be uploaded to replace this media item size. If the uploaded image's dimensions do not match, it will automatically be re-sized for you to "+w+"x"+h+".";
					formhtml+= "<input type='file' name='newimage' id='newimage' /></td><td>";
				    formhtml+= "<input type='hidden' name='cropitem[w]' value='"+w+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[h]' value='"+h+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[item]' value='"+item+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[id]' value='"+mediaitem+"' />";
				    formhtml+= "<input type='submit' name='upload' id='upload' value='upload' class='button button-primary button-large' /></td></tr>";
				$(formhtml).insertAfter($(this).closest('tr'));
				
				//highlight row
				var uploadrow = $(this).parents("tr").attr("rel"); console.log("Upload Row: "+uploadrow);
				$(".cropper").each(function(){
					var row = $(this).parents("tr");
					if(row.attr("rel") == uploadrow) { 
						row.addClass("highlight"); 
					} else { row.removeClass("highlight"); }
				});
				//reset the cropper to this aspect, just in case the user cancels the upload
				$(".container > img").cropper("setAspectRatio", w/h);
				$(".container > img").cropper("reset", true);
				console.log("Setting up aspect for: "+w+"x"+h+" = "+w/h);
			}	
		});
		
		$(".cropper").on("click",function(){
			//remove filefields
			$(".filefield").remove();
			$("#upload").css("display","none");
			cropitem = {item:$(this).attr("rel"), w:$(this).data("width"), h:$(this).data("height") }

			//$.fn.cropper.setDefaults(
			$(".container > img").cropper("setAspectRatio", cropitem.w/cropitem.h);
			$(".container > img").cropper("reset", true);
			//console.log("Setting up aspect for: "+cropitem.w+"x"+cropitem.h+" = "+cropitem.w/cropitem.h);
			
			//highlight row
			var croprow = $(this).parents("tr").attr("rel");
			$(".cropper").each(function(){
				var row = $(this).parents("tr");
				if(row.attr("rel") == croprow) { 
					row.addClass("highlight"); 
				} else { row.removeClass("highlight"); }
			});
		});
		
		$("#savecrop").on("click",function(){
			$.post(ajaxurl, {action:'cropimage', id:mediaitem, cropitem:cropitem, cropdata:cropdata  }, 
			    function(r){			//console.log(r);
					if(r.err < 0) { 
						$(".results").html("<strong>Error: </strong> Couldn't refine crop: "+r.msg); 
					} else { 
						console.log(r.w);
						$(".results").html("<strong>Success: </strong>"+r.w+"x"+r.h+" crop has been refined.<br />Clear your browser's cache and click the image name to see a preview."); 
					}
			    }
			);
		});
		
		$(".preview").on("mouseover",function(){
			$("#popover-preview").css({background: "url("+$(this).attr("rel")+"?"+Math.round(Math.random()*1000000)+") no-repeat center center", backgroundSize: "contain"});
			$("#popover p small").html($(this).attr("title"));
		});
		
		//pre-select first crop
		$(".cropper").first().click();
	}
	
	function showModal(){
		$("#modal-cropper, .media-modal-backdrop").show();
	};
	function hideModal(){
		$("#modal-cropper, .media-modal-backdrop").hide();
	};

	//init cropper
	function initCropper(){
		$(".container img").cropper({
			done: function(data) {
			    cropdata = data;
			},
			built: function(){
				$("#cropperimage img").css("opacity",1);
			}
		});
	};
	
	initCropper();
	
	if( typeof(mediaitem)!="undefined" ) cropRefine(mediaitem);
	
	//donate form
	var $donateform = jQuery("div.donate").html();
	jQuery("div.donate").html("<form action='https://www.paypal.com/cgi-bin/webscr' method='post' id='donate' target='_blank'>"+$donateform+"</form>").css("display","block");

});

	
