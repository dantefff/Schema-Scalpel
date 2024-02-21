<?php

namespace SchemaScalpel;

if (!defined('ABSPATH')) exit();

/**
 *
 * @package    Schema_Scalpel
 * @subpackage Schema_Scalpel/admin
 * @author     Kevin Gillispie
 */

class Schema_Scalpel_Admin
{
    private $schema_scalpel;
    private $version;

    public function __construct($schema_scalpel, $version)
    {
        $this->schema_scalpel = $schema_scalpel;
        $this->version = $version;
    }

    public function enqueue_styles()
    {
        if (stripos(get_current_screen()->id, "page_scsc") > -1) :
            wp_enqueue_style($this->schema_scalpel . "-bootstrap", plugins_url('/admin/css/bootstrap.min.css', SCHEMA_SCALPEL_PLUGIN), array(), $this->version, 'all');
            wp_enqueue_style($this->schema_scalpel . "-prism", plugins_url('/admin/css/prism.css', SCHEMA_SCALPEL_PLUGIN), array(), $this->version, 'all');
            wp_enqueue_style($this->schema_scalpel . "-admin", plugins_url('/admin/css/schema-scalpel-admin.css', SCHEMA_SCALPEL_PLUGIN), array(), $this->version, 'all');
        endif;
    }

    public function enqueue_scripts()
    {
        // placeholder 
    }

    public static function generate_blogposting_schema($type, $author)
    {
        global $wpdb;
        $args = array('numberposts' => -1, 'fields' => 'ids');
        $post_ids = get_posts($args);
        $existing_post_schema_result = $wpdb->get_results("SELECT DISTINCT(post_id) FROM {$wpdb->prefix}scsc_custom_schemas WHERE schema_type='posts'");
        $existing_post_schema_ids = array();
        foreach ($existing_post_schema_result as $key => $value) {
            $existing_post_schema_ids[] = $value->post_id;
        }
        foreach ($post_ids as $key => $ID) {
            if (in_array($ID, $existing_post_schema_ids)) continue;
            $post = get_post($ID, ARRAY_A);
            $post_title = $post['post_title'];
            $post_thumbnail_url = get_the_post_thumbnail_url($ID);
            $post_thumbnail_schema = (!empty($post_thumbnail_url)) ? "&quot;image&quot;:[&quot;{$post_thumbnail_url}&quot;]," : '';
            $post_published_time = get_post_time('Y-m-d\TH:i:s', true, $ID, true) . '+00:00';
            $post_modified_time = get_post_modified_time('Y-m-d\TH:i:s', true, $ID, true) . '+00:00';
            $author_display_name = get_the_author_meta('display_name', $post['post_author']);
            $author_url = get_the_author_meta('user_url', $post['post_author']);
            $blog_posting = <<<BLOGPOSTING
            {&quot;@context&quot;:&quot;https://schema.org&quot;,&quot;@type&quot;:&quot;{$type}&quot;,&quot;headline&quot;:&quot;{$post_title}&quot;,{$post_thumbnail_schema}&quot;datePublished&quot;:&quot;{$post_published_time}&quot;,&quot;dateModified&quot;:&quot;{$post_modified_time}&quot;,&quot;author&quot;:[{&quot;@type&quot;:&quot;{$author}&quot;,&quot;name&quot;:&quot;{$author_display_name}&quot;,&quot;url&quot;:&quot;{$author_url}&quot;}]}
            BLOGPOSTING;
            $wpdb->insert($wpdb->prefix . 'scsc_custom_schemas', array("custom_schema" => serialize($blog_posting), "schema_type" => "posts", "post_id" => $ID));
        }
    }
}
