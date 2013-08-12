<?php

/*
Plugin Name:  Donately Create Fundraiser
Plugin URI:   http://www.fiftyandfifty.org
Description:  API Integration with the Donately donation platform for remote creation of fundraisers via the API
Version:      1.0.0
Author:       5ifty&5ifty

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of Alex Moss or pleer nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


define('DNTLY_CF_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('DNTLY_CF_PLUGIN_PATH', __DIR__ );
define('DNTLY_CF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/* FRONT END STYLES / SCRIPTS
================================================== */
function dntly_cf_front_scripts_styles(){
  // ajax localize already added from main donatly plugin
	wp_register_script( 'dntly-cf-script', DNTLY_CF_PLUGIN_URL . 'lib/dntly-cf.js', array('jquery') );
	wp_enqueue_script( 'dntly-cf-script' );
}
add_action('wp_enqueue_scripts', 'dntly_cf_front_scripts_styles');


/* BUILD FUNDRAISERS
================================================== */
function dntly_cf_build_fundraiser_form( $atts ){

  $dntly_options = get_option('dntly_options');
  $dntly_options['account'] = isset($dntly_options['account']) ? $dntly_options['account'] : null;

  extract( shortcode_atts( array(
    'account' => null,
    'cid' => null
  ), $atts ) );
  
  $account = $account != '' ? sanitize_text_field($account) : null;
  $campaign_id = $cid != '' ? sanitize_text_field($cid) : null;
  $dntly_options = get_option('dntly_options');
  include_once( DNTLY_CF_PLUGIN_PATH . '/fundraiser_form.php');

}
add_shortcode( 'dntly_fundraiser_form', 'dntly_cf_build_fundraiser_form' );



/* AJAX FUNCTION
================================================== */
function dntly_create_fundraiser_extended(){
  // print 'dntly_create_fundraiser_extended';
  // print_r($_REQUEST);

  $dntly = new DNTLY_API;

  $dntly_result = $dntly->array_to_object($_POST['dntly_result']);

  if( $dntly_result->success ){
    if( isset($dntly_result->fundraiser->id) ){
      $post_id = $dntly->add_update_fundraiser($dntly_result->fundraiser, $dntly->dntly_account_id);
      $permalink = get_permalink($post_id);   
      print json_encode(array('success' => true, 'url' => $permalink)); 
    }
    else{
      print json_encode(array('success' => false, 'message' => 'Error finding new fundraiser url' ));
    }
  }
  else{
    print json_encode(array('success' => false, 'message' => $dntly_result->error->message ));
  }

  $city     = $_POST['city'];

  update_post_meta( $post_id, '_dntly_cf_data', $city );

  die();

}
add_action('wp_ajax_dntly_create_fundraiser_extended','dntly_create_fundraiser_extended');
add_action('wp_ajax_nopriv_dntly_create_fundraiser_extended','dntly_create_fundraiser_extended');
