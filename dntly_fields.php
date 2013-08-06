<?php 
/* 

Description:  Donately Fields Helper Functions
Author:       5ifty&5ifty - A humanitarian focused creative agency
Author URI:   http://www.fiftyandfifty.org/
Contributors: Alexander Zizzo, Bryan Shanaver

TODO:
--------------
- amount raised redundancy
- more elegant campaign id depending on fundraiser view

*/

class Dntly_Fields {

  private $dntly_data;
  private $dntly_camp_id;
  private $dntly_account_id;
  private $dntly_environment;

  function __construct() {
    // set locale for currenct conversion
    setlocale(LC_MONETARY, 'en_US');

    // needed for $post->ID
    global $post;

    // dntly fields
    $this->dntly_data         = get_post_meta($post->ID, '_dntly_data', true);
    $this->dntly_camp_id      = get_post_meta($post->ID, '_dntly_id', true);
    $this->dntly_campaign_id  = get_post_meta($post->ID, '_dntly_campaign_id', true);
    $this->dntly_account_id   = get_post_meta($post->ID, '_dntly_account_id', true);
    $this->dntly_environment  = get_post_meta($post->ID, '_dntly_environment', true);
  }
  
  function dntly_data(){
    return $this->dntly_data;
  }
  function dntly_environment(){
    return $this->dntly_environment;
  }

  function dntly_account_id(){
    return $this->dntly_account_id;
  }

  function dntly_account_title(){
    if ( isset($this->dntly_data['account_title']) ) {
      $account_title = $this->dntly_data['account_title'];
    } else {
      $account_title = null;
    }
    return $account_title;
  }

  function dntly_campaign_id() {
    return $this->dntly_camp_id;
  }
  function dntly_parent_campaign_id(){
    return $this->dntly_campaign_id;
  }


  function dntly_campaign_goal() {      
    if ( isset($this->dntly_data['campaign_goal']) ) {
      // if it is set and not NULL, get campaign goal integar.
      $campaign_goal = intval($this->dntly_data['campaign_goal']);
    } else {
      // otherwise set it to zero.
      $campaign_goal = intval(0);
    }
    return $campaign_goal;
  }

  function dntly_fundraiser_goal() {      
    if ( isset($this->dntly_data['goal']) ) {
      // if it is set and not NULL, get campaign goal integar.
      $goal = intval($this->dntly_data['goal']);
    } else {
      // otherwise set it to zero.
      $goal = intval(0);
    }
    return $goal;
  }

  function dntly_donations_count() {      
    if ( isset($this->dntly_data['donations_count']) ) {
      // if it is set and not NULL, get campaign goal integar.
      $donations_count = intval($this->dntly_data['donations_count']);
    } else {
      // otherwise set it to zero.
      $donations_count = intval(0);
    }
    return $donations_count;
  }


  function dntly_donors_count() {      

    if ( isset($this->dntly_data['donors_count']) ) {
      // if it is set and not NULL, get campaign goal integar.
      $donors_count = intval($this->dntly_data['donors_count']);
    } else {
      // otherwise set it to zero.
      $donors_count = intval(0);
    }
    return $donors_count;
  }


  function dntly_amount_raised() {      

    if ( isset($this->dntly_data['amount_raised']) ) {
      // if it is set and not NULL, get fundraiser amount raised integar.
      $amount_raised = intval($this->dntly_data['amount_raised']);
    } else {
      // otherwise set it to zero.
      $amount_raised = intval(0);
    }
    return $amount_raised;
  }

  function dntly_percentage_raised() {

    $campaign_goal = $this->dntly_campaign_goal();
    $amount_raised = $this->dntly_amount_raised();

    if ( $campaign_goal ) {
      $percentage_raised = number_format( $amount_raised / $campaign_goal * 100 );
    } else {
      $percentage_raised = intval(0);
    }

    return $percentage_raised;
  }

  function dntly_fundraiser_percentage_raised() {

    $fundraiser_goal = $this->dntly_fundraiser_goal();
    $amount_raised   = $this->dntly_amount_raised();

    if ( $fundraiser_goal ) {
      $percentage_raised = number_format( $amount_raised / $fundraiser_goal * 100 );
    } else {
      $percentage_raised = intval(0);
    }

    return $percentage_raised;
  }

  // META GENERAL
  function dntly_meta_general() {

    if ( isset($post) && is_object($post) && isset($post->ID) && !empty($post->ID)) {
      $meta_general = get_post_meta($post->ID, '_meta_general', true);
    } else {
      $meta_general = null;
    }

    return $meta_general;
  }

  // TRACKING_CODES
  function dntly_tracking_codes() {

    if ( isset($this->dntly_data['tracking_codes']) ) {
     $tracking_codes = $this->dntly_data['tracking_codes'];
    } else {
     $tracking_codes = null;
      // $track_codes = var_dump($this->dntly_data['tracking_codes']);
    }
    return $tracking_codes;
  }

}
























/* ============================================================= */
/*                      Wordpress Functions                      */
/* ============================================================= */

add_action('init', 'dntly_helper_functions');

function dntly_helper_functions(){

  /* ACCOUNT ID
  ================================================== */
  function get_the_account_id(){
    $df = new Dntly_fields;
    return $df->dntly_account_id;
  }
  function dntly_account_id(){
    echo get_the_account_id();
  }

  /* ACCOUNT TITLE
  ================================================== */
  function get_the_account_title(){
    $df = new Dntly_fields;
    return $df->dntly_account_title;
  }
  function account_title(){
    echo get_the_account_title();
  }

  /* CAMPAIGN & FUNDRAISER ('parent') ID
  ================================================== */
  function get_the_campaign_id( $scope = NULL ) {
    $df = new Dntly_Fields;

    if ( isset($scope) && $scope == 'parent') {
      return $df->dntly_parent_campaign_id();
    } else {
      return $df->dntly_campaign_id();
    }
  }
  function campaign_id( $scope = NULL ){
    if ( isset($scope) && $scope == 'parent') {
      echo get_the_campaign_id('parent');
    } else {
      echo get_the_campaign_id();
    }
    
  }
  /* CAMPAIGN GOAL
  ================================================== */
  function get_the_campaign_goal(){
    $df = new Dntly_Fields;
    return $df->dntly_campaign_goal();
  }
  function campaign_goal(){

    $campaign_goal      = get_the_campaign_goal();
    $campaign_goal_usd  = number_format( $campaign_goal );

    echo '$'.$campaign_goal_usd;
  }

  /* FUNDRAISER ID
  ================================================== */
  function get_the_fundraiser_id(){
    return get_the_campaign_id();
  }
  function fundraiser_id(){
    echo campaign_id();
  }


  /* FUNDRAISER GOAL
  ================================================== */
  function get_the_fundraiser_goal(){
    $df = new Dntly_Fields;
    return $df->dntly_fundraiser_goal();
  }
  function fundraiser_goal(){

    $fundraiser_goal      = get_the_fundraiser_goal();
    $fundraiser_goal_usd  = number_format( $fundraiser_goal );

    echo '$'.$fundraiser_goal_usd;
  }

  /* DONATION COUNT
  ================================================== */
  function get_the_donations_count(){
    $df = new Dntly_Fields;
    return $df->dntly_donations_count();
  }
  function donations_count(){
    echo get_the_donations_count();
  }

  /* DONORS COUNT
  ================================================== */
  function get_the_donors_count(){
    $df = new Dntly_Fields;
    return $df->dntly_donors_count();
  }
  function donors_count(){
    echo get_the_donors_count();
  }

  /* AMOUNT RAISED
  ================================================== */
  function get_the_amount_raised(){
    $df = new Dntly_Fields;
    return $df->dntly_amount_raised();
  }
  function amount_raised( $format = NULL ){
    if ( isset($format) && $format == 'usd' ) {
      $amount_raised      = get_the_amount_raised();
      $amount_raised_usd  = money_format('%.2n', $amount_raised);
      echo $amount_raised_usd;
    } else {
      echo get_the_amount_raised();
    }
  }

  /* CAMPAIGN PERCENTAGE RAISED
  ================================================== */
  function get_the_percentage_raised(){
    $df = new Dntly_Fields;
    return $df->dntly_percentage_raised();
  }
  function percentage_raised($type = NULL){
    echo get_the_percentage_raised();
  }

  /* FUNDRAISER PERCENTAGE RAISED
  ================================================== */
  function get_the_fundraiser_percentage_raised(){
    $df = new Dntly_Fields;
    return $df->dntly_fundraiser_percentage_raised();
  }
  function fundraiser_percentage_raised(){
    echo get_the_fundraiser_percentage_raised();
  }

  /* META GENERAL
  ================================================== */
  function get_the_meta_general(){
    $df = new Dntly_fields;
    return $df->dntly_meta_general();
  }
  function meta_general(){
    echo get_the_meta_general();
  }

  /* TRACKING CODES
  ================================================== */
  function get_the_tracking_codes(){
    $df = new Dntly_fields;
    return $df->dntly_tracking_codes();
  }
  function dntly_tracking_codes(){
    echo get_the_tracking_codes();
  }

  
}
