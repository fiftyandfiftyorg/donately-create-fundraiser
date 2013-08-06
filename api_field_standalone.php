<?php

require_once 'WP_Http.php';

/* WEEKLY GOAL
================================================== */

# curl -X GET https://fiftyandfifty.dntly.com/api/v1/admin/events.json \-u dt_d88ffc342dde5c195df4019f90d6ed
# curl -X GET https://fiftyandfifty.dntly.com/api/v1/admin/events.json \-u b384c98ff1bf9273c6b4929346d87b90


function dntly_api_field(){


  // wp remote get
  function remote_get($url, $args = array()) {
    $objFetchSite = new WP_Http();
    return $objFetchSite->get($url, $args);
  }
  function wp_parse_args( $args, $defaults = '' ) {
  if ( is_object( $args ) )
      $r = get_object_vars( $args );
    elseif ( is_array( $args ) )
      $r =& $args;
    else
      wp_parse_str( $args, $r );

    if ( is_array( $defaults ) )
      return array_merge( $defaults, $r );
    return $r;
  }

  function apply_filters() {}

  function is_wp_error($thing) {
    if ( is_object($thing) && is_a($thing, 'WP_Error') )
      return true;
    return false;
  }

  // CONFIG VARS
  $DEBUG                  = false;  // toggle debugging
  $total_amount           = 5000;   // total weekly goal amount ( unformatted dollars )
  $update_interval        = 1;      // update interval (in hours)

  // dntly_options
  $user                   = 'alex@fiftyandfifty.org'; // not used
  $token                  = 'b384c98ff1bf9273c6b4929346d87b90';

  // time stuff
  date_default_timezone_set('America/Los_Angeles'); //set default timezone
  $date_30_days_ago       = date('Y-m-d', strtotime('-30 days'));
  $date_30_days_ago_unix  = strtotime($date_30_days_ago);
  $date_30_days_ago_ms    = $date_30_days_ago_unix * 1000;


  // headers/token auth
  $authorization          = 'Basic ' . base64_encode("{$token}:");
  $dntly_headers          = array( 'Authorization' => $authorization, 'sslverify' => false );


  /* DNTLYSEED OPTIONS ARRAY
  ================================================== */
  global $OPTIONS;
  $OPTIONS = array(
    // times & dates
    'date_30d'            => $date_30_days_ago_ms,
    // api
    'api_url'             => 'http://dntly.com/api/v1/admin/donations.json',
    'api_headers'         => $dntly_headers,
    // post variables
    'post_vars'           => array(
        'count'           => 100,
        'type'            => 'donation.created',
        'created[gt]'     => $date_30_days_ago_ms,
        'order'           => 'ASC'
    )
  );


  // GET JSON FROM DNTLY API
  /////////////////////////////////////////////////////////
  function wp_api_call( $url, $headers, $post_vars ){
    // get json and response (using wp_remote_get)
    $response       = remote_get($url, array('headers' => $headers, 'body' => $post_vars));


    // var_dump($response);

    if ( is_wp_error($response) ) {
      echo $response->get_error_message();
    } else {

      $response_json  = json_decode($response['body'], true);
      $donations      = $response_json['donations'];

      // var_dump($donations);

      // make array of response
      $data = array();
      
      foreach($donations as $key => $value) {
        $data[$key] = $value['amount_in_cents'];
      }
      return $data;
    }
  }

  

 
  // INIT 
  /////////////////////////////////////////////////////////
  $api_response = wp_api_call( $OPTIONS['api_url'], $dntly_headers, $OPTIONS['post_vars'] );
  // $api_response = api_call( 'https://dntly.com/api/v1/admin/donations.json', $OPTIONS['post_vars'], $authorization, $date_30_days_ago_ms );

  var_dump($api_response);



}



dntly_api_field();
