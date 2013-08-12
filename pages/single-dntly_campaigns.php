<?php 
/*
Template Name: Single Fundraiser
*/
	get_header(); 
?>

<?php if( have_posts() ) : while ( have_posts() ) : the_post(); ?>


<?php // get campaign id then pass it to shortcode
	$cid = get_the_campaign_id();
	echo do_shortcode("[dntly_fundraiser_form cid=$cid tracking_codes='true']"); 
?>


<pre>
  <?php //var_dump($dntly_options) ?>
</pre>


<pre>
  <?php 
    $post_meta = get_post_meta($post->ID);
    var_dump($post_meta);
  ?>
</pre>

<?php endwhile; endif; wp_reset_postdata(); ?>
		

<?php get_footer(); ?>

