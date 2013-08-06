<?php 
  // Create a stream
  $opts = array(
    'http'=>array(
      'method'=>"GET",
      'header'=>$headers
    )
  );
  $context = stream_context_create($opts);
  // Open the file using the HTTP headers set above
  $file = file_get_contents($url, false, $context);

 ?>





 <?php 

    $postdata = http_build_query(
        array(
          'count'           => 100,
          'type'            => 'donation.created',
          'created[gt]'     => $date,
          'order'           => 'ASC'
        )
    );

    $opts = array('https' =>
        array(
            'method'  => 'GET',
            'header'  => $header,
            'body' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    $result = file_get_contents($url, false, $context);

    return $result;

  ?>






  <?php 


$opts = array('ftp' => array( 
        'proxy' => 'tcp://vbinprst10:8080', 
        'request_fulluri'=>true, 
        'header' => array( 
            "Proxy-Authorization: Basic $auth"
            ) 
        ), 
        'http' => array( 
        'proxy' => 'tcp://vbinprst10:8080', 
        'request_fulluri'=>true, 
        'header' => array( 
            "Proxy-Authorization: Basic $auth"
            ) 
        ) 
    ); 


   ?>