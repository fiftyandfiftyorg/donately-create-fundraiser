<?php 


// define('DNTLYSEED_PLUGIN_URL', plugin_dir_url( __FILE__ ));
// define('DNTLYSEED_PLUGIN_PATH', __DIR__ );
// define('DNTLYSEED_PLUGIN_BASENAME', plugin_basename(__FILE__));


add_action('init', 'dntly_get_option');

function dntly_get_option(){

  function dntly_get_opt(){

    function pp($var) { echo '<pre>'; print_r($var); echo '</pre>'; }

    $post_vars = array(
      'count'             => 100,
      // 'description'       => 'church_true',
    );
    $filter = http_build_query($post_vars);

    // init curl
    $process = curl_init('http://theseedcompany.dntly.com/api/v1/admin/fundraisers.json?'.$filter);

    curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ); 
    curl_setopt($process, CURLOPT_USERPWD, 'b384c98ff1bf9273c6b4929346d87b90' ); 
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
    $return = curl_exec($process);
    curl_close($process); 

    $response_json  = json_decode($return, true);
    $fundraisers    = $response_json['fundraisers'];

    pp($fundraisers);

    // make array of response
    $data = array();
    $sum = 0;

    foreach($fundraisers as $key => $value) {

      $data[$key] = $value['description'];

      // $sum += $cents[$key];

    }

    // pp($data);

    return $data;

    $dollars = $sum/100;
    
    setlocale(LC_MONETARY, 'en_US');
    // echo money_format('%(#10n', $dollars);
  }


}