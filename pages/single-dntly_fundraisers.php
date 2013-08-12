<?php get_header(); ?>


<?php if( have_posts() ) : while ( have_posts() ) : the_post(); ?>

      <?php 
        // fundraisers = projects,
        // # of fundraisers = # of map markers
        $dntly_campaign_id = get_the_campaign_id('parent');
        $campaigns_args = array(
          'post_type'     =>  'dntly_campaigns',
          'meta_query' => array(
            array(
              'key'   => '_dntly_id',
              'value' => $dntly_campaign_id,
            )
          ),
          'nopaging'      =>  true
        );
        $campaigns = new WP_Query($campaigns_args);
        //if( have_posts() ) : while ( have_posts() ) : the_post();
        // if( $campaigns->have_posts() ) : while( $campaigns->have_posts() ) : $campaigns->the_post();
      ?>


      <pre>
        <?php 
          $post_meta = get_post_meta($post->ID);
          var_dump($post_meta);
        ?>
      </pre>
  
 <?php endwhile; endif; wp_reset_postdata(); ?>


<?php get_footer(); ?>