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


include 'dntly_fields.php';

define('DNTLYSEED_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('DNTLYSEED_PLUGIN_PATH', __DIR__ );
define('DNTLYSEED_PLUGIN_BASENAME', plugin_basename(__FILE__));

/* FRONT END STYLES / SCRIPTS
================================================== */
function dntly_cf_front_scripts_styles(){
	//wp_register_script( 'dntlyseed-script', DNTLYSEED_PLUGIN_URL . 'donately-seed.js', array('jquery') );
	//wp_localize_script( 'dntlyseed-script', 'dntly_cf_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	//wp_enqueue_script( 'dntlyseed-script' );
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
  include_once( DNTLYSEED_PLUGIN_PATH . '/fundraiser_form.php');

}
add_shortcode( 'dntly_fundraiser_form', 'dntly_cf_build_fundraiser_form' );



/* WEEKLY GOAL
================================================== */

# curl -X GET https://fiftyandfifty.dntly.com/api/v1/admin/events.json \-u dt_d88ffc342dde5c195df4019f90d6ed:

add_action('init', 'dntly_cf_weekly_goal');
function dntly_cf_weekly_goal(){

  // CONFIG VARS
  $DEBUG              		= false;  // toggle debugging
  $total_amount       		= 5000;   // total weekly goal amount ( unformatted dollars )
  $update_interval        = 1;      // update interval (in hours)

  // dntly_options
  $dntly_options      		= get_option('dntly_options');
  $user               		= isset($dntly_options['user']) ? $dntly_options['user'] : null;
  $token              		= isset($dntly_options['token']) ? $dntly_options['token'] : null;
  $account            		= isset($dntly_options['account']) ? $dntly_options['account'] : null;

  // time stuff
  date_default_timezone_set('America/Los_Angeles'); //set default timezone
  $date_now         			= date('m/d/Y');
  $date_last_sun    			= date('m/d/Y', strtotime('last Sunday', strtotime($date_now)));
  $time_now         			= strtotime($date_now);
  $time_last_sun    			= strtotime($date_last_sun);
  $time_last_sun_ms 			= $time_last_sun * 1000;

  // headers/token auth
  $authorization  				= 'Basic ' . base64_encode("{$token}:");
  $dntly_cf_headers  		= array( 'Authorization' => $authorization, 'sslverify' => false );

  // decode json option hash
  $dntly_cf_amt_opt     	= get_option( 'seed_amount_weekly' );
  $dntly_cf_amt_opt_arr  = json_decode($dntly_cf_amt_opt, true);

  // protocol checking
  $protocol = is_SSL() ? 'https://' : 'http://';


  /* DNTLYSEED OPTIONS ARRAY
  ================================================== */
  global $DNTLYSEED_OPTS;
  $DNTLYSEED_OPTS = array(
  	'total_amount' 				=> $total_amount,
  	'total_amount_fmt'  	=> money_format('%.2n', $total_amount),
  	// times & dates
  	'date_now' 						=> $date_now,
  	'date_last_sun' 			=> $date_last_sun,
  	'time_now' 						=> $time_now,
  	'time_last_sun' 			=> $time_last_sun,
  	'time_last_sun_ms' 		=> $time_last_sun_ms,
  	// url, headers (token auth)
  	'api_url'           	=> $protocol.'theseedcompany.dntly.com/api/v1/admin/donations.json',
    // 'api_url'             => 'https://seed-test.dntly.com/api/v1/admin/donations.json',
  	'api_headers'       	=> $dntly_cf_headers,
  	// post variables
  	'post_vars' 					=> array(
	  		'count'         	=> 100,
		    'type'          	=> 'donation.created',
		    'created[gt]'   	=> $time_last_sun_ms,
		    'order'         	=> 'ASC'
  	),
  	// dntlyseed decoded opts 					
  	'weekly_amount'     	=> $dntly_cf_amt_opt_arr['amount_sum'],
  	'timestamp'         	=> $dntly_cf_amt_opt_arr['timestamp']
  );


  // TIMEOUT OVERRIDE
  /////////////////////////////////////////////////////////
  add_filter( 'http_request_timeout', 'dntly_cf_timeout_extd' );
  function dntly_cf_timeout_extd( $time ) {
    // Default timeout is 5
    return 10;
  }



  // GET JSON FROM DNTLY API
  /////////////////////////////////////////////////////////
  function dntly_cf_api_call_events( $url, $headers, $post_vars ){
    // get json and response (using wp_remote_get)
    $response       = wp_remote_get($url, array('headers' => $headers, 'body' => $post_vars));

    // var_dump($response);

    if ( is_wp_error($response) ) {
      echo $response->get_error_message();
    } else {

      $response_json  = json_decode($response['body'], true);
      $donations      = $response_json['donations'];

      // make array of response
      $data = array();
      
      foreach($donations as $key => $value) {
        $data[$key] = $value['amount_in_cents'];
      }
      return $data;
    }


  }
  // SUM THE ARRAY FROM JSON RESPONSE
  /////////////////////////////////////////////////////////
  function sum_response_array( $num_arr ) { 
    if (!isset($num_arr)) {
      return intval(0);
    } else {
      $amount_sum  = (array_sum($num_arr) / 100);
      return $amount_sum;
    }
  }
  // FORMAT THE SUM TO USD
  /////////////////////////////////////////////////////////
  function format_sum( $int ) {
    if ( !isset($int) ) {
      return NULL;
    } else {
      // $amount_sum_formatted = money_format('%.0n', $int);
      $amount_sum_formatted = number_format( $int );
      return $amount_sum_formatted;
    }
  }
  // CALCULATE PERCENTAGE OF WEEKLY GOAL
  /////////////////////////////////////////////////////////
  function percent_of_weekly( $sum, $weekly_goal ) {
    if ( !isset($sum) ) {
      return NULL;
    } else {
      $percent_of_goal = ( $sum / $weekly_goal ) * 100;
      return $percent_of_goal;
    }
  }
  // TIME DIFFERENCE
  /////////////////////////////////////////////////////////
  function time_difference( $time ){

    $seed_last_mod      = $time;
    $current_time       = time() * 1000;
    $one_hour_ago       = (strtotime('-1 hour') * 1000);

    $diff_s = (($current_time - $seed_last_mod) / 1000 );
    $diff_m = $diff_s / 60;
    $diff_h = $diff_m / 60;

    return $diff_h;

  }


  // UPDATE OPTION
  /////////////////////////////////////////////////////////
  function dntly_cf_update_options( $args ) {
    $diff = time_difference( $args['timestamp'] );

    // check if difference is >= 1 hour
    if ( $diff >= 1 ) {
      // get vars for api call
      $amounts_in_cents = dntly_cf_api_call_events( $args['api_url'], $args['api_headers'], $args['post_vars'] );

      // sum the array
      $amt_sum          = sum_response_array( $amounts_in_cents );
      // create array of amount and timestamp
	    $seed_weekly_amt = array(
	      'amount_sum'  => $amt_sum,
	      'timestamp'   => (time() * 1000)
	    );
	    // json encode the array
	    $seed_weekly_amt_json = json_encode($seed_weekly_amt);

      // check if option is same / exists
      if ( get_option( 'seed_amount_weekly' ) != $seed_weekly_amt_json ) {
          update_option( 'seed_amount_weekly', $seed_weekly_amt_json );
      } else {
          // create it if it doesn't
          $deprecated = ' ';
          $autoload = 'no';
          add_option( 'seed_amount_weekly', $seed_weekly_amt_json, $deprecated, $autoload );
      }

      return true;

    } else {

      return false;
    }
  }

 
  // INIT 
  /////////////////////////////////////////////////////////
  dntly_cf_update_options( $DNTLYSEED_OPTS );
  
               



  /* DISPLAY FUNCTIONS
  =============================================================== */
  function get_the_weekly_goal(){
    $seed_amt_opt       = get_option( 'seed_amount_weekly' );
    $seed_amt_opt_arr   = json_decode($seed_amt_opt, true);

    return $seed_amt_opt_arr['amount_sum'];
  }

    function dntly_cf_weekly_goal_formatted() {
      $amt_sum          = get_the_weekly_goal();
      $amt_formatted    = format_sum($amt_sum);

      return $amt_formatted;
    }

    function dntly_cf_weekly_goal_total(){
      global $DNTLYSEED_OPTS;

      $total_amount       = $DNTLYSEED_OPTS['total_amount'];
      $total_amount_fmt   = format_sum($total_amount);

      return $total_amount_fmt;
    }    

    function dntly_cf_weekly_goal_percentage() {
      global $DNTLYSEED_OPTS;

      $amt_total          = $DNTLYSEED_OPTS['total_amount'];
      $amt_sum            = get_the_weekly_goal();
      $amt_formatted      = format_sum($amt_sum);
      $amt_percentage     = percent_of_weekly( $amt_sum, $amt_total );
      $amt_percentage_fmt = number_format($amt_percentage, 2);

      return $amt_percentage_fmt;
    }



  /* TOTAL GIVEN FUNCTION
  ================================================== */
  function dntly_total_given( $account_id = NULL, $staging = false ) {

      if ( $staging ) {
        $url = 'http://www.dntly-staging.com/api/v1/public/accounts/'.$account_id.'.json';
      } else {
        $url = 'https://www.dntly.com/api/v1/public/accounts/'.$account_id.'.json';
      }

      $response = wp_remote_get( $url );

      if( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
      } else {
        $json = $response['body'];
        $json = json_decode($json, true);
        
        $total_donations                = $json['account']['donations_count'];
        $total_amount_raised            = $json['account']['amount_raised'];
        $total_amount_raised_formatted  = '$' . number_format( $total_amount_raised );

        echo $total_amount_raised_formatted;
      }
  }


  /* DEBUGGING 
  ================================================================ */
  // DEBUG - display all dntly options
  if ($DEBUG) {
    print '<h3><b>$dntly_options (get_option["dntly_options"]:</b></h3><pre>';
    print_r($dntly_options);
    print '</pre>';
  }
  // DEBUG - time stamps
  if ($DEBUG) {
    print '<h3><b>time manipulations: </b></h3><pre>';
    print ' DATE_NOW ' . $date_now . ' DATE_LAST_SUN ' . $date_last_sun . ' TIME NOW ' .  $time_now . ' TIME_LAST_SUN_MS ' . $time_last_sun_ms;
    print '</pre>';
  }
  // DEBUG - show the post variables
  if ($DEBUG) {
    print '<h3><b>post variables passed: </b></h3><pre>';
    print_r($dntly_vars);
    print '</pre>';
  }


}