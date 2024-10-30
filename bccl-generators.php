<?php
/**
 *  Contains generator functions providing the full text of each license.
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}



// Default License Templates
// Creative Commons licenses use these templates as is.
function bccl_default_license_templates() {
    return array(
        'license_text_long' => __('#work# by #creator# is licensed under a #license#.', 'creative-commons-configurator-1'), // Supports: #work#, #creator#, #license#, #year#
        'license_text_short' => __('This work is licensed under a #license#.', 'creative-commons-configurator-1'),  // Supports: #license#, #year#
        'extra_perms' => __('Permissions beyond the scope of this license may be available at #page#.', 'creative-commons-configurator-1'), // Supports: #page#
        'license_text_media' => __('(#media_type# by <em>#creator#</em> under #license#)', 'creative-commons-configurator-1'),  // Supports: #media_type#, #creator#, #license#
        'outer_html' => '<p prefix="dct: http://purl.org/dc/terms/ cc: http://creativecommons.org/ns#" class="cc-block">#inner_html#</p>',  // Supports: #inner_html# Mandatory: #inner_html#
        'outer_html_media' => '<div style="#style#" class="#classes#" prefix="dct: http://purl.org/dc/terms/ cc: http://creativecommons.org/ns#" typeof="cc:Work" about="#attachment_url#">#inner_html#</div>',   // Supports: #style#, #classes#, #attachment_url#, #inner_html# (mandatory: #inner_html#)
    );
}


// Generator Functions


// Generator for no licensing information (manual)
function bccl_manual_generator( $license_slug, $license_data, $post, $options, $minimal=false, $is_content_media=false, $extra_template_vars=array() ) {
    return '';
}


// All Rights Reserved Generator
function bccl_arr_generator( $license_slug, $license_data, $post, $options, $minimal=false, $is_content_media=false, $extra_template_vars=array() ) {
    $templates = array_merge( bccl_default_license_templates(), array(
        'license_text_long' => __('Copyright &copy; #year# #creator#. All Rights Reserved.', 'creative-commons-configurator-1'), // Supports: #work#, #creator#, #license#, #year#
        'license_text_short' => __('Copyright &copy; #year#. All Rights Reserved.', 'creative-commons-configurator-1'),  // Supports: #license#, #year#
        // TODO: perhaps add something like: Used under permission...
        //'license_text_media' => __('(#media_type# by <em>#creator#</em> - All Rights Reserved)', 'creative-commons-configurator-1'),  // Supports: #creator#, #license#
        'extra_perms' => '<br />' . __('Information about how to reuse or republish this work may be available at #page#.', 'creative-commons-configurator-1'), // Supports: #page#
    ));
    return bccl_base_generator( $license_slug, $license_data, $post, $options, $minimal, $is_content_media, $templates, $extra_template_vars );
}


// CC Zero Generator
function bccl_cc0_generator( $license_slug, $license_data, $post, $options, $minimal=false, $is_content_media=false, $extra_template_vars=array() ) {
    $templates = array_merge( bccl_default_license_templates(), array(
        'license_text_long' => __('To the extent possible under law, #creator# has waived all copyright and related or neighboring rights to #work#.', 'creative-commons-configurator-1'), // Supports: #work#, #creator#, #license#, #year#
        'license_text_short' => __('To the extent possible under law, the creator has waived all copyright and related or neighboring rights to this work.', 'creative-commons-configurator-1'),  // Supports: #license#, #year#
        'license_text_media' => __('(#media_type# by <em>#creator#</em> under #license#)', 'creative-commons-configurator-1'),  // Supports: #creator#, #license#
        'extra_perms' => __('Terms and conditions beyond the scope of this waiver may be available at #page#.', 'creative-commons-configurator-1') // Supports: #page#
    ));
    return bccl_base_generator( $license_slug, $license_data, $post, $options, $minimal, $is_content_media, $templates, $extra_template_vars );
}


// CC Generator
function bccl_cc_generator( $license_slug, $license_data, $post, $options, $minimal=false, $is_content_media=false, $extra_template_vars=array() ) {
    // Use default templates.
    $templates = bccl_default_license_templates();
    return bccl_base_generator( $license_slug, $license_data, $post, $options, $minimal, $is_content_media, $templates, $extra_template_vars );
}




// Base Generator
function bccl_base_generator( $license_slug, $license_data, $post, $options, $minimal, $is_content_media, $templates, $extra_template_vars ) {

    // Templates are required
    if ( empty( $templates ) ) {
        return '';
    }

    // Determine license group
    $license_group = bccl_get_license_group_name( $license_slug );

    // Allow filtering of the templates
    $templates = bccl_license_apply_filters( 'bccl_license_templates', $license_slug, $license_group, $templates );

    // License image hyperlink ($post is not used currently)
    $license_button_hyperlink = bccl_cc_generate_image_hyperlink( $license_slug, $license_group, $license_data, $post, $options );
    if ( is_null($post) ) {
        $work_title_hyperlink = '';
        $creator_hyperlink = '';
    } else {
        // Work hyperlink
        $work_title_hyperlink = bccl_get_work_hyperlink( $post );
        // Creator hyperlink
        $creator_hyperlink = bccl_get_creator_hyperlink( $post, $options["cc_creator"] );
    }

    // License
    // License URL
    $license_url = apply_filters( 'bccl_license_url', $license_data['url'] );
    // License name
    if ( $is_content_media == false ) {
        $license_name = $license_data['name'];
    } else {
        $license_name = $license_data['name_short'];
    }
    if ( empty($license_name) ) {
        $license_name = 'license';
    }
    // License Hyperlink
    if ( ! empty( $license_url ) ) {
        $license_hyperlink = sprintf( '<a rel="license" target="_blank" href="%s">%s</a>', esc_url($license_url), esc_attr($license_name) );
    } else {
        $license_hyperlink = sprintf( '<em rel="license">%s</em>', esc_attr($license_name) );
    }
    // License Text
    $license_text = '';
    if ( $is_content_media == false ) {
        // The license is about the post, page, attachment page, custom post type, etc
        $license_text_long_template = $templates['license_text_long'];
        $license_text_short_template = $templates['license_text_short'];
        if ( ! is_null($post) && ! empty( $license_text_long_template ) && $options['cc_extended'] == '1' ) {
            // Construct long license text.
            $license_text_long_template = bccl_license_apply_filters( 'bccl_license_text_long_template', $license_slug, $license_group, $license_text_long_template );
            $template_vars = array(
                '#work#'    => $work_title_hyperlink,
                '#creator#' => $creator_hyperlink,
                '#license#' => $license_hyperlink,
                '#year#'    => get_the_date('Y')
            );
            $license_text = $license_text_long_template;
            foreach ( $template_vars as $var_name=>$var_value ) {
                $license_text = str_replace( $var_name, $var_value, $license_text );
            }
            //$license_text = sprintf(__('%s by %s is licensed under a %s.', 'creative-commons-configurator-1'), $work_title_hyperlink, $creator_hyperlink, $license_hyperlink);
        } elseif ( ! empty( $license_text_short_template ) ) {
            // Construct short license text.
            $license_text_short_template = bccl_license_apply_filters( 'bccl_license_text_short_template', $license_slug, $license_group, $license_text_short_template );
            $template_vars = array(
                '#license#' => $license_hyperlink,
                '#year#'    => get_the_date('Y')
            );
            $license_text = $license_text_short_template;
            foreach ( $template_vars as $var_name=>$var_value ) {
                $license_text = str_replace( $var_name, $var_value, $license_text );
            }
            //$license_text = sprintf(__('This work is licensed under a %s.', 'creative-commons-configurator-1'), $license_hyperlink);
        }
        // Allow filtering of the license text
        $license_text = bccl_license_apply_filters( 'bccl_license_text', $license_slug, $license_group, $license_text );

    } else {    // $is_content_media - true
        // The license is for media within the content
        $license_text_media_template = $templates['license_text_media'];
        if ( ! is_null($post) && ! empty( $license_text_media_template ) ) {
            $license_text_media_template = bccl_license_apply_filters( 'bccl_license_text_media_template', $license_slug, $license_group, $license_text_media_template );
            // Determine media type
            $mime_type = get_post_mime_type( $post->ID );
            //$attachment_type = strstr( $mime_type, '/', true );
            // See why we do not use strstr(): http://www.codetrax.org/issues/1091
            $attachment_type = preg_replace( '#\/[^\/]*$#', '', $mime_type );
            // DCMI type
            $attachment_dcmi_type = null;
            if ( 'image' == $attachment_type ) {
                $attachment_dcmi_type = 'StillImage';
            } elseif ( 'video' == $attachment_type ) {
                $attachment_dcmi_type = 'MovingImage';
            } elseif ( 'audio' == $attachment_type ) {
                $attachment_dcmi_type = 'Sound';
            }

            $template_vars = array(
                '#media_type#' => $attachment_type,
                '#creator#'    => $creator_hyperlink,
                '#license#'    => $license_hyperlink,
            );
            $license_text = $license_text_media_template;
            foreach ( $template_vars as $var_name=>$var_value ) {
                $license_text = str_replace( $var_name, $var_value, $license_text );
            }

        }

    }

    // For media within the content.
    // Licensing for inline media does not support extra perms, so we process it here.
    if ( $is_content_media == true ) {
        // The generated license metadata, eg "Image by Creator under License",
        // may be used within the current caption (if exists - usually in images)
        // or as is as the caption (featured images, images without caption shortcode,
        // video and audio).
        // In order to give more flexibility to the media processors in creative-commons-configurator-1.php
        // we return an array of generated metadata of the form:
        //   array(
        //     'outer' => OUTER_HTML_ENCLOSURE,
        //     'inner' => MAIN_LICENSE_METADATA
        //   )
        //
        
        // The 'inner' part has already been generated.
        $license_block_parts = array();
        $license_block_parts['inner'] = $license_text;
        // Outer part
        $outer_html = $templates['outer_html_media'];
        $outer_html = bccl_license_apply_filters( 'bccl_outer_html_media_template', $license_slug, $license_group, $outer_html );
        // Expand template variables
        foreach( $extra_template_vars as $var_name=>$var_value ) {
            $outer_html = str_replace( $var_name, $var_value, $outer_html );
        }
        // Add non visible license metadata
        // Note: The link element inside the body needs the property or itemprop attribute.
        $non_visible_license_metadata = PHP_EOL;
        // License
        if ( ! empty( $license_url ) ) {
            $non_visible_license_metadata .= '<link href="' . esc_url($license_url) . '" rel="license" property="cc:license" type="text/html" />' . PHP_EOL;
        }
        // DCMI Type
        if ( isset($attachment_dcmi_type) ) {
            $non_visible_license_metadata .= '<link href="http://purl.org/dc/dcmitype/' .  esc_attr($attachment_dcmi_type) . '" rel="dct:type" property="dct:type" />' . PHP_EOL;
        }
        // Identifier
        if ( isset($post->ID) ) {
            $non_visible_license_metadata .= '<link href="' . esc_url(get_permalink($post->ID)) . '" rel="dct:identifier" property="dct:identifier" />' . PHP_EOL;
            //$non_visible_license_metadata .= '<link href="' . esc_url(get_permalink($post->ID)) . '" rel="alternate" type="text/html" />' . PHP_EOL;
        }
        // Construct all outer HTML
        $outer_html .= $non_visible_license_metadata;
        $license_block_parts['outer'] = $outer_html;

        // #inner_html# is processed in creative-commons-configurator-1.php
        return $license_block_parts;

    }

    // Extra perms
    $extra_perms_text = '';
    if ( is_null($post) ) {
        $extra_perms_url = $options['cc_perm_url'];
        $extra_perms_title = $options['cc_perm_title'];
    } else {
        $extra_perms_url = bccl_get_extra_perms_url( $post, $options );
        $extra_perms_title = bccl_get_extra_perms_title( $post, $options );
    }
    $extra_perms_template = $templates['extra_perms'];
    if ( ! empty( $extra_perms_template ) && ! empty( $extra_perms_url ) ) {
        if ( empty($extra_perms_title) ) {
            // If there is no title, use the URL as the anchor text.
            $extra_perms_title = $extra_perms_url;
        }
        $extra_perms_hyperlink = sprintf( '<a target="_blank" href="%s" rel="cc:morePermissions">%s</a>', esc_url($extra_perms_url), esc_attr($extra_perms_title) );
        // Construct extra permissions clause
        $extra_perms_template = bccl_license_apply_filters( 'bccl_extra_permissions_template', $license_slug, $license_group, $extra_perms_template );
        $template_vars = array(
            '#page#' => $extra_perms_hyperlink
        );
        $extra_perms_text = $extra_perms_template;
        foreach ( $template_vars as $var_name=>$var_value ) {
            $extra_perms_text = str_replace( $var_name, $var_value, $extra_perms_text );
        }
        //$extra_perms_text = sprintf($extra_perms_template, $extra_perms_hyperlink);
        // Alt text: Terms and conditions beyond the scope of this license may be available at %s.
    }
    // Allow filtering of the complete extra permissions clause.
    $extra_perms_text = bccl_license_apply_filters( 'bccl_extra_perms_text', $license_slug, $license_group, $extra_perms_text );

    // Construct HTML block
    if ( $minimal === false ) {

        $cc_block = array();
        // License Button
        if ( ! empty($license_button_hyperlink) ) {
            $cc_block[] = $license_button_hyperlink;
            //$cc_block[] = '<br />';
        }
        // License
        if ( ! empty($license_text) ) {
            $cc_block[] = $license_text;
        }
        // Extra perms
        if ( ! empty($extra_perms_text) ) {
            $cc_block[] = $extra_perms_text;
        }
        // Source Work
        //if ( ! empty($source_work_html) ) {
        //    $cc_block[] = '<br />';
        //    $cc_block[] = $source_work_html;
        //}

        // Construct full license text block
        // $pre_text = 'Copyright &copy; ' . get_the_date('Y') . ' - Some Rights Reserved' . '<br />';
        $full_license_block = implode(PHP_EOL, $cc_block);
        $full_license_block = bccl_license_apply_filters( 'bccl_full_license_block', $license_slug, $license_group, $full_license_block );
        // Construct enclosure
        //$enclosure_template = '<p prefix="dct: http://purl.org/dc/terms/ cc: http://creativecommons.org/ns#" class="cc-block">%s</p>';
        $enclosure_template = $templates['outer_html'];
        $enclosure_template = bccl_license_apply_filters( 'bccl_outer_html_template', $license_slug, $license_group, $enclosure_template );
        return str_replace('#inner_html#', $full_license_block, $enclosure_template);

    } else {    // $minimal === true
        // Construct HTML block
        $cc_block = array();
        // License Button
        if ( ! empty($license_button_hyperlink) ) {
            $cc_block[] = $license_button_hyperlink;
            //$cc_block[] = '<br />';
        }
        // License
        $cc_block[] = $license_hyperlink;
        // $pre_text = 'Copyright &copy; ' . get_the_date('Y') . ' - Some Rights Reserved' . '<br />';
        $minimal_license_block = implode(PHP_EOL, $cc_block);
        $minimal_license_block = bccl_license_apply_filters( 'bccl_minimal_license_block', $license_slug, $license_group, $minimal_license_block );
        return $minimal_license_block;
    }
}


// License Badge Shortcode

function bccl_license_badge_shortcode( $atts ) {
    // Entire list of supported parameters and their default values
    $pairs = array(
        'type'    => '',    // License slug (required)
        'compact' => '1',   // Display compact image.
        'link'    => '1',   // Create hyperlink to the license page at creativecommons.org
    );
    // Combined and filtered attribute list.
	$atts = shortcode_atts( $pairs, $atts, 'license' );

    // Construct the array with the slugs of the licenses supported by the shortcode.
    $license_slugs_all = array_keys( bccl_get_all_licenses() );
    $license_slugs_unsupported = apply_filters( 'bccl_shortcode_license_unsupported_slugs', array( 'manual', 'arr' ) );
    $license_slugs = array();
    foreach ( $license_slugs_all as $slug ) {
        if ( ! in_array( $slug, $license_slugs_unsupported ) ) {
            $license_slugs[] = $slug;
        }
    }

    // Check for required parameters.
    if ( empty( $atts['type'] ) ) {
        return '<code>license error: missing "type" - supported: ' . implode(', ', $license_slugs) . '</code>';
    }

    // Type validation
    if ( ! in_array( $atts['type'], $license_slugs ) ) {
        // If an invalid license type has been requested we return an empty string.
        // This way, even if the available licenses have been customized, any
        // [license] shortcode with invalid type in the posts would not print the error message.
        //return '<code>license error: invalid type - supported: ' . implode(', ', $license_slugs) . '</code>';
        return '';
    }

    // Get license data
    $license_data = bccl_get_license_data( $atts['type'] );

    // Construct absolute image URL
    $license_image_url = $license_data['button_compact_url'];
    if ( empty( $atts['compact'] ) ) {
        $license_image_url = $license_data['button_url'];
    }
    $license_image_url = bccl_make_absolute_image_url( $license_image_url );

    // Construct HTML output
    $html = '<div class="cc-badge">';
    if ( ! empty( $atts['link'] ) ) {
        $license_url = apply_filters( 'bccl_license_url_shortcode', $license_data['url'] );
        // We do not use rel="license" so as to avoid confusing the bots.
        $html .= sprintf('<a target="_blank" href="%s" title="%s">', esc_url($license_url), esc_attr($license_data['name']) );
    }
    $html .= sprintf('<img src="%s" alt="%s" />', esc_url($license_image_url), esc_attr($license_data['name']) );
    if ( ! empty( $atts['link'] ) ) {
        $html .= '</a>';
    }
    $html .= '</div>';

    $html = apply_filters( 'bccl_shortcode_badge_html', $html );

	return $html;
}
add_shortcode( 'license', 'bccl_license_badge_shortcode' );



    /****** VALID CODE FOR SOURCE WORK
    // Determine Source Work
    $source_work_html = '';
    // Source work
    $source_work_url = get_post_meta( $post->ID, '_bccl_source_work_url', true );
    $source_work_title = get_post_meta( $post->ID, '_bccl_source_work_title', true );
    // $source_work_url & $source_work_title are mandatory for the source work HTML to be generated.
    if ( ! empty($source_work_url) && ! empty($source_work_title) ) {
        $source_work_html = 'Based on';
        // Source work creator
        $source_creator_url = get_post_meta( $post->ID, '_bccl_source_creator_url', true );
        $source_creator_name = get_post_meta( $post->ID, '_bccl_source_creator_name', true );
        if ( empty($source_creator_name) ) {
            // If the creator name is empty, use the source creator URL instead.
            $source_creator_name = $source_creator_url;
        }
        $source_work_creator_html = sprintf('<a href="%s" property="cc:attributionName" rel="cc:attributionURL">%s</a>')
    }

    if ( ! empty($extra_perms_url) ) {
        if ( empty($extra_perms_title) ) {
            // If there is no title, use the URL as the anchor text.
            $extra_perms_title = $extra_perms_url;
        }
        $extra_perms_text = sprintf('Permissions beyond the scope of this license may be available at <a target="_blank" href="%s" rel="cc:morePermissions">%s</a>.', $extra_perms_url, $extra_perms_title);
    }
    *****/

