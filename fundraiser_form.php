<?php
	wp_head();

  /* ====================================================================== */ 
  /*                                                                        */
  /*                              PHP (DNTLY API)                           */
  /*                                                                        */
  /* ====================================================================== */

  /* DNTLY CORE PLUGIN ACTIVATION CHECK
  ================================================== */
	if( !defined('DNTLY_VERSION') ){
		print "Donately Plugin must be activated.";
		die();
	}

  /* DNTLY FUNDRAISER API FUNCTIONS
  ================================================== */
	$dntly = new DNTLY_API;
	$create_user_and_fundraiser_url = $dntly->build_url("create_fundraiser");

	if( $campaign_id ){
		$fundraiser_campaign_select = "<input type='hidden' name='campaign' value='".$campaign_id."' />";
	}
	else{
		$dntly_campaigns = new WP_Query( array(
			'post_type' => isset($dntly->dntly_options['dntly_campaign_posttype'])?$dntly->dntly_options['dntly_campaign_posttype']:'dntly_campaigns',
			'post_status' => 'publish',
			'orderby' => 'title', 
			'order' => 'ASC',
		) );	
		$dntly_account_options = '';
		foreach( $dntly_campaigns->posts as $a ){
			$dtnly_id = get_post_meta($a->ID, '_dntly_id', true);
			$dntly_account_options .=	"<option value='{$dtnly_id}' ".selected($campaign_id, $dtnly_id, false).">{$a->post_title}</option>";
		}
		$fundraiser_campaign_select = "<div class='left'><label for=''>Select a Campaign*</label><select name='campaign' class='input-medium required'>" . $dntly_account_options . "</select></div>";					
	}
?>



<?php 
/* ====================================================================== */ 
/*                                                                        */
/*                               JAVASCRIPT                               */
/*                                                                        */
/* ====================================================================== */ ?>

<script type="text/javascript">
	
  /* INIT VARS
  ================================================== */
	var fundraiser_form,
		email, 
		password,
		title,
		description,
		campaign,
		goal;

  /* IE COMPATIBILITY
  ================================================== */
  function isIE8orIE9() {
    return !!( ( (/msie 8./i).test(navigator.appVersion) || (/msie 9./i).test(navigator.appVersion)  ) && !(/opera/i).test(navigator.userAgent) && window.ActiveXObject && XDomainRequest && !window.msPerformance );
  }

  /* MAKE AJAX REQUEST
  ================================================== */
	function make_ajax_request(url, data, request_type){
		// console.log('make_ajax_request');
		var result;
		if ( isIE8orIE9() ) {
      var xdr = new XDomainRequest();
      xdr.timeout = 10000;
      xdr.open("post", url);
      xdr.onerror = function() {
          handle_response(response.error);
      }
      xdr.ontimeout = function() { /* only needed for IE9 support */ }
      xdr.onprogress = function() { /* only needed for IE9 support */ }
      xdr.onload = function() {
        var dom = new ActiveXObject("Microsoft.XMLDOM");
        dom.async = false;
        dom.loadXML(xdr.responseText);
        var response = JSON.parse(dom.parseError.srcText);
        if(response.success){ handle_response(xdr.responseText, request_type); }
        else{ handle_response(xdr.responseText, request_type, true); }
      };
      xdr.send(jQuery.param(data));
		} 
		else {
			result = jQuery.ajax({
				'type'       : 'post',
				'url'        : url,
				'data'       : data,
				'async'      : false,
				'error'      : function(response) { handle_response(response, request_type, true); },
				'success'    : function(response) { handle_response(response, request_type); }
			})
		}
	}

  /* DISPLAY ERRORS
  ================================================== */
	function display_errors(message){
		if( typeof(message) != 'string' ){
			alert("Error\n\nConnection Error");
		}
		else {
			alert( "Error\n\n" + message );
		}
	}

  /* HANDLE RESPONSE
  ================================================== */
	function handle_response(response, type, error){
		// console.log('handle_response');
		// console.log(response, type, error);
		if(type === undefined){type = 'user_and_fundraiser';}
		if(error === undefined){error = false;}
		try{
			var r = JSON.parse(response);
		}
		catch(e){
			var r = response;
		}
		if(error || !r.success){
			if( typeof(r.error) == 'undefined' ){console.log(response);r = {};r.error = {};r.error.message = 'unknown error ['+type+']'}
			else if( r.error.message === undefined ){console.log(r);r.error.message = r.error;}
			display_errors(r.error.message);
		}
		else{
			if( type == 'fundraiser_create_local_extended' ){
				return after_fundraiser_create_local(r);
			}
			else{
				return after_fundraiser_create_remote(r);
			}
		}
	}


  /* LOADING SPINNER
  ================================================== */
  function dntly_spinner() {
		var opts = {lines: 8,length: 5,width: 3,radius: 6,corners: 1,rotate: 0,direction: 1,color: '#000',speed: 1.2,trail: 40,shadow: false,hwaccel: false,className: 'dntly_submit_spinner',zIndex: 2e9,top: 'auto',left: 'auto'};
		var spinner = new Spinner(opts).spin();
		return spinner;
  }
  function show_spinner(){
  	var s = dntly_spinner();
  	jQuery('#fcm').append(s.el);
  }
  function hide_spinner(){
  	jQuery('#fcm').find('.dntly_submit_spinner').remove();
  }


  /* [1] CREATE USER AND FUNDRAISER
  ================================================== */
	function create_user_and_fundraiser(){
			var goal_clean = goal.replace("$", ""); goal_clean = goal_clean.replace(",", "");
			if (goal_clean.indexOf(".") >= 0){
				var goal_in_cents = goal_clean.replace(".", "");
			}
			else{
				var goal_in_cents = Math.round(parseInt(goal_clean * 100, 10));
			}
			var data            = {
				'email'             : email,
				'title'             : title,
				'description'       : description,
				'campaign_id'       : campaign,
				'goal_in_cents'     : goal_in_cents
			};
			make_ajax_request(user_and_fundraiser_url, data, 'user_and_fundraiser');
	}


  /* [2] AFTER FUNDRAISER CREATE REMOTE
  ================================================== */
  function after_fundraiser_create_remote(r){

    data_cf_post_id = jQuery("input#post_id_hidden").val(),
    data_cf_nonce   = jQuery("input#fundraiser_nonce").val(),
    data_cf_city    = jQuery("input[name='city']").val();
    
    data = {
      'action'        : 'dntly_create_fundraiser_extended',
      'dntly_result'  : r,
      'postID'        : data_cf_post_id,
      'city'          : data_cf_city
    }

    make_ajax_request(dntly_ajax.ajaxurl, data, 'fundraiser_create_local_extended');

  }

  /* [3] AFTER FUNDRAISER CREATE LOCAL (DOM MANIP)
  ================================================== */
  function after_fundraiser_create_local(r){


    alert(r.url);

  }

  /* GET FUNDRAISER DATA
  ================================================== */
	function get_fundraiser_data(){
		email           = jQuery("input[name=email]", fundraiser_form).val();
		title           = jQuery("input[name=title]", fundraiser_form).val();
		description     = jQuery("textarea[name=description]", fundraiser_form).val();
		campaign     		= jQuery("select[name=campaign] option:selected", fundraiser_form).val() || jQuery("input[name=campaign]", fundraiser_form).val();
		goal 						= jQuery("input[name=goal]", fundraiser_form).val();
		if(!email){
			alert("Error\n\nPlease provide an email address.");
			return false;
		}
		if(!title){
			alert("Error\n\nPlease provide a title for your fundraiser");
			return false;
		}
		if(!goal){
			alert("Error\n\nPlease provide a goal for your fundraiser");
			return false;
		}
		if(!campaign){
			alert("Error\n\nPlease choose a campaign.");
			return false;
		}
		return true;
	}

  /* INITIALIZE FUNDRAISER FORM
  ================================================== */
	function intialize_fundraiser_form(){
		user_and_fundraiser_url   = "<?php print $create_user_and_fundraiser_url ?>";
		fundraiser_form     			= jQuery('#dntly_fundraiser');
		fundraiser_form.find('#submit_btn').bind('click', function(e) {
			e.preventDefault();
			show_spinner();
			if( get_fundraiser_data() ){
				create_user_and_fundraiser()
			}
			hide_spinner();
			return false;
		});
	}
  // DOM READY INIT
	jQuery(document).ready(function($) {
		intialize_fundraiser_form();
	});

</script>



<?php 
/* ====================================================================== */ 
/*                                                                        */
/*                                HTML                                    */
/*                                                                        */
/* ====================================================================== */ ?>


<?php /* INLINE STYLES
================================================== */ ?>
<style type="text/css">
	#fundraiser_success {
		display: none;
	}
</style>


<?php /* FUNDRAISER FORM HTML
================================================== */ ?>
<div class="fundraiser_form_wrapper">
	<form action="" class="" id="dntly_fundraiser">
		<fieldset class="">
			<input type="hidden" style="display:none;" name="post_id_hidden" id="post_id_hidden" value="<?php global $post; echo $post->ID; ?>">
			<!-- <input type="hidden" style="display:none;" name="post_id_hidden" id="post_id_hidden" value="<?php // global $post; echo $post->ID; ?>"> -->

			 <?php wp_nonce_field('create_fundraiser','fundraiser_nonce'); ?>
			
			<label for="">Campaign Title *</label>
			<input type="text" required name="title" class="input-medium required"><br/>
				
			<label for="">Your Email Address *</label>
			<input type="email" required name="email" class="input-medium required"><br/>
			
			<label for="">City *</label>
			<input type="text" required name="city" class="input-medium required"><br/>

			<?php print $fundraiser_campaign_select ?>

			<input type="number" id="goal-number" style="display:none" required name="goal" class="input-medium required" maxlength="7" value="1"/>

			<label for="">Description </label>
			<textarea name="description" required>Fundraiser/Campaign Content goes here.</textarea>

 			

			<div id="fcm"></div>

			<a class="fundraiser_btn" class="submit" id="submit_btn">Create Campaign</a>			
		</fieldset>
	</form>
</div>
<div id="fundraiser_success">
	<!-- <a href="" class="visit_btn">Visit Your Page</a> <a href="" class="share_btn">Share your Page</a> -->
	<!-- <a href="/fundraisers" target="_top" class="fundraiser_btn">Find Your Fundraiser</a> -->
	<p>Thank you for signing up. You'll find your page <a href="/fundraisers" target="_top" style="color:#DD3727">here</a> within the next hour.</p>

</div>
<div class="clear"></div>