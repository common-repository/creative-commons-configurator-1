<?php
/*
Plugin Name: Creative Commons Configurator
Plugin URI: http://www.g-loaded.eu/2006/01/14/creative-commons-configurator-wordpress-plugin/
Description: Helps you publish your content under the terms of Creative Commons and other licenses.
Version: 1.8.27
Author: George Notaras
Author URI: http://www.g-loaded.eu/
License: Apache License v2
Text Domain: creative-commons-configurator-1
Domain Path: /languages/
*/

/**
 *  Copyright 2008-2015 George Notaras <gnot@g-loaded.eu>, CodeTRAX.org
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}


// Store plugin directory
define( 'BCCL_PLUGIN_FILE', __FILE__ );
// Store plugin directory
// NOTE: TODO: Consider using __DIR__ (requires PHP >=5.3) instead of dirname.
// See: http://stackoverflow.com/questions/2220443/whats-better-of-requiredirname-file-myparent-php-than-just-require#comment18170996_12129877
//define( 'BCCL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BCCL_PLUGIN_DIR', dirname(BCCL_PLUGIN_FILE) . '/' );

// Import modules
require( BCCL_PLUGIN_DIR . 'bccl-settings.php' );
require( BCCL_PLUGIN_DIR . 'bccl-admin-panel.php' );
require( BCCL_PLUGIN_DIR . 'bccl-template-tags.php' );
require( BCCL_PLUGIN_DIR . 'bccl-utils.php' );
require( BCCL_PLUGIN_DIR . 'bccl-licenses.php' );
require( BCCL_PLUGIN_DIR . 'bccl-generators.php' );
// require( BCCL_PLUGIN_DIR . 'bccl-deprecated.php' );


/*
 * Translation Domain
 *
 * Translation files are searched in: wp-content/plugins
 */
load_plugin_textdomain('creative-commons-configurator-1', false, dirname( plugin_basename( BCCL_PLUGIN_FILE ) ) . '/languages/');
// For language packs only:
//load_plugin_textdomain('creative-commons-configurator-1');

/**
 * Settings Link in the ``Installed Plugins`` page
 */
function bccl_plugin_actions( $links, $file ) {
    if( $file == plugin_basename( BCCL_PLUGIN_FILE ) && function_exists( "admin_url" ) ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=cc-configurator-options' ) . '">' . __('Settings') . '</a>';
        // Add the settings link before other links
        array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links', 'bccl_plugin_actions', 10, 2 );
// ALT: add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( BCCL_PLUGIN_FILE ) . 'plugin.php'), 'admin_plugin_settings_link' );


/**
 *  Return license text for widget
 */
function bccl_get_widget_output( $display_in_archives=false ) {

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return;
    }

    // Archives and homepage/index
    if ( is_archive() || is_front_page() || is_home() ) {
        if ( ! $display_in_archives ) {
            return;
        }
        $post = null;
        // We always use the default license
        $license_slug = $options['cc_default_license'];
        if ( empty($license_slug) ) {
            return;
        }
        $license_data = bccl_get_license_data( $license_slug );
        if ( empty($license_data) ) {
            return;
        }
        // Generate the widget HTML code
        $widget_html = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $post, $options, $minimal=true );
        // Allow filtering of the widget HTML
        $widget_html = apply_filters( 'bccl_widget_html_archives', $widget_html );
        return $widget_html;
    }

    // Licensing is added on posts, pages, attachments and custom post types.
    if ( ! is_singular() || is_front_page() ) {
        return;
    }
    $post = get_queried_object();

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return;
    }

    $license_slug = bccl_get_content_license_slug( $post, $options );
    if ( empty($license_slug) ) {
        return;
    }

    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return;
    }

    // Check whether we should display the widget content or not.
    // In general, if the license block is set to be displayed under the content,
    // then the widget is suppressed.

    if ( is_attachment() ) {
        if ( $options["cc_body_attachments"] == "1" ) {
            return;
        }
    } elseif ( is_page() ) {
        if ( $options["cc_body_pages"] == "1" ) {
            return;
        }
    //} elseif ( is_single() ) {
    } else {    // posts and custom post types.
        if ( $options["cc_body"] == "1" ) {
            return;
        }
    }

    // Append the license block to the content
    $widget_html = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $post, $options, $minimal=true );

    // Allow filtering of the widget HTML
    $widget_html = apply_filters( 'bccl_widget_html', $widget_html );

    return $widget_html;
}



/*
Adds a link element with "license" relation in the web page HEAD area.

Also, adds style for the license block, only if the user has:
 - enabled the display of such a block
 - not disabled internal license block styling
 - if it is single-post view
*/
function bccl_add_to_head_section() {

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return;
    }

    $output = array();

    if ( is_archive() || is_front_page() ) {

        // We use a null post here.
        if ( apply_filters('bccl_exclude_license', false, null) ) {
            return;
        }

    } elseif ( is_singular() ) {

        // Licensing is added on posts, pages, attachments and custom post types.
        $post = get_queried_object();

        if ( apply_filters('bccl_exclude_license', false, $post) ) {
            return;
        }

        $license_slug = bccl_get_content_license_slug( $post, $options );
        if ( empty($license_slug) ) {
            return;
        }

        $license_data = bccl_get_license_data( $license_slug );
        if ( empty($license_data) ) {
            return;
        }

        // If the addition of data in the head section has been enabled
        if ( $options["cc_head"] == "1" ) {

            if ( substr($license_slug, 0, 2) == 'cc' ) {
                // Currently only licenses by the Creative Commons Corporation are
                // supported for inclusion in the HEAD area (CC & CC0).
                // Adds a link element with "license" relation in the web page HEAD area.
                $license_url = apply_filters( 'bccl_license_url', $license_data['url'] );
                if ( ! empty($license_url) ) {
                    $output[] = '<link rel="license" type="text/html" href="' . esc_url($license_url) . '" />';
                }
            }

        }

    }

    // Internal style. If the user has not deactivated our internal style, print it too
    if ( $options["cc_no_style"] != "1" ) {
        // Adds style for the license block
        $color = $options["cc_color"];
        $bgcolor = $options["cc_bgcolor"];
        $brdrcolor = $options["cc_brdr_color"];
        $style_output = array();
        // Check for AMP page
        if ( function_exists('is_amp_endpoint') && is_amp_endpoint() ) {
            $style_output[] = '<style amp-custom>';
        } else {
            $style_output[] = '<style type="text/css">';
        }
        //$style_output[] = '<!--';
        $style_output[] = "p.cc-block { clear: both; width: 90%; margin: 8px auto; padding: 4px; text-align: center; border: 1px solid $brdrcolor; color: $color; background-color: $bgcolor; }";
        //$style_output[] = "p.cc-block a:link, p.cc-block a:visited, p.cc-block a:hover, p.cc-block a:active { text-decoration: none; color: $color; border: none; }";
        $style_output[] = "p.cc-block a:link, p.cc-block a:visited, p.cc-block a:hover, p.cc-block a:active { text-decoration: underline; color: $color; border: none;}";
//        $style_output[] = ".cc-block img, .cc-block a img { display: inline; padding: 8px; text-decoration: none; }";
        $style_output[] = ".cc-button { display: block; margin-left: auto; margin-right: auto; margin-top: 6px; margin-bottom: 6px; border-width: 0; }";
//        $style_output[] = ".cc-attached-media-license, .cc-attached-featured-media-license { text-align: center; }";
//        $style_output[] = ".cc-button { border-width: 0; }";

        $style_output[] = ".wp-caption { border: 0; }";
        if ( $options === false || $options['cc_enable_individual_media_licensing'] == '1' ) {
            // Fixes the distance between the bottom of the player and the added caption.
            $style_output[] = ".wp-video, .wp-audio-shortcode { margin-bottom: 0.25em; }";
        }

        $style_output[] = ".widget_bccl_widget { text-align: center; }";
        $extra_styles = apply_filters( 'bccl_extra_style', '' );
        if ( ! empty($extra_styles) ) {
            $style_output[] = $extra_styles;
        }
        //$style_output[] = '-->';
        $style_output[] = '</style>';
        $style_output[] = ''; // Blank line
        $style = implode(PHP_EOL, $style_output);
        $style = apply_filters( 'bccl_style', $style );
        $output[] = $style;
    }

    $html = implode(PHP_EOL, $output);
    if ( ! empty($html) ) {
        // Print our comment
        echo PHP_EOL . "<!-- BEGIN License added by Creative-Commons-Configurator plugin for WordPress -->" . PHP_EOL;

        echo $html;

        // Closing comment
        echo PHP_EOL . "<!-- END License added by Creative-Commons-Configurator plugin for WordPress -->" . PHP_EOL . PHP_EOL;
    }
}
add_action('wp_head', 'bccl_add_to_head_section');
// AMP pages
add_action('amp_post_template_head', 'bccl_add_to_head_section');


/**
 * Adds the CC RSS module namespace declaration.
 */
function bccl_add_cc_ns_feed() {

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return;
    }

    $license_slug = $options['cc_default_license']; // We use the general default license
    if ( empty($license_slug) ) {
        return;
    }

    if ( $options["cc_feed"] == "1" ) {
        if ( substr($license_slug, 0, 2) == 'cc' ) {
            // Currently only licenses by the Creative Commons Corporation are
            // supported for inclusion in the feeds (CC & CC0).
            echo "xmlns:creativeCommons=\"http://backend.userland.com/creativeCommonsRssModule\"" . PHP_EOL;
        }
    }
}


/**
 * Adds the CC URL to the feed.
 */
function bccl_add_cc_element_feed() {

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return;
    }

    $license_slug = $options['cc_default_license']; // We use the general default license
    if ( empty($license_slug) ) {
        return;
    }

    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return;
    }

    if ( $options["cc_feed"] == "1" ) {
        if ( substr($license_slug, 0, 2) == 'cc' ) {
            // Currently only licenses by the Creative Commons Corporation are
            // supported for inclusion in the feeds (CC & CC0).
            $license_url = apply_filters( 'bccl_license_url', $license_data['url'] );
            if ( ! empty($license_url) ) {
                echo "\t<creativeCommons:license>" . esc_url($license_url) . "</creativeCommons:license>" . PHP_EOL;
            }
        }
    }
}


/**
 * Adds the CC URL to the feed items.
 */
function bccl_add_cc_element_feed_item() {

    // No need to check is_singular() here. We always have a post.
    //$post = get_queried_object();
    // Do not use get_queried_object() as it does not retrieve the item.
    global $post;

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return;
    }

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return;
    }

    $license_slug = bccl_get_content_license_slug( $post, $options );
    if ( empty($license_slug) ) {
        return;
    }

    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return;
    }

    // If the addition of data in the feeds has been enabled
    if ( $options["cc_feed"] == "1" ) {

        if ( substr($license_slug, 0, 2) == 'cc' ) {
            // Currently only licenses by the Creative Commons Corporation are
            // supported for inclusion in the HEAD area (CC & CC0).
            $license_url = apply_filters( 'bccl_license_url', $license_data['url'] );
            if ( ! empty($license_url) ) {
                echo "\t\t<creativeCommons:license>" . esc_url($license_url) . "</creativeCommons:license>" . PHP_EOL;
            }
        }
    }
}


/*
 * Adds the license block under the published content.
 *
 * The check if the user has chosen to display a block under the published
 * content is performed in bccl_get_license_block(), in order not to retrieve
 * the saved settings two timesor pass them between functions.
 */
function bccl_append_to_post_body($PostBody) {

    // Licensing is added on posts, pages, attachments and custom post types.
    if ( ! is_singular() || is_front_page() ) {
        return $PostBody;
    }
    $post = get_queried_object();

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return $PostBody;
    }

    $options = get_option("cc_settings");
    if ( $options === FALSE ) {
        return $PostBody;
    }

    $license_slug = bccl_get_content_license_slug( $post, $options );
    if ( empty($license_slug) ) {
        return $PostBody;
    }

    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return $PostBody;
    }

    // Option to suppress the license if it is the same with the default.
    if ( apply_filters( 'bccl_suppress_license_if_default', false ) ) {
        if ( $license_slug == $options['cc_default_license'] ) {
            return $PostBody;
        }
    }

    // Append according to options
    if ( is_attachment() ) {
        if ( $options["cc_body_attachments"] != "1" ) {
            return $PostBody;
        }
    } elseif ( is_page() ) {
        if ( $options["cc_body_pages"] != "1" ) {
            return $PostBody;
        }
    //} elseif ( is_single() ) {
    } else {    // posts and custom post types
        if ( $options["cc_body"] != "1" ) {
            return $PostBody;
        }
    }

    // Append the license block to the content
    $cc_block = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $post, $options );
    // HTML code to prepend/append to CC block
    $cc_block = apply_filters( 'bccl_license_block_html', $cc_block );

    //$cc_block = bccl_get_license_block("", "", "default", "default");
    if ( ! empty($cc_block) ) {
        if ( apply_filters( 'bccl_license_block_before_content', false ) ) {
            $PostBody = bccl_add_placeholders($cc_block) . $PostBody;
        } else {
            $PostBody .= bccl_add_placeholders($cc_block);
        }
    }

    return $PostBody;
}
add_filter('the_content', 'bccl_append_to_post_body', apply_filters( 'bccl_append_to_post_body_filter_priority', 250 ) );

// Feed actions
add_action('rdf_ns', 'bccl_add_cc_ns_feed');
add_action('rdf_header', 'bccl_add_cc_element_feed');
add_action('rdf_item', 'bccl_add_cc_element_feed_item');

add_action('rss2_ns', 'bccl_add_cc_ns_feed');
add_action('rss2_head', 'bccl_add_cc_element_feed');
add_action('rss2_item', 'bccl_add_cc_element_feed_item');

add_action('atom_ns', 'bccl_add_cc_ns_feed');
add_action('atom_head', 'bccl_add_cc_element_feed');
add_action('atom_entry', 'bccl_add_cc_element_feed_item');



//
// Add separate licensing metadata on media.
//

//
// License metadata for images is added differently according to the following criteria
// 1) featured images
// 2) images with captions, the final HTML code of which is always generated using the caption shortcode
// 3) images without captions. The only way to isolate these is to filter the post content (fortunately this was easy for images without caption).
//


//
// Featured image
//
function bccl_separate_licensing_on_featured_image( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

    // The license data of the featured image is only added on content pages.
    if ( ! is_singular() ) {
        return $html;
    }

    if ( empty($html) ) {
        return $html;
    }

    // Plugin options
    $options = get_option('cc_settings');
    if ( $options === false || $options['cc_enable_individual_media_licensing'] == '0' ) {
        return $html;
    }

    // Size can be a string or array containing width and hight items.
    // We accept only a size as string.
    if ( is_array($size) ) {
        return $html;
    }

    // Individual image licensing is added on posts, pages, attachments and custom post types.
    if ( ! is_singular() || is_front_page() ) {
        return $html;
    }

    $post = get_post($post_id);

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return $html;
    }

    // Attachment ID
    // $post_thumbnail_id

    // Current image size's URL
    $main_size_meta = wp_get_attachment_image_src( $post_thumbnail_id, $size );
    $current_attachment_url = $main_size_meta[0];

    // Attachment object
    $attachment = get_post( $post_thumbnail_id );
    $mime_type = get_post_mime_type( $attachment->ID );
    //$attachment_type = strstr( $mime_type, '/', true );
    // See why we do not use strstr(): http://www.codetrax.org/issues/1091
    $attachment_type = preg_replace( '#\/[^\/]*$#', '', $mime_type );
    $attachment_dcmi_type = 'StillImage';

    // Image Width
    $width = null;
    if ( ! preg_match( '/width=["\']([0-9]+)/', $html, $width_match ) ) {
        return $html;
    }
    $width = $width_match[1];

    // License data
    $license_slug = bccl_get_content_license_slug( $attachment, $options );
    if ( empty($license_slug) ) {
        return $html;
    }
    // In case of ARR or manual license, just return the content without adding
    // any image licensing.
    if ( in_array($license_slug, array('manual', 'arr')) ) {
        return $html;
    }
    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return $html;
    }
    // License URL.
    // We need it for the additional link elements we add in case visible
    // licensing metadata is not enabled and also to abort in case it is empty.
    $license_url = $license_data['url'];
    if ( empty($license_url) ) {
        return $html;
    }

    // Notes:
    // 1. Sets the 'width' CSS property on outer div's 'style' attribute
    // 2. no alignment class on outer div
    // 3. Sets the 'wp-caption' class to outer div

    // Enclosure style
    $enclosure_style = '';
    if ( ! is_null($width) ) {
        $enclosure_style = 'width: ' . esc_attr($width) . 'px;';
    }
    // Default classes
    $default_classes = 'cc-block-media cc-block-featured-image wp-caption';

    // Construct extra template vars
    $extra_template_vars = array(
        '#style#'          => $enclosure_style,
        '#classes#'        => $default_classes,
        '#attachment_url#' => $current_attachment_url,
    );

    // License metadata
    $license_block_parts = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $attachment, $options, $minimal=false, $is_content_media=true, $extra_template_vars=$extra_template_vars );

    // Process Inner

    $license_metadata = $license_block_parts['inner'];
    // Allow filtering
    $license_metadata = apply_filters( 'bccl_license_block_media_featured_' . esc_attr($attachment_type) . '_html', $license_metadata );

    // Add class
    $license_metadata = '<span class="cc-attached-featured-media-license cc-attached-featured-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</span>';
    // License metadata as caption itself
    $license_metadata_as_caption = '<p class="wp-caption-text caption entry-caption cc-attached-featured-media-license cc-attached-featured-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</p>';

    // Add visible licensing information

    if ( $options['cc_featured_media_license_omit_human_visible_data'] == '0' ) {

        // Append hyperlink with rel="license".
        //$license_metadata_added = false;
        if ( strpos($html, '</figcaption>') !== false ) {
            // If the figure html element contains a figcaption element,
            // then we add the HTML hyperlink at the end of it.
            $html = str_replace('</figcaption>', ' ' . $license_metadata . '</figcaption>', $html);
            //$license_metadata_added = true;
        } elseif ( strpos($html, '</p>') !== false ) {
            // If the caption is placed in a 'p' element
            $html = str_replace('</p>', ' ' . $license_metadata . '</p>', $html);
            //$license_metadata_added = true;
        } else {
            // Just append the license metadata
            $html .= $license_metadata_as_caption;
        }

    }

    $inner_html = $html;

    // Process outer

    $outer_html = $license_block_parts['outer'];

    // Finally process the #inner_html# template variable.
    return str_replace('#inner_html#', $inner_html, $outer_html);

}
add_filter('post_thumbnail_html', 'bccl_separate_licensing_on_featured_image', 10, 5);


//
// Images using the 'caption' shortcode
//
function bccl_separate_licensing_on_images( $dummy, $attr, $content ) {

    // Plugin options
    $options = get_option('cc_settings');
    if ( $options === false || $options['cc_enable_individual_media_licensing'] == '0' ) {
        return $dummy;
    }

    // Individual image licensing is added on posts, pages, attachments and custom post types.
    if ( ! is_singular() || is_front_page() ) {
        return $dummy;
    }

    $post = get_queried_object();

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return $dummy;
    }

    // Attachment ID
    // The shortcode about images contains the image attachment ID as 'attachment_N'
    // For videos and audio the $attr array does not contain the attachment ID.
    // In those cases we retrieve the ID by querying the database using bccl_attachment_id_from_url().
    if ( ! isset($attr['id']) ) {
        return $dummy;
    }
    // Find the attachement id from $attr['id'] (format: attachment_ID)
    $id_str = str_replace('attachment_', '', $attr['id']);
    if ( ! is_numeric($id_str) ) {
        return $dummy;
    }
    $id = absint($id_str);

    // Current image size's URL
    $current_attachment_url = '';
    // Image URL.
    if ( preg_match('#<img.* src="([^"]+)"#', $content, $matches) ) {
        // First try regex on the $content
        $current_attachment_url = $matches[1];
    } else {
        // Otherwise, use the URL of the full image size.
        $main_size_meta = wp_get_attachment_image_src( $id, 'full' );
        $current_attachment_url = $main_size_meta[0];
    }
    if ( empty($current_attachment_url) ) {
        return $dummy;
    }

    // Attachment object
    $attachment = get_post( $id );
    $mime_type = get_post_mime_type( $attachment->ID );
    //$attachment_type = strstr( $mime_type, '/', true );
    // See why we do not use strstr(): http://www.codetrax.org/issues/1091
    $attachment_type = preg_replace( '#\/[^\/]*$#', '', $mime_type );
    $attachment_dcmi_type = 'StillImage';

    // License data
    $license_slug = bccl_get_content_license_slug( $attachment, $options );
    if ( empty($license_slug) ) {
        return $dummy;
    }
    // In case of ARR or manual license, just return the content without adding
    // any image licensing.
    if ( in_array($license_slug, array('manual', 'arr')) ) {
        return $dummy;
    }
    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return $dummy;
    }
    // License URL.
    // We need it for the additional link elements we add in case visible
    // licensing metadata is not enabled and also to abort in case it is empty.
    $license_url = $license_data['url'];
    if ( empty($license_url) ) {
        return $dummy;
    }

    // HTML
    // Currently (WP 4.2.2) it's not possible to filter the final HTML output of img_caption_shortcode().
    // So, here we first remove our current function for the filter hook, then run
    // img_caption_shortcode() to get the default final HTML output and then process
    // it and add our licensing metadata.
    // See about this method: https://core.trac.wordpress.org/ticket/29832#comment:1

    remove_filter('img_caption_shortcode', __FUNCTION__, 10, 3);

    $html = img_caption_shortcode( $attr, $content );
    if ( empty($html) ) {
        return $dummy;
    }

    add_filter('img_caption_shortcode', __FUNCTION__, 10, 3);

    // NOTE: no width on outer div since the 'caption' shortcode adds it on its own outer div
    // NOTE: no alignment class on the outer div since the 'caption' shortcode takes care of this.
    // NOTE: no wp-caption class here, since it is added by WP since the image has a caption.

    // Notes:
    // 1. no 'width' on outer div's 'style' attribute
    // 2. no alignment class on outer div
    // 3. no 'wp-caption' class to outer div

    // Enclosure style
    $enclosure_style = '';
    // Default classes
    $default_classes = 'cc-block-media cc-block-image';
    // Enclose all HTML in our div element.


    // Construct extra template vars
    $extra_template_vars = array(
        '#style#'          => $enclosure_style,
        '#classes#'        => $default_classes,
        '#attachment_url#' => $current_attachment_url,
    );

    // License metadata
    $license_block_parts = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $attachment, $options, $minimal=false, $is_content_media=true, $extra_template_vars=$extra_template_vars );

    // Process Inner

    $license_metadata = $license_block_parts['inner'];
    // Allow filtering
    $license_metadata = apply_filters( 'bccl_license_block_media_' . esc_attr($attachment_type) . '_html', $license_metadata );

    // Add class to license metadata
    $license_metadata = '<span class="cc-attached-media-license cc-attached-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</span>';
    // License metadata as caption itself
    $license_metadata_as_caption = '<p class="wp-caption-text caption entry-caption cc-attached-media-license cc-attached-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</p>';

    // Add visible licensing information

    if ( $options['cc_media_license_omit_human_visible_data'] == '0' ) {

        // Append hyperlink with rel="license".
        if ( strpos($html, '</figcaption>') !== false ) {
            // If the figure html element contains a figcaption element,
            // then we add the HTML hyperlink at the end of it.
            $html = str_replace('</figcaption>', ' ' . $license_metadata . '</figcaption>', $html);
        } elseif ( strpos($html, '</p>') !== false ) {
            // If the caption is placed in a 'p' element
            $html = str_replace('</p>', ' ' . $license_metadata . '</p>', $html);
        } else {
            // Just append the license metadata
            $html .= $license_metadata_as_caption;
        }

    }

    $inner_html = $html;

    // Process outer

    $outer_html = $license_block_parts['outer'];

    // Finally process the #inner_html# template variable.
    return str_replace('#inner_html#', $inner_html, $outer_html);

}
add_filter('img_caption_shortcode', 'bccl_separate_licensing_on_images', 10, 3);


//
// Content filter that finds the img elements that contain images without caption
// and add licensing metadata.
//
function bccl_separate_licensing_on_images_without_caption( $post_content ) {

    // Plugin options
    $options = get_option('cc_settings');
    if ( $options === false || $options['cc_enable_individual_media_licensing'] == '0' ) {
        return $post_content;
    }

    $post = get_queried_object();

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return $post_content;
    }

    // Pattern that matches images without caption
    //$pattern_images_no_caption = '#<p>[\s\R]*(<img [^>]+>)#';
    $pattern_images_no_caption = '#<(?:p|h[\d]+)>[\s\R]*(<img [^>]+>)#';
    $pattern_images_no_caption = apply_filters('bccl_pattern_match_images_without_caption' , $pattern_images_no_caption);
    if ( ! preg_match_all( $pattern_images_no_caption, $post_content, $matches ) ) {
		return $post_content;
	}
    //var_dump($matches[1]);

    // Iterate over the found images and add licensing metadata

    foreach ( $matches[1] as $image_html ) {

        // Find the attachment ID
        if ( ! preg_match( '#wp-image-([0-9]+)#i', $image_html, $id_match ) ) {
            continue;
        }
        //var_dump($id_match[1]);
        $id = $id_match[1];

        // Find the attachment URL
        if ( ! preg_match( '#src="([^"]+)"#', $image_html, $url_match ) ) {
            continue;
        }
        //var_dump($url_match[1]);
        $current_attachment_url = $url_match[1];

        // Attachment
        $attachment = get_post(absint($id));
        $attachment_type = 'image';
        $attachment_dcmi_type = 'StillImage';
        
        // Image Width
        $width = null;
        if ( ! preg_match( '/width=["\']([0-9]+)/', $image_html, $width_match ) ) {
            continue;
        }
        $width = $width_match[1];

        // Alignment class
        $alignment_class = null;
        if ( preg_match( '#class="[^"]*(align(?:left|center|right))[^"]*"#', $image_html, $alignment_match ) ) {
            $alignment_class = $alignment_match[1];
        }

        // License data
        $license_slug = bccl_get_content_license_slug( $attachment, $options );
        if ( empty($license_slug) ) {
            continue;
        }
        // In case of ARR or manual license, just return the content without adding
        // any image licensing.
        if ( in_array($license_slug, array('manual', 'arr')) ) {
            continue;
        }
        $license_data = bccl_get_license_data( $license_slug );
        if ( empty($license_data) ) {
            continue;
        }
        // License URL.
        // We need it for the additional link elements we add in case visible
        // licensing metadata is not enabled and also to abort in case it is empty.
        $license_url = $license_data['url'];
        if ( empty($license_url) ) {
            continue;
        }

        // Notes:
        // 1. Sets the 'width' CSS property on outer div's 'style' attribute
        // 2. Sets the proper alignment class on outer div
        // 3. Sets the 'wp-caption' class to outer div

        // Enclosure style
        $enclosure_style = '';
        if ( ! is_null($width) ) {
            $enclosure_style = 'width: ' . esc_attr($width) . 'px;';
        }
        // Default classes
        $default_classes = 'cc-block-media cc-block-image wp-caption';
        if ( ! is_null($alignment_class) ) {
            $default_classes .= ' ' . esc_attr($alignment_class);
        }

        // Construct extra template vars
        $extra_template_vars = array(
            '#style#'          => $enclosure_style,
            '#classes#'        => $default_classes,
            '#attachment_url#' => $current_attachment_url,
        );

        // License metadata
        $license_block_parts = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $attachment, $options, $minimal=false, $is_content_media=true, $extra_template_vars=$extra_template_vars );

        // Process Inner

        $license_metadata = $license_block_parts['inner'];
        // Allow filtering
        $license_metadata = apply_filters( 'bccl_license_block_media_' . esc_attr($attachment_type) . '_html', $license_metadata );

        // Add class
        $license_metadata = '<span class="cc-attached-media-license cc-attached-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</span>';
        // License metadata as caption itself
        $license_metadata_as_caption = '<p class="wp-caption-text caption entry-caption cc-attached-media-license cc-attached-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</p>';

        // Add visible licensing information

        $html = $image_html;

        if ( $options['cc_media_license_omit_human_visible_data'] == '0' ) {

            // Just append the license metadata
            $html .= $license_metadata_as_caption;

        }

        $inner_html = $html;

        // Process outer

        $outer_html = $license_block_parts['outer'];

        // Finally process the #inner_html# template variable.
        $complete_image_html_with_license = str_replace('#inner_html#', $inner_html, $outer_html);
        $post_content = str_replace( $image_html, $complete_image_html_with_license, $post_content );

    }

    return $post_content;
}
add_filter( 'the_content', 'bccl_separate_licensing_on_images_without_caption', 100 );


//
// Video and Audio
//
function bccl_separate_licensing_on_video_audio( $html, $atts, $attachment, $post_id, $library ) {

    // Plugin options
    $options = get_option('cc_settings');
    if ( $options === false || $options['cc_enable_individual_media_licensing'] == '0' ) {
        return $html;
    }

    // Individual image licensing is added on posts, pages, attachments and custom post types.
    if ( ! is_singular() || is_front_page() ) {
        return $html;
    }

    //$post = get_queried_object();
    $post = get_post($post_id);

    if ( apply_filters('bccl_exclude_license', false, $post) ) {
        return $html;
    }

    // Attachment ID
    // Unfortunately, WP does not provide enough information in order to determine the attachment ID
    // So, first collect all the audio/video media file URLs in $attachments_urls
    $attachments_data = get_post_meta( $post->ID, 'enclosure', false );
    //var_dump($attachments_data);
    $attachments_urls = array();
    foreach ( $attachments_data as $attachment_data ) {
        $parts = preg_split('#\R#u', $attachment_data);
        $attachments_urls[] = $parts[0];
    }
    //var_dump($attachments_urls);
    // Then check which media file URL exists in the $atts array so as to
    // determine which attachment we are processing currently.
    $atts_values = array_values($atts);
    //var_dump($atts_values);
    // Find the URL of the attachment we are processing
    $current_attachment_url = '';
    foreach ( $attachments_urls as $attachment_url) {
        if ( in_array($attachment_url, $atts_values) ) {
            $current_attachment_url = $attachment_url;
            break;
        }
    }
    // Now use this URL to directly query the database for the post ID of the
    // attachment with guid = $current_attachment_url
    $id = bccl_attachment_id_from_url($current_attachment_url);
    if ( empty($id) ) {
        return $html;
    }
    //var_dump($id);

    // Attachment object
    $attachment = get_post( $id );
    $mime_type = get_post_mime_type( $attachment->ID );
    //$attachment_type = strstr( $mime_type, '/', true );
    // See why we do not use strstr(): http://www.codetrax.org/issues/1091
    $attachment_type = preg_replace( '#\/[^\/]*$#', '', $mime_type );
    $attachment_dcmi_type = '';
    if ( 'video' == $attachment_type ) {
        $attachment_dcmi_type = 'MovingImage';
    } elseif ( 'audio' == $attachment_type ) {
        $attachment_dcmi_type = 'Sound';
    }

    // License data
    $license_slug = bccl_get_content_license_slug( $attachment, $options );
    if ( empty($license_slug) ) {
        return $html;
    }
    // In case of ARR or manual license, just return the content without adding
    // any media licensing.
    if ( in_array($license_slug, array('manual', 'arr')) ) {
        return $html;
    }
    $license_data = bccl_get_license_data( $license_slug );
    if ( empty($license_data) ) {
        return $html;
    }
    // License URL.
    // We need it for the additional link elements we add in case visible
    // licensing metadata is not enabled and also to abort in case it is empty.
    $license_url = $license_data['url'];
    if ( empty($license_url) ) {
        return $html;
    }

    // NOTES:
    // 1. Exei wp-caption class sto outer div
    // no width
    // no alignment

    // Enclosure style
    $enclosure_style = '';
    // Default classes
    $default_classes = 'cc-block-media cc-block-' . esc_attr($attachment_type) . ' wp-caption';

    // Construct extra template vars
    $extra_template_vars = array(
        '#style#'          => $enclosure_style,
        '#classes#'        => $default_classes,
        '#attachment_url#' => $current_attachment_url,
    );

    // License metadata
    $license_block_parts = call_user_func( $license_data['generator_func'], $license_slug, $license_data, $attachment, $options, $minimal=false, $is_content_media=true, $extra_template_vars=$extra_template_vars );

    // Process Inner

    $license_metadata = $license_block_parts['inner'];
    // Allow filtering
    $license_metadata = apply_filters( 'bccl_license_block_media_' . esc_attr($attachment_type) . '_html', $license_metadata );

    // Add class
    // On video/audio captions we always use a p element since WP does not seem to generate any caption so far.
    $license_metadata = '<p class="wp-caption-text caption entry-caption cc-attached-media-license cc-attached-' . esc_attr($attachment_type) . '-license">' . $license_metadata . '</p>';

    // Add visible licensing information

    if ( $options['cc_media_license_omit_human_visible_data'] == '0' ) {
        $html .= $license_metadata;
    }

    $inner_html = $html;

    // Process outer

    $outer_html = $license_block_parts['outer'];

    // Finally process the #inner_html# template variable.
    return str_replace('#inner_html#', $inner_html, $outer_html);

}

// Does not work for Videos.
add_filter('wp_video_shortcode', 'bccl_separate_licensing_on_video_audio', 10, 5);
add_filter('wp_audio_shortcode', 'bccl_separate_licensing_on_video_audio', 10, 5);


