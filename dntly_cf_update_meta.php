<?php
  /* Post Meta */
  $data = ( isset($_POST['dntly_cf_data']) ? $_POST['dntly_cf_data'] : null);
  update_post_meta( $post_id, '_dntly_cf_data', $data );
  return $data;