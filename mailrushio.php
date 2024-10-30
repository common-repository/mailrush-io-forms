<?php
/*
   Plugin Name: MailRush.io Forms
   Plugin URI: https://mailrush.io/
   Version: 1.3
   Author: <a href="https://mailrush.io/">MailRush.io</a>
   Description: Add Newsletter subscription forms to your WordPress using this Widget. Automatically add subscriptors to your MailRush.io campaigns. MailRush.io is an Email Marketing Tool with contact list management for sending Newsletters, Marketing Emails and Transactional Emails.
   Text Domain: MailRush.io
   License: GPLv3
*/

function mailrush_options_page() {

?>
    
    <div id="mailrush_ui_container">
        <div id="mailrush_ui_content">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" />
            <h2>Mailing List Subscription Forms Plugin for WordPress</h2>
            <p>Integrate your MailRush.io account to this WordPress by entering your API Key.<br />If you do not have an API Key, you can create your account at <a href="https://mailrush.io/" target="_blank">MailRush.io</a></p>
            <form method="post"  action="options.php">
                <?php settings_fields( 'mailrush_settings' ); ?>
                <?php mailrush_do_options(); ?>
                <div id="mailrush_buttons_section">
                    <p>
                        <input type="submit" class="button-primary" id="mailrush_save" value="<?php _e('Save Changes', 'mailrush') ?>"/>
                        <a class="button-primary" href="widgets.php">Add the Widget to your Website</a>
                    </p>
                    <p>
                        <a class="button-primary" href="https://app.mailrush.io/" target="_blank">MailRush Dashboard</a>
                    </p>
                </div>

            </form>
        </div>
    </div>
<?php
}
wp_enqueue_style('mailrush', plugin_dir_url( __FILE__ ) . '/css/mailrush_ui.css' );
function mailrush_menu() {
    add_menu_page(__('MailRush.io Forms', 'mailrush'), __('MailRush.io Forms', 'mailrush'), 'manage_options', basename(__FILE__), 'mailrush_options_page',plugin_dir_url( __FILE__ ) . 'images/dashboard_icon.svg');
}
add_action( 'admin_menu', 'mailrush_menu' );

function mailrush_init() {
	register_setting( 'mailrush_settings', 'mailrush', 'mailrush_validate' );
}
add_action( 'admin_init', 'mailrush_init' );

function mailrush_do_options() {
	$options = get_option( 'mailrush' );
    ob_start();

    ?>
    <div class="mailrush_ui_row"><?php _e( '<strong>Enter your MailRush.io API Key:</strong> ', 'mailrush' ); ?><input type="text" class="regular-text" id="mailrush_live_id" name="mailrush[mailrush_live_id]" value="<?php echo $options['mailrush_live_id']; ?>" /></div>
        <div id="mailrush_ui_tgroup" class="mailrush_ui_group">
            <div class="mailrush_ui_row"><?php _e( '<strong>Send Transactional Emails:</strong> ', 'mailrush' ); ?><input type="checkbox" id="mailrush_transactional" name="mailrush[mailrush_transactional]" value="Yes" <?php if($options['mailrush_transactional'] != null) { ?>checked="checked"<?php } ?> /></div>
        
    <?php
    echo "<p>Use for transactional Emails: ";
    if($options['mailrush_transactional'] != null){ echo "Yes"; } else { echo "No"; }
    echo "</p>";
    ?>
    </div>   
    <?php
    if($options['mailrush_live_id'] != "" ){
         $url = "http://app.mailrush.io/api/v1/campaign/list";
        $response = wp_remote_post( $url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array( 'apikey' => $options['mailrush_live_id'] ),
            'cookies' => array()
            )
        );
        
        if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
        } else {

            $obj = json_decode($response["body"], true);

            echo '';

            if($obj["code"] == "202"){
                echo '<div class="mailrush_ui_spa"><div class="mailrush_ui_spr"><h2>Campaign List</h2></div><div class="mailrush_ui_valid"></div> <span>API Connected.</span></div>';
                echo '<div class="mailrush_ui_group"><p><strong>Select the campaign you want to import your subscribers:</strong></p>';

                $arr = $obj["data"];
                function create_section_for_radio($arr,$checked_item) { 
      
                    foreach ($arr as $value) {
    
                        $checked = '';
                        if($checked_item == $value['id']){
                            $checked = 'checked="checked"';
                        }
                        echo '<input type="radio" name="mailrush[campaign]" value="' . $value['id'] . '" ' . $checked . '/> ' . $value["campaignname"] . '<br />';
                    }
                    echo '</div>';
                 }
                if (is_array($arr)){
                    create_section_for_radio($arr,$options["campaign"]);
                }
            } else {
                if($obj["code"] == "404"){
                    echo '<div class="mailrush_ui_spa"><div class="mailrush_ui_spr"><h2>Campaign List</h2></div><div class="mailrush_ui_spp"><div class="mailrush_ui_valid"></div> <span>API Connected.</span></div></div>';
                    echo '<div class="mailrush_ui_group"><a href="https://app.mailrush.io" target="_blank">Create a MailRush Campaign to get Started</a></div>';
                } else {
                    echo '<div class="mailrush_ui_spa"><div class="mailrush_ui_spr"><h2>Campaign List</h2></div><div class="mailrush_ui_spp"><div class="mailrush_ui_invalid"></div> <span>API Disconnected. Enter a valid API Key.</span></div></div>';
                }
                
            }
            
        }
    }   
}

function mailrush_validate($input) {

    $input['mailrush_live_id'] = wp_filter_nohtml_kses( $input['mailrush_live_id'] );
    $input['campaign'] = wp_filter_nohtml_kses( $input['campaign'] );
    $input['mailrush_transactional'] = wp_filter_nohtml_kses( $input['mailrush_transactional'] );

	return $input;
}

function mailrush_widget_enqueue_script() {
        $options = get_option( 'mailrush' );
        if($options['campaign'] != ""){
            wp_register_script('mailrush',"https://app.mailrush.io/assets/".$options['campaign']."/form.js");
        }
        wp_enqueue_script('mailrush');
        
}
add_action('wp_enqueue_scripts', 'mailrush_widget_enqueue_script');


function mailrush_add_id_to_script( $tag, $handle, $src ) {
   if ( 'mailrush' === $handle ) {
        $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '"></script>';
   }
   return $tag;
}

add_filter( 'script_loader_tag', 'mailrush_add_id_to_script', 10, 3 );

/////////////
// Creating wp_mail replacemente 

if( ! function_exists('wp_mail') ) {
    $options = get_option( 'mailrush' );
    if($options['mailrush_transactional'] == "Yes"){
        function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
            $options = get_option( 'mailrush' );
            //API Url
            $url = 'http://app.mailrush.io/api/v1/mail/send';
            $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => array( 
                    'apikey' => $options['mailrush_live_id'],
                    'from' => get_settings('admin_email'),
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $message,
                    'html' => $message),
                'cookies' => array()
                )
            );
            
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
    
                return false;
            } else {
    
                $obj = json_decode($response["body"], true);
    
                if($obj["code"] == "202"){
                    
                } else {
                    echo $obj["result"];
                }
                return true;
            }
        }
    }
} else {
    add_action( 'admin_notices', 'wp_mail_mailrush_fail' );

    /**
     * Display the notice that wp_mail function was declared by another plugin
    *
    * return void
    */
    function wp_mail_mailrush_fail()
    {
    echo '<div class="error"><p>' . __( 'Other plugin is using wp_mail function. Disable it in order to activate MailRush.io sending instead.' ) . '</p></div>';
    }

    return;
}


// Register and load the widget
function mailrush_load_widget() {
    register_widget( 'mailrush_widget' );
}
add_action( 'widgets_init', 'mailrush_load_widget' );
 
// Creating the widget 
class mailrush_widget extends WP_Widget {
 
function __construct() {
parent::__construct(
 
// Base ID of your widget
'mailrush_widget', 
 
// Widget name will appear in UI
__('MailRush.io Subscription Form', 'mailrush_widget_domain'), 
 
// Widget description
array( 'description' => __( 'MailRush.io Newsletter subscription form widget', 'mailrush_widget_domain' ), ) 
);
}

// Creating widget front-end
 
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
 
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];
 
// This is where you run the code and display the output
echo __( '<div class="mailrush_form"></div>', 'mailrush_widget_domain' );
echo $args['after_widget'];
}
         
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Subscribe', 'mailrush_widget_domain' );
}
// Widget admin form
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
}
     
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}


} // Class mailrush_widget ends here