<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              contact_form
 * @since             1.0.0
 * @package           contact_form
 *
 * @wordpress-plugin
 * Plugin Name:       contact_form
 * Plugin URI:        #
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Dan Mobbs
 * Author URI:        da
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       contact_form
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH') ) {
    die;
}

class ContactForm
{
    
    public function __construct() 
    {
        add_action( 'init', array($this, 'create_custom_post_type' ));
        add_action( 'wp_enqueue_scripts', array($this, 'load_assets' ));
        add_shortcode( 'contact-form', array($this, 'load_shortcode' ));
        add_action( 'wp_footer', array($this, 'load_scripts' ));
        add_action( 'rest_api_init', array($this, 'register_rest_api' ));
    }

    public function create_custom_post_type() 
    {
       $args = array(
            'public'                => true,
            'has_archive'           => true,     
            'supports'              => true,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability'            => 'manage_options',
            'labels'                => array(
                                    'name'          => 'Contact Form',
                                    'singular_name' => 'Contact Form Entry'
            ),
            'menu_icon'             => 'dashicons-email-alt2'
       );

       register_post_type('contact_form_pt', $args);
    }

    public function load_assets() 
    {
        wp_enqueue_style(
            'contact-form', 
            plugin_dir_url( __FILE__ ) . '/style/custom.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_script(
            'contact-form', 
            plugin_dir_url( __FILE__ ) . '/JS/app.js',
            array('jquery'),
            1,
            true
        );
    }

    public function load_shortcode()
    {
    ?>

    <div class="form_wrapper">
        <div class="form_header">
            <h1>Give me your email, now!</h1>
            <p>Please fill in the form</p>
        </div>    
        <form id="contact-form_form">
            <input name="name" class="input" type="text" placeholder="Name">
            <input name="email" class="input" type="email" placeholder="Email">
            <input name="phone" class="input" type="text" placeholder="Phone">
            <textarea name="message" class="input" placeholder="Message"></textarea>
            <button class="btn" type="submit">Send</button>
        </form>
    </div>    
    
    <?php
    }

    public function load_scripts() 
    {
    ?>
        <script>

            var nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';

            (function($){

                $('#contact-form_form').submit( function(event) {
                    event.preventDefault();
                   
                    var form = $(this).serialize();

                    $.ajax({
                        method: 'post',
                        url: '<?php echo get_rest_url(null, 'contact-form/v1/send-email'); ?>',
                        headers: { 'X-WP-Nonce': nonce }, 
                        data: form
                    })
                });

            })(jQuery)    
        </script>
    <?php
    }

    public function register_rest_api() 
    {
        register_rest_route( 'contact-form/v1', 'send-email', array(
            'methods'   =>  'POST',
            'callback'  =>  array($this, 'handle_contact_form')
        ));
    }

    public function handle_contact_form($data) 
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];
        
        if(!wp_verify_nonce($nonce, 'wp_rest')) 
        {
            return new WP_REST_Response('Message not sent', 422);
        }

        $wp_id = wp_insert_post([
                'post_type'         => 'contact_form_pt',
                'post_title'        => 'Contact enquiery',
                'post_status'       => 'publish'
        ]);

        if($post_id)
        {
            return new WP_REST_Response('Thank you for your email', 200);
        }
    }
}

$contact_Form = new ContactForm;
