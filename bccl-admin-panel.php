<?php

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}



function bccl_show_info_msg($msg) {
    echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}


/*
* Construct the Creative Commons Configurator administration panel under Settings->License
*/


function bccl_admin_init() {

    // Here we just add some dummy variables that contain the plugin name and
    // the description exactly as they appear in the plugin metadata, so that
    // they can be translated.
    $bccl_plugin_name = __('Creative Commons Configurator', 'creative-commons-configurator-1');
    $bccl_plugin_description = __('Helps you publish your content under the terms of Creative Commons and other licenses.', 'creative-commons-configurator-1');

    // Perform automatic settings upgrade based on settings version.
    // Also creates initial default settings automatically.
    // NOTE: Reverted back to the test performed in bccl-settings.php, because otherwise
    // the ``is_array($options) && array_has_key('foo', $options)`` should be performed
    // every time a setting value is retrieved from the settings array.
    //bccl_plugin_upgrade();

    // Register scripts and styles

    /* Register our script for the color picker. */
    wp_register_script( 'wp-color-picker-script', plugins_url( 'js/color-picker-script.js', BCCL_PLUGIN_FILE ), array( 'wp-color-picker' ), false, true );
    /* Register our stylesheet. */
    // wp_register_style( 'myPluginStylesheet', plugins_url('stylesheet.css', BCCL_PLUGIN_FILE) );

}
add_action( 'admin_init', 'bccl_admin_init' );


function bccl_admin_menu() {
    /* Register our plugin page */
    add_options_page(
        __('License Settings', 'creative-commons-configurator-1'),
        __('License', 'creative-commons-configurator-1'),
        'manage_options',
        'cc-configurator-options',
        'bccl_options_page'
    );
}
add_action( 'admin_menu', 'bccl_admin_menu');


/** Enqueue scripts and styles
 *  From: http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts#Example:_Target_a_Specific_Admin_Page
 */
function bccl_enqueue_admin_scripts_and_styles( $hook ) {
    //var_dump($hook);
    if ( 'settings_page_cc-configurator-options' != $hook ) {
        return;
    }
    // Enqueue script and style for the color picker.
    wp_enqueue_script( 'wp-color-picker-script' );
    wp_enqueue_style( 'wp-color-picker' );

    // Register our stylesheet.
    wp_register_style( 'bccl_settings', plugins_url( 'css/bccl-settings.css', BCCL_PLUGIN_FILE ) );

    // Enqueue.
    wp_enqueue_style( 'bccl_settings' );
}
add_action( 'admin_enqueue_scripts', 'bccl_enqueue_admin_scripts_and_styles' );
// Note: `admin_print_styles` should not be used to enqueue styles or scripts on the admin pages. Use `admin_enqueue_scripts` instead. 


function bccl_options_page() {
    // Permission Check
    if ( ! current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if (isset($_POST['info_update'])) {

        bccl_save_settings( $_POST );

    } elseif ( isset( $_POST['info_reset'] ) ) {

        bccl_reset_settings();

    }

    // Try to get the options from the DB.
    $options = get_option('cc_settings');
    //var_dump($options);

    bccl_set_license_options($options);

}


function bccl_set_license_options($options) {
    /*
    CC License Options
    */
    global $wp_version;

    print('
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2>'.__('License Settings', 'creative-commons-configurator-1').'</h2>

        <p>'.__('Welcome to the administration panel of the Creative-Commons-Configurator plugin for WordPress.', 'creative-commons-configurator-1').'</p>
    </div>

    <div class="wrap">
        <p>'.__('In this screen you can select a default license for all your content and customize where and how the generated licensing information is embedded.', 'creative-commons-configurator-1').'</p>

        <form name="formbccl" method="post" action="' . admin_url( 'options-general.php?page=cc-configurator-options' ) . '">

        <h3>'.__('License your content', 'creative-commons-configurator-1').'</h3>
        <p>'.__('In this section you can select the default license that is used for all content for which custom licensing information has not been set.', 'creative-commons-configurator-1').'</p>

        <table class="form-table">
        <tbody>

            <tr valign="top">
            <th scope="row">'.__('Default License', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Default License', 'creative-commons-configurator-1').'</span></legend>
                ' . bccl_get_license_selection_form( $options["cc_default_license"], $options["cc_default_license"] ) . '
                <br /><br />
                <label for="cc_default_license">
                '.__('Select a default license for all your content. The license can be customized on a per post basis in the <em>License</em> box in the post editing screen. Further customization is possible programmatically through the available filters. For instance, it is possible to customize the license on a per taxonomy, author, post type basis, set a different default license for specific post types or limit the license choices in the <em>License</em> box for specific authors. The possibilities are unlimited. For more information about using the available filters please check the <a href="https://wordpress.org/plugins/creative-commons-configurator-1/">plugin description page</a> at WordPress.org.', 'creative-commons-configurator-1').'
                </label>
                <br />
            </fieldset>
            </td>
            </tr>

        </tbody>
        </table>

        <h3>'.__('Set where license information is added', 'creative-commons-configurator-1').'</h3>
        <p>'.__('In this section you can configure which parts of the web site licensing information is added to.', 'creative-commons-configurator-1').'</p>

        <table class="form-table">
        <tbody>

            <tr valign="top">
            <th scope="row">'.__('Syndicated Content', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Syndicated Content', 'creative-commons-configurator-1').'</span></legend>
                <label for="cc_feed">
                <input id="cc_feed" type="checkbox" value="1" name="cc_feed" '. (($options["cc_feed"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Include license information in the blog feeds. (<em>Recommended</em>)', 'creative-commons-configurator-1').'
                </label>
                <br />
            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('HTML Head', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Page Head HTML', 'creative-commons-configurator-1').'</span></legend>
                <label for="cc_head">
                <input id="cc_head" type="checkbox" value="1" name="cc_head" '. (($options["cc_head"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Include license information in the page\'s HTML head. This will not be visible to human visitors, but search engine bots will be able to read it. Note that the insertion of license information in the HTML head is done in relation to the content types (posts, pages or attachment pages) on which the license text block is displayed (see the <em>text block</em> settings below). (<em>Recommended</em>)', 'creative-commons-configurator-1').'
                </label>
                <br />
            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Text Block', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Text Block', 'creative-commons-configurator-1').'</span></legend>

                <p>'.__('By enabling the following options, a small block of text, which contains links to the author, the work and the used license, is appended to the published content.', 'creative-commons-configurator-1').'</p>
                <br />
                <label for="cc_body">
                <input id="cc_body" type="checkbox" value="1" name="cc_body" '. (($options["cc_body"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Posts: Add the text block with license information under the published posts and custom post types. (<em>Recommended</em>)', 'creative-commons-configurator-1').'
                </label>
                <br />

                <label for="cc_body_pages">
                <input id="cc_body_pages" type="checkbox" value="1" name="cc_body_pages" '. (($options["cc_body_pages"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Pages: Add the text block with license information under the published pages.', 'creative-commons-configurator-1').'
                </label>
                <br />

                <label for="cc_body_attachments">
                <input id="cc_body_attachments" type="checkbox" value="1" name="cc_body_attachments" '. (($options["cc_body_attachments"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Attachments: Add the text block with license information under the attached content in attachment pages.', 'creative-commons-configurator-1').' (<em>'.__('Recommended', 'creative-commons-configurator-1').'</em>)
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('License Image', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('License Image', 'creative-commons-configurator-1').'</span></legend>

                <label for="cc_body_img">
                <input id="cc_body_img" type="checkbox" value="1" name="cc_body_img" '. (($options["cc_body_img"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('By enabling this option, the license image is also included in the license text block.', 'creative-commons-configurator-1').'
                </label>
                <br />

                <label for="cc_compact_img">
                <input id="cc_compact_img" type="checkbox" value="1" name="cc_compact_img" '. (($options["cc_compact_img"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('If license images have been enabled above, use compact license images if available.', 'creative-commons-configurator-1').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Separate Media License', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Individual Media License', 'creative-commons-configurator-1').'</span></legend>

                <p>'.__('This option affects the way licensing information is added to media which appear inside your content. By default, the plugin assumes that all media within the content are released under the same license as the text. Many times this is not the case. By enabling the following option, the plugin is instructed to generate separate license metadata for each of those images, videos or audios files.', 'creative-commons-configurator-1').'</p>
                <br />

                <label for="cc_enable_individual_media_licensing">
                <input id="cc_enable_individual_media_licensing" type="checkbox" value="1" name="cc_enable_individual_media_licensing" '. (($options["cc_enable_individual_media_licensing"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Generate separate licensing information for media within the content.', 'creative-commons-configurator-1').' (<span style="color: red;">' . __('Experimental', 'creative-commons-configurator-1') . '</span>)
                </label>
                <br />

                <p>'.__('This option is mainly useful in case you republish content and media created by third parties. It can also be useful for the proper indexing of your media in case the indexing service requires separate licensing metadata for the media that exist in your content.', 'creative-commons-configurator-1').'</p>
                <br />

                <p>'.__('Please note that, after enabling this feature, some minor customization of your theme\'s CSS might be required, so that the visible licensing information is displayed correctly. Due to the huge variety of theme designs and concepts, it is technically impossible for the plugin to do this automatically.', 'creative-commons-configurator-1').'</p>
                <br />

                <label for="cc_featured_media_license_omit_human_visible_data">
                <input id="cc_featured_media_license_omit_human_visible_data" type="checkbox" value="1" name="cc_featured_media_license_omit_human_visible_data" '. (($options["cc_featured_media_license_omit_human_visible_data"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Omit human visible licensing information from the captions of <em>featured images</em>, but still add machine readable metadata.', 'creative-commons-configurator-1').' (<span style="color: red;">' . __('Experimental', 'creative-commons-configurator-1') . '</span>)
                </label>
                <br />

                <label for="cc_media_license_omit_human_visible_data">
                <input id="cc_media_license_omit_human_visible_data" type="checkbox" value="1" name="cc_media_license_omit_human_visible_data" '. (($options["cc_media_license_omit_human_visible_data"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Omit human visible licensing information from the captions of <em>attached media</em>, but still add machine readable metadata.', 'creative-commons-configurator-1').' (<span style="color: red;">' . __('Experimental', 'creative-commons-configurator-1') . '</span>)
                </label>
                <br />

            </fieldset>
            </td>
            </tr>


        </tbody>
        </table>

        <h3>'.__('Configure the license information generator', 'creative-commons-configurator-1').'</h3>
        <p>'.__('In this section you can configure how the licensing information is generated and its level of detail. These settings have an effect only if the text block containing licensing information has been enabled above for a specific content type.', 'creative-commons-configurator-1').'</p>

        <table class="form-table">
        <tbody>

            <tr valign="top">
            <th scope="row">'.__('Extended License Text Block', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Extended License Text Block', 'creative-commons-configurator-1').'</span></legend>

                <label for="cc_extended">
                <input id="cc_extended" type="checkbox" value="1" name="cc_extended" '. (($options["cc_extended"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Include extended information about the published work and its creator. By enabling this option, hyperlinks to the published content and its creator/publisher are also included into the license statement inside the block. This, by being an attribution example itself, will generally help others to attribute the work to you.', 'creative-commons-configurator-1').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Creator Attribution Name', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Creator Attribution Name', 'creative-commons-configurator-1').'</span></legend>

                <select name="cc_creator" id="cc_creator">');
                $creator_arr = bccl_get_creator_pool();
                foreach ($creator_arr as $internal => $creator) {
                    if ($options["cc_creator"] == $internal) {
                        $selected = ' selected="selected"';
                    } else {
                        $selected = '';
                    }
                    printf('<option value="%s"%s>%s</option>', $internal, $selected, $creator);
                }
                print('</select>
                <br /><br />
                <label for="cc_creator">
                '.__('If extended information about the published work has been enabled, then you can choose which name will indicate the creator of the work. By default, the blog name is used.', 'creative-commons-configurator-1').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Additional Permissions', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Additional Permissions', 'creative-commons-configurator-1').'</span></legend>

                <input name="cc_perm_url" type="text" id="cc_perm_url" class="code" value="' . esc_url($options["cc_perm_url"]) . '" size="100" maxlength="1024" />
                <br /><br />
                <label for="cc_perm_url">
                '.__('If you have added any extra permissions to your license, provide the URL to the web page that contains them. It is highly recommended to use absolute URLs.', 'creative-commons-configurator-1').'
                </label>
                <p><strong>'.__('Example', 'creative-commons-configurator-1').'</strong>: <code>http://www.example.org/ExtendedPermissions</code></p>
                <br />

                <input name="cc_perm_title" type="text" id="cc_perm_title" class="code" value="' . esc_attr($options["cc_perm_title"]) . '" size="100" maxlength="1024" />
                <br /><br />
                <label for="cc_perm_title">
                '.__('Enter the title of the page containing additional permissions. This can be whatever you like. It will be used as the anchor text of the hyperlink to the extra permissions page.', 'creative-commons-configurator-1').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

        </tbody>
        </table>

        <h3>'.__('Style of the license text block', 'creative-commons-configurator-1').'</h3>
        <p>'.__('In this section you can configure the looks of the text block that contains the licensing information. These settings have an effect only if the text block containing licensing information has been enabled above for a specific content type.', 'creative-commons-configurator-1').'</p>

        <table class="form-table">
        <tbody>
            
            <tr valign="top">
            <th scope="row">'.__('Colors of the text block', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Colors of the text block', 'creative-commons-configurator-1').'</span></legend>

                <input name="cc_color" type="text" id="cc_color" value="' . esc_attr($options["cc_color"]) . '" size="7" maxlength="7" class="cc-color-field" data-default-color="#000000" />
                <label for="cc_color">
                '.__('Foreground color for text including hyperlinks.', 'creative-commons-configurator-1').'
                </label>
                <br />
                <br />

                <input name="cc_bgcolor" type="text" id="cc_bgcolor" value="' . esc_attr($options["cc_bgcolor"]) . '" size="7" maxlength="7" class="cc-color-field" data-default-color="#eef6e6" />
                <label for="cc_bgcolor">
                '.__('Background color of the license block.', 'creative-commons-configurator-1').'
                </label>
                <br />
                <br />

                <input name="cc_brdr_color" type="text" id="cc_brdr_color" value="' . esc_attr($options["cc_brdr_color"]) . '" size="7" maxlength="7" class="cc-color-field" data-default-color="#cccccc" />
                <label for="cc_brdr_color">
                '.__('Color of the border of the license block.', 'creative-commons-configurator-1').'
                </label>
                <br />
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Disable internal style', 'creative-commons-configurator-1').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Disable internal style', 'creative-commons-configurator-1').'</span></legend>

                <label for="cc_no_style">
                <input id="cc_no_style" type="checkbox" value="1" name="cc_no_style" '. (($options["cc_no_style"]=="1") ? 'checked="checked"' : '') .'" />
                '.__('Disable the internal formatting of the license block. If the internal formatting is disabled, then the color selections above have no effect any more. You can still format the license block via your own CSS. The <code>cc-block</code> and <code>cc-button</code> classes have been reserved for formatting the license block and the license button respectively.', 'creative-commons-configurator-1').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

        </tbody>
        </table>

        <!-- Submit Buttons -->
        <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row">
                    <input id="submit" class="button-primary" type="submit" value="'.__('Save Changes', 'creative-commons-configurator-1').'" name="info_update" />
                </th>
                <!--
                <th scope="row">
                    <input id="reset" class="button-primary" type="submit" value="'.__('Reset to defaults', 'creative-commons-configurator-1').'" name="info_reset" />
                </th>
                <th></th><th></th><th></th><th></th>
                -->
            </tr>
        </tbody>
        </table>

        </form>

    </div>

    ');
}





/**
 * Adds Bccl_Widget widget.
 */
class Bccl_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'bccl_widget', // Base ID
			__('Creative Commons License', 'creative-commons-configurator-1'), // Name
			array( 'description' => __( 'Licensing information', 'creative-commons-configurator-1'), ) // Description
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

        $display_in_archives = false;
        if ( isset($instance['display_in_archives']) && $instance['display_in_archives'] ) {
            $display_in_archives = true;
        }
        $widget_output = bccl_get_widget_output( $display_in_archives=$instance['display_in_archives'] );
        if ( empty( $widget_output ) ) {
            return;
        }

        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];
        echo $widget_output;
        echo $args['after_widget'];

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'License', 'creative-commons-configurator-1');
		}

		if ( isset( $instance[ 'display_in_archives' ] ) ) {
			$display_in_archives = $instance[ 'display_in_archives' ];
		}
		else {
			$display_in_archives = false;
		}

		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $display_in_archives ); ?> id="<?php echo $this->get_field_id( 'display_in_archives' ); ?>" name="<?php echo $this->get_field_name( 'display_in_archives' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'display_in_archives' ); ?>"><?php _e( 'Display in front page and archives', 'creative-commons-configurator-1'); ?></label></p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['display_in_archives'] = isset( $new_instance['display_in_archives'] ) ? (bool) $new_instance['display_in_archives'] : false;

		return $instance;
	}

} // class Bccl_Widget

// register Bccl_Widget widget
function register_bccl_widget() {
    register_widget( 'Bccl_Widget' );
}
add_action( 'widgets_init', 'register_bccl_widget' );
























/**
 * Meta box in post/page editing panel.
 */

/* Define the custom box */
add_action( 'add_meta_boxes', 'bccl_add_license_box' );

/**
 * Adds a box to the main column of the editing panel of the supported post types.
 * See the bccl_get_post_types_for_metabox() docstring for more info on the supported types.
 */
function bccl_add_license_box() {

    // Get the Global License metabox permission (filtered)
    $metabox_permission = apply_filters( 'bccl_license_metabox_permission', 'edit_posts' );

    // Global License metabox permission check (can be user customized via filter).
    if ( ! current_user_can( $metabox_permission ) ) {
        return;
    }

    // Global License metabox permission check (internal - `edit_posts` is the minimum capability).
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $supported_types = bccl_get_post_types_for_metabox();

    // Add an CC-Configurator meta box to all supported types
    foreach ($supported_types as $supported_type) {
        add_meta_box( 
            'bccl-license-box',
            __( 'License', 'creative-commons-configurator-1'),
            'bccl_inner_license_box',
            $supported_type,
            'advanced',
            'high'
        );
    }

}


/**
 * Load CSS and JS for license box.
 * The editing pages are post.php and post-new.php
 */
function bccl_license_box_css_js () {
    // $supported_types = bccl_get_supported_post_types();
    // See: #900 for details

    // Using included Jquery UI
//    wp_enqueue_script('jquery');
//    wp_enqueue_script('jquery-ui-core');
//    wp_enqueue_script('jquery-ui-widget');
//    wp_enqueue_script('jquery-ui-tabs');

    //wp_register_style( 'bccl-jquery-ui-core', plugins_url('css/jquery.ui.core.css', BCCL_PLUGIN_FILE) );
    //wp_enqueue_style( 'bccl-jquery-ui-core' );
    //wp_register_style( 'bccl-jquery-ui-tabs', plugins_url('css/jquery.ui.tabs.css', BCCL_PLUGIN_FILE) );
    //wp_enqueue_style( 'bccl-jquery-ui-tabs' );
//    wp_register_style( 'bccl-metabox-tabs', plugins_url('css/bccl-metabox-tabs.css', BCCL_PLUGIN_FILE) );
//    wp_enqueue_style( 'bccl-metabox-tabs' );

}
// add_action('admin_print_styles-post.php', 'bccl_license_box_css_js');
// add_action('admin_print_styles-post-new.php', 'bccl_license_box_css_js');


/* For future reference - Add data to the HEAD area of post editing panel */
function bccl_metabox_script_caller() {
    print('
    <script>
        jQuery(document).ready(function($) {
        $("#bccl-metabox-tabs .hidden").removeClass(\'hidden\');
        $("#bccl-metabox-tabs").tabs();
        });
    </script>
    ');
}
// add_action('admin_head-post.php', 'bccl_metabox_script_caller');
// add_action('admin_head-post-new.php', 'bccl_metabox_script_caller');
// OR
// add_action('admin_footer-post.php', 'bccl_metabox_script_caller');
// add_action('admin_footer-post-new.php', 'bccl_metabox_script_caller');


/* Prints the box content */
function bccl_inner_license_box( $post ) {

    // Use a nonce field for verification
    wp_nonce_field( plugin_basename( BCCL_PLUGIN_FILE ), 'bccl_noncename' );

    // Get the post type. Will be used to customize the displayed notes.
    $post_type = get_post_type( $post->ID );

    // Try to get the options from the DB.
    $options = get_option('cc_settings');

    // Display the meta box HTML code.

    //
    //  Custom fields:
    //
    //  _bccl_license: contains a license slug (see bccl-licenses.php)
    //  _bccl_perm_url
    //  _bccl_perm_title
    //  _bccl_forced_creator_url
    //  _bccl_forced_creator_name
    //  _bccl_source_work_url
    //  _bccl_source_work_title
    //  _bccl_source_creator_url
    //  _bccl_source_creator_name
    
    // Retrieve the field data from the database.

    // Content license slug
    $bccl_license_value = bccl_get_content_license_slug( $post, $options );
    //var_dump( $bccl_license_value );
    // Custom extra perms page
    $bccl_perm_url_value = get_post_meta( $post->ID, '_bccl_perm_url', true );
    $bccl_perm_title_value = get_post_meta( $post->ID, '_bccl_perm_title', true );
    // Forced creator of current work
    $bccl_forced_creator_url_value = get_post_meta( $post->ID, '_bccl_forced_creator_url', true );
    $bccl_forced_creator_name_value = get_post_meta( $post->ID, '_bccl_forced_creator_name', true );
    // Source work
    $bccl_source_work_url_value = get_post_meta( $post->ID, '_bccl_source_work_url', true );
    $bccl_source_work_title_value = get_post_meta( $post->ID, '_bccl_source_work_title', true );
    // Source work creator
    $bccl_source_creator_url_value = get_post_meta( $post->ID, '_bccl_source_creator_url', true );
    $bccl_source_creator_name_value = get_post_meta( $post->ID, '_bccl_source_creator_name', true );

    // Construct the HTML code of the metabox.

    print('
        <h3>'.__('License this work', 'creative-commons-configurator-1').'</h3>
        <div class="inside">
            <p>'.__('The default license from the general plugin settings is used in order to license this work. Please use the following selector to choose a custom license.', 'creative-commons-configurator-1').'</p>
            ' . bccl_get_license_selection_form( $options["cc_default_license"], $bccl_license_value ,$element_name="bccl_license", $mark_default=true ) . '
        </div>

        <!-- Extra permissions -->

        <h3>'.__('Additional terms and conditions', 'creative-commons-configurator-1').'</h3>
        <div class="inside">
            '.__('If you make your work available under terms beyond the scope of the used license, it is possible to add a link to the page containing these additional terms and conditions. The following fields override the respective fields of the general plugin settings.', 'creative-commons-configurator-1').'

            <p class="meta-options">
                <input name="bccl_perm_url" type="text" id="bccl_perm_url" class="regular-text code" value="' . esc_url( stripslashes( $bccl_perm_url_value ) ) . '" size="100" maxlength="1024" />
                <br />
                <label for="bccl_perm_url">'.__('URL of the page containing extra permissions.', 'creative-commons-configurator-1').'</label>
            </p>
            <p class="meta-options">
                <input name="bccl_perm_title" type="text" id="bccl_perm_title" class="regular-text code" value="' . esc_attr( stripslashes( $bccl_perm_title_value ) ) . '" size="100" maxlength="1024" />
                <br />
                <label for="bccl_perm_title">'.__('Title of the page containing extra permissions.', 'creative-commons-configurator-1').'</label>
            </p>
        </div>

        <!-- Forced Creator -->

        <h3>'.__('Force creator attribution', 'creative-commons-configurator-1').'</h3>
        <div class="inside">
            '.__('By default, the plugin attributes this work to the current editor. This section lets you attribute it to another creator. This is useful in case you republish Creative Commons licensed works made by other creators. For instance, if you upload another creator\'s image to your blog\'s media library for reuse, it is always a good idea to also fill in these settings so that the plugin generates proper attribution on the attachment page. Moreover, if you include such works made by other creators in your posts, you should also check the <em>Separate Media License</em> option in the <a href="options-general.php?page=cc-configurator-options">License settings page</a> in order to instruct the plugin to generate separate licensing metadata for media files included in your content.', 'creative-commons-configurator-1').'

            <p class="meta-options">
                <input name="bccl_forced_creator_url" type="text" id="bccl_forced_creator_url" class="regular-text code" value="' . esc_url( stripslashes( $bccl_forced_creator_url_value ) ) . '" size="100" maxlength="1024" />
                <br />
                <label for="bccl_forced_creator_url">'.__('URL of the creator\'s web site.', 'creative-commons-configurator-1').'</label>
            </p>
            <p class="meta-options">
                <input name="bccl_forced_creator_name" type="text" id="bccl_forced_creator_name" class="regular-text code" value="' . esc_attr( stripslashes( $bccl_forced_creator_name_value ) ) . '" size="100" maxlength="1024" />
                <br />
                <label for="bccl_forced_creator_name">'.__('Name of the creator.', 'creative-commons-configurator-1').'</label>
            </p>
        </div>

        <!-- Source work -->

        <!--
        <h3>'.__('Source work attributes', 'creative-commons-configurator-1').'</h3>
        <div class="inside">
            '.__('If this content is a derivative work, here you can enter some attributes of the source work.', 'creative-commons-configurator-1').'

            <h4>'.__('URL and title of the source work', 'creative-commons-configurator-1').'</h4>
            <p class="meta-options">
                <input name="bccl_source_work_url" type="text" id="bccl_source_work_url" class="regular-text code" value="' . esc_url_raw( stripslashes( $bccl_source_work_url_value ) ) . '" size="100" maxlength="1024" />
                <label for="bccl_source_work_url">'.__('URL of the source work.', 'creative-commons-configurator-1').'</label>
            </p>
            <p class="meta-options">
                <input name="bccl_source_work_title" type="text" id="bccl_source_work_title" class="regular-text code" value="' . esc_attr( stripslashes( $bccl_source_work_title_value ) ) . '" size="100" maxlength="1024" />
                <label for="bccl_source_work_title">'.__('Title of the source work.', 'creative-commons-configurator-1').'</label>
            </p>

            <h4>'.__('URL and title of the creator of the source work', 'creative-commons-configurator-1').'</h4>
            <p class="meta-options">
                <input name="bccl_source_creator_url" type="text" id="bccl_source_creator_url" class="regular-text code" value="' . esc_url_raw( stripslashes( $bccl_source_creator_url_value ) ) . '" size="100" maxlength="1024" />
                <label for="bccl_source_creator_url">'.__('URL of the creator of the source work.', 'creative-commons-configurator-1').'</label>
            </p>
            <p class="meta-options">
                <input name="bccl_source_creator_name" type="text" id="bccl_source_creator_name" class="regular-text code" value="' . esc_attr( stripslashes( $bccl_source_creator_name_value ) ) . '" size="100" maxlength="1024" />
                <label for="bccl_source_creator_name">'.__('Name of the creator of the source work.', 'creative-commons-configurator-1').'</label>
            </p>
        </div>
        -->
    ');

}




/* Manage the entered data */
add_action( 'save_post', 'bccl_save_postdata', 10, 2 );
// Action for attachments
// The 'save_post' action does not seem to work for attachments. On the other
// hand there is no 'save_attachment' action hook. So we here use the 'edit_attachment'.
add_action( 'edit_attachment', 'bccl_save_postdata', 10, 1 );

/* When the post is saved, saves our custom description and keywords */
function bccl_save_postdata( $post_id, $post=null ) {

    if ( is_null($post) ) {
        // Happens when attachments are saved.
        $post = get_post($post_id);
    }

    // Verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;

    /* Verify the nonce before proceeding. */
    // Verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !isset($_POST['bccl_noncename']) || !wp_verify_nonce( $_POST['bccl_noncename'], plugin_basename( BCCL_PLUGIN_FILE ) ) )
        return;

    // Get the Global License metabox permission (filtered)
    $metabox_permission = apply_filters( 'bccl_license_metabox_permission', 'edit_posts' );

    // Global License metabox permission check (can be user customized via filter).
    if ( ! current_user_can( $metabox_permission ) ) {
        return;
    }

    /* Get the post type object. */
	$post_type_obj = get_post_type_object( $post->post_type );

    // Try to get the options from the DB.
    $options = get_option('cc_settings');

    /* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type_obj->cap->edit_post, $post_id ) )
		return;

    // OK, we're authenticated: we need to find and save the data

    //
    // Get value for custom fields, Sanitize user input.

    // Custom license
    $bccl_license_value = $options['cc_default_license'];
    if ( isset( $_POST['bccl_license'] ) ) {
        $bccl_license_value = sanitize_text_field( stripslashes( $_POST['bccl_license'] ) );
    }
    //var_dump( $bccl_license_value );

    // Perm URL
    $bccl_perm_url_value = '';
    if ( isset( $_POST['bccl_perm_url'] ) ) {
        $bccl_perm_url_value = esc_url( stripslashes( $_POST['bccl_perm_url'] ) );
    }

    // Perm Title
    $bccl_perm_title_value = '';
    if ( isset( $_POST['bccl_perm_title'] ) ) {
        $bccl_perm_title_value = sanitize_text_field( stripslashes( $_POST['bccl_perm_title'] ) );
    }

    // Forced creator URL
    $bccl_forced_creator_url_value = '';
    if ( isset( $_POST['bccl_forced_creator_url'] ) ) {
        $bccl_forced_creator_url_value = esc_url( stripslashes( $_POST['bccl_forced_creator_url'] ) );
    }

    // Forced creator name
    $bccl_forced_creator_name_value = '';
    if ( isset( $_POST['bccl_forced_creator_name'] ) ) {
        $bccl_forced_creator_name_value = sanitize_text_field( stripslashes( $_POST['bccl_forced_creator_name'] ) );
    }

    // Source work URL
    $bccl_source_work_url_value = '';
    if ( isset( $_POST['bccl_source_work_url'] ) ) {
        $bccl_source_work_url_value = esc_url_raw( stripslashes( $_POST['bccl_source_work_url'] ) );
    }

    // Source work title
    $bccl_source_work_title_value = '';
    if ( isset( $_POST['bccl_source_work_title'] ) ) {
        $bccl_source_work_title_value = sanitize_text_field( stripslashes( $_POST['bccl_source_work_title'] ) );
    }

    // Source work creator URL
    $bccl_source_creator_url_value = '';
    if ( isset( $_POST['bccl_source_creator_url'] ) ) {
        $bccl_source_creator_url_value = esc_url_raw( stripslashes( $_POST['bccl_source_creator_url'] ) );
    }

    // Source work creator name
    $bccl_source_creator_name_value = '';
    if ( isset( $_POST['bccl_source_creator_name'] ) ) {
        $bccl_source_creator_name_value = sanitize_text_field( stripslashes( $_POST['bccl_source_creator_name'] ) );
    }

    // Custom field names
    $bccl_license_field_name = '_bccl_license';
    $bccl_perm_url_field_name = '_bccl_perm_url';
    $bccl_perm_title_field_name = '_bccl_perm_title';
    $bccl_forced_creator_url_field_name = '_bccl_forced_creator_url';
    $bccl_forced_creator_name_field_name = '_bccl_forced_creator_name';
    $bccl_source_work_url_field_name = '_bccl_source_work_url';
    $bccl_source_work_title_field_name = '_bccl_source_work_title';
    $bccl_source_creator_url_field_name = '_bccl_source_creator_url';
    $bccl_source_creator_name_field_name = '_bccl_source_creator_name';

    // The ``_bccl_license`` custom field always has a value, but we only save the field,
    // if the slug is other than the slug of the default license in the plugin settings (Settings->License).
    // If the slug is the default slug, then we just delete the custom field associated with this post.
    if ( $bccl_license_value == $options['cc_default_license'] ) {
        delete_post_meta( $post_id, $bccl_license_field_name );
    } else {
        update_post_meta( $post_id, $bccl_license_field_name, $bccl_license_value);
    }

    // Perm URL
    if ( empty($bccl_perm_url_value) ) {
        delete_post_meta($post_id, $bccl_perm_url_field_name);
    } else {
        update_post_meta($post_id, $bccl_perm_url_field_name, $bccl_perm_url_value);
    }

    // Perm Title
    if ( empty($bccl_perm_title_value) ) {
        delete_post_meta($post_id, $bccl_perm_title_field_name);
    } else {
        update_post_meta($post_id, $bccl_perm_title_field_name, $bccl_perm_title_value);
    }

    // Forced creator URL
    if ( empty($bccl_forced_creator_url_value) ) {
        delete_post_meta($post_id, $bccl_forced_creator_url_field_name);
    } else {
        update_post_meta($post_id, $bccl_forced_creator_url_field_name, $bccl_forced_creator_url_value);
    }

    // Forced creator name
    if ( empty($bccl_forced_creator_name_value) ) {
        delete_post_meta($post_id, $bccl_forced_creator_name_field_name);
    } else {
        update_post_meta($post_id, $bccl_forced_creator_name_field_name, $bccl_forced_creator_name_value);
    }

    // Source work URL
    if ( empty($bccl_source_work_url_value) ) {
        delete_post_meta($post_id, $bccl_source_work_url_field_name);
    } else {
        update_post_meta($post_id, $bccl_source_work_url_field_name, $bccl_source_work_url_value);
    }

    // Source work title
    if ( empty($bccl_source_work_title_value) ) {
        delete_post_meta($post_id, $bccl_source_work_title_field_name);
    } else {
        update_post_meta($post_id, $bccl_source_work_title_field_name, $bccl_source_work_title_value);
    }

    // Source work creator URL
    if ( empty($bccl_source_creator_url_value) ) {
        delete_post_meta($post_id, $bccl_source_creator_url_field_name);
    } else {
        update_post_meta($post_id, $bccl_source_creator_url_field_name, $bccl_source_creator_url_value);
    }

    // Source work creator name
    if ( empty($bccl_source_creator_name_value) ) {
        delete_post_meta($post_id, $bccl_source_creator_name_field_name);
    } else {
        update_post_meta($post_id, $bccl_source_creator_name_field_name, $bccl_source_creator_name_value);
    }
}


