<?php
if (!defined('ABSPATH')) exit;

class NC_Display_Settings {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('wp_ajax_nc_save_display', array(__CLASS__, 'ajax_save'));
        add_action('wp_ajax_nc_apply_theme', array(__CLASS__, 'ajax_apply_theme'));
        add_action('wp_ajax_nc_save_custom_theme', array(__CLASS__, 'ajax_save_custom_theme'));
        add_action('wp_ajax_nc_delete_custom_theme', array(__CLASS__, 'ajax_delete_custom_theme'));
        add_action('wp_head', array(__CLASS__, 'output_frontend_css'), 99);
    }
    
    public static function add_menu() {
        add_submenu_page('edit.php?post_type=nc_event', 'Display Settings', 'Display & Themes', 'manage_options', 'nc-display', array(__CLASS__, 'render_page'));
    }
    
    public static function get_themes() {
        $themes = array(
            'gold_black' => array(
                'name' => 'Gold & Black',
                'home_bg' => '#000000', 'home_section_title_color' => '#EAE396', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#EAE396',
                'home_flyer_border' => 'rgba(234,227,150,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#EAE396', 'home_scroll_color' => '#EAE396',
                'cal_bg' => '#000000', 'cal_header_bg' => '#111111', 'cal_header_text' => '#EAE396', 'cal_weekday_bg' => '#1a1a1a', 'cal_weekday_text' => '#EAE396',
                'cal_day_bg' => '#1a1a1a', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#333333', 'cal_today_bg' => '#CA9134', 'cal_today_text' => '#000000',
                'cal_event_bg' => '#CA9134', 'cal_event_text' => '#000000', 'cal_nav_bg' => '#CA9134', 'cal_nav_text' => '#000000',
                'event_bg' => '#000000', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#EAE396', 'event_time_bg' => 'rgba(0,0,0,0.5)',
                'event_time_color' => '#FFFFFF', 'event_time_border_color' => 'rgba(234,227,150,0.3)', 'event_cover_bg' => 'rgba(202,145,52,0.2)',
                'event_cover_color' => '#EAE396', 'event_cover_border_color' => '#CA9134', 'event_guestlist_bg' => 'rgba(0,255,136,0.1)',
                'event_guestlist_color' => '#00ff88', 'event_desc_bg' => 'rgba(255,255,255,0.05)', 'event_desc_color' => '#FFFFFF',
                'event_back_bg' => 'transparent', 'event_back_color' => '#FFFFFF', 'event_back_border_color' => 'rgba(234,227,150,0.4)', 'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#EAE396',
            ),
            'minimal_2025' => array(
                'name' => 'Minimal 2025',
                'home_bg' => '#0a0a0a', 'home_section_title_color' => '#FFFFFF', 'home_flyer_glow' => '0', 'home_flyer_glow_color' => '#FFFFFF',
                'home_flyer_border' => 'rgba(255,255,255,0.1)', 'home_flyer_border_width' => '1', 'home_flyer_radius' => '4',
                'home_day_color' => '#888888', 'home_date_color' => '#FFFFFF', 'home_scroll_color' => '#333333', 'home_info_bg' => 'rgba(0,0,0,0.9)',
                'cal_bg' => '#0a0a0a', 'cal_header_bg' => '#0a0a0a', 'cal_header_text' => '#FFFFFF', 'cal_weekday_bg' => '#111111', 'cal_weekday_text' => '#666666',
                'cal_day_bg' => '#111111', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#1a1a1a', 'cal_today_bg' => '#FFFFFF', 'cal_today_text' => '#000000',
                'cal_event_bg' => '#333333', 'cal_event_text' => '#FFFFFF', 'cal_event_radius' => '2', 'cal_nav_bg' => '#222222', 'cal_nav_text' => '#FFFFFF', 'cal_nav_radius' => '4',
                'event_bg' => '#0a0a0a', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#888888', 'event_date_style' => 'normal',
                'event_time_bg' => '#111111', 'event_time_color' => '#FFFFFF', 'event_time_border' => '0', 'event_time_radius' => '4',
                'event_cover_bg' => '#111111', 'event_cover_color' => '#FFFFFF', 'event_cover_border' => '0', 'event_cover_radius' => '4',
                'event_guestlist_bg' => '#111111', 'event_guestlist_color' => '#FFFFFF',
                'event_desc_bg' => '#111111', 'event_desc_color' => '#AAAAAA', 'event_desc_border' => '0', 'event_desc_radius' => '4',
                'event_back_bg' => '#111111', 'event_back_color' => '#FFFFFF', 'event_back_border' => '0', 'event_back_radius' => '4',
                'event_flyer_shadow' => '0', 'event_flyer_border' => '0', 'event_flyer_radius' => '4',
            ),
            'neon_purple' => array(
                'name' => 'Neon Purple',
                'home_bg' => '#0a0a14', 'home_section_title_color' => '#bf5fff', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#bf5fff',
                'home_flyer_border' => 'rgba(191,95,255,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#bf5fff', 'home_scroll_color' => '#bf5fff',
                'cal_bg' => '#0a0a14', 'cal_header_bg' => '#0f0f1a', 'cal_header_text' => '#bf5fff', 'cal_weekday_bg' => '#14141f', 'cal_weekday_text' => '#bf5fff',
                'cal_day_bg' => '#14141f', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#1f1f2a', 'cal_today_bg' => '#9933ff', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#9933ff', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#9933ff', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#0a0a14', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#bf5fff', 'event_time_bg' => 'rgba(153,51,255,0.2)',
                'event_time_color' => '#FFFFFF', 'event_time_border_color' => 'rgba(191,95,255,0.3)', 'event_cover_bg' => 'rgba(153,51,255,0.2)',
                'event_cover_color' => '#bf5fff', 'event_cover_border_color' => '#9933ff', 'event_guestlist_bg' => 'rgba(153,51,255,0.1)',
                'event_guestlist_color' => '#bf5fff', 'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC',
                'event_back_bg' => '#1f1f2a', 'event_back_color' => '#bf5fff', 'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#bf5fff',
            ),
            'red_hot' => array(
                'name' => 'Red Hot',
                'home_bg' => '#0f0a0a', 'home_section_title_color' => '#ff3333', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#ff3333',
                'home_flyer_border' => 'rgba(255,51,51,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#ff3333', 'home_scroll_color' => '#ff3333',
                'cal_bg' => '#0f0a0a', 'cal_header_bg' => '#1a0f0f', 'cal_header_text' => '#ff3333', 'cal_weekday_bg' => '#1f1414', 'cal_weekday_text' => '#ff3333',
                'cal_day_bg' => '#1f1414', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#2a1a1a', 'cal_today_bg' => '#ff0000', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#ff0000', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#ff0000', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#0f0a0a', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#ff3333', 'event_time_bg' => 'rgba(255,0,0,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(255,0,0,0.2)', 'event_cover_color' => '#ff3333',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#2a1a1a', 'event_back_color' => '#ff3333',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#ff3333',
            ),
            'ice_blue' => array(
                'name' => 'Ice Blue',
                'home_bg' => '#0a0f14', 'home_section_title_color' => '#00bfff', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#00bfff',
                'home_flyer_border' => 'rgba(0,191,255,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#00bfff', 'home_scroll_color' => '#00bfff',
                'cal_bg' => '#0a0f14', 'cal_header_bg' => '#0f141a', 'cal_header_text' => '#00bfff', 'cal_weekday_bg' => '#141a1f', 'cal_weekday_text' => '#00bfff',
                'cal_day_bg' => '#141a1f', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#1a2028', 'cal_today_bg' => '#0099cc', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#0099cc', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#0099cc', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#0a0f14', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#00bfff', 'event_time_bg' => 'rgba(0,153,204,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(0,153,204,0.2)', 'event_cover_color' => '#00bfff',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#1a2028', 'event_back_color' => '#00bfff',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#00bfff',
            ),
            'green_neon' => array(
                'name' => 'Green Neon',
                'home_bg' => '#0a0f0a', 'home_section_title_color' => '#00ff88', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#00ff88',
                'home_flyer_border' => 'rgba(0,255,136,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#00ff88', 'home_scroll_color' => '#00ff88',
                'cal_bg' => '#0a0f0a', 'cal_header_bg' => '#0f140f', 'cal_header_text' => '#00ff88', 'cal_weekday_bg' => '#141a14', 'cal_weekday_text' => '#00ff88',
                'cal_day_bg' => '#141a14', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#1a201a', 'cal_today_bg' => '#00cc66', 'cal_today_text' => '#000000',
                'cal_event_bg' => '#00cc66', 'cal_event_text' => '#000000', 'cal_nav_bg' => '#00cc66', 'cal_nav_text' => '#000000',
                'event_bg' => '#0a0f0a', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#00ff88', 'event_time_bg' => 'rgba(0,204,102,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(0,204,102,0.2)', 'event_cover_color' => '#00ff88',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#1a201a', 'event_back_color' => '#00ff88',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#00ff88',
            ),
            'pink_party' => array(
                'name' => 'Pink Party',
                'home_bg' => '#140a10', 'home_section_title_color' => '#ff66b2', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#ff66b2',
                'home_flyer_border' => 'rgba(255,102,178,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#ff66b2', 'home_scroll_color' => '#ff66b2',
                'cal_bg' => '#140a10', 'cal_header_bg' => '#1a0f14', 'cal_header_text' => '#ff66b2', 'cal_weekday_bg' => '#1f141a', 'cal_weekday_text' => '#ff66b2',
                'cal_day_bg' => '#1f141a', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#2a1a22', 'cal_today_bg' => '#ff3399', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#ff3399', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#ff3399', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#140a10', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#ff66b2', 'event_time_bg' => 'rgba(255,51,153,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(255,51,153,0.2)', 'event_cover_color' => '#ff66b2',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#2a1a22', 'event_back_color' => '#ff66b2',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#ff66b2',
            ),
            'clean_white' => array(
                'name' => 'Clean White',
                'home_bg' => '#f5f5f5', 'home_section_title_color' => '#333333', 'home_flyer_glow' => '0', 'home_flyer_glow_color' => '#333333',
                'home_flyer_border' => 'rgba(0,0,0,0.1)', 'home_day_color' => '#666666', 'home_date_color' => '#333333', 'home_scroll_color' => '#CCCCCC',
                'home_info_bg' => '#FFFFFF',
                'cal_bg' => '#f5f5f5', 'cal_header_bg' => '#FFFFFF', 'cal_header_text' => '#333333', 'cal_weekday_bg' => '#FFFFFF', 'cal_weekday_text' => '#666666',
                'cal_day_bg' => '#FFFFFF', 'cal_day_text' => '#333333', 'cal_day_border' => '#EEEEEE', 'cal_today_bg' => '#333333', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#333333', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#333333', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#f5f5f5', 'event_title_color' => '#333333', 'event_date_color' => '#666666', 'event_time_bg' => '#FFFFFF',
                'event_time_color' => '#333333', 'event_time_border_color' => '#EEEEEE', 'event_cover_bg' => '#FFFFFF', 'event_cover_color' => '#333333',
                'event_desc_bg' => '#FFFFFF', 'event_desc_color' => '#666666', 'event_back_bg' => '#333333', 'event_back_color' => '#FFFFFF',
                'event_flyer_shadow' => '0', 'event_flyer_border' => '1', 'event_flyer_border_color' => '#EEEEEE',
            ),
            'midnight_blue' => array(
                'name' => 'Midnight Blue',
                'home_bg' => '#0a0a1a', 'home_section_title_color' => '#4488ff', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#4488ff',
                'home_flyer_border' => 'rgba(68,136,255,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#4488ff', 'home_scroll_color' => '#4488ff',
                'cal_bg' => '#0a0a1a', 'cal_header_bg' => '#0f0f20', 'cal_header_text' => '#4488ff', 'cal_weekday_bg' => '#141428', 'cal_weekday_text' => '#4488ff',
                'cal_day_bg' => '#141428', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#1a1a30', 'cal_today_bg' => '#2266cc', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#2266cc', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#2266cc', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#0a0a1a', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#4488ff', 'event_time_bg' => 'rgba(34,102,204,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(34,102,204,0.2)', 'event_cover_color' => '#4488ff',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#1a1a30', 'event_back_color' => '#4488ff',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#4488ff',
            ),
            'sunset_orange' => array(
                'name' => 'Sunset Orange',
                'home_bg' => '#140a05', 'home_section_title_color' => '#ff8833', 'home_flyer_glow' => '1', 'home_flyer_glow_color' => '#ff8833',
                'home_flyer_border' => 'rgba(255,136,51,0.3)', 'home_day_color' => '#FFFFFF', 'home_date_color' => '#ff8833', 'home_scroll_color' => '#ff8833',
                'cal_bg' => '#140a05', 'cal_header_bg' => '#1a0f08', 'cal_header_text' => '#ff8833', 'cal_weekday_bg' => '#1f1410', 'cal_weekday_text' => '#ff8833',
                'cal_day_bg' => '#1f1410', 'cal_day_text' => '#FFFFFF', 'cal_day_border' => '#2a1a14', 'cal_today_bg' => '#ff6600', 'cal_today_text' => '#FFFFFF',
                'cal_event_bg' => '#ff6600', 'cal_event_text' => '#FFFFFF', 'cal_nav_bg' => '#ff6600', 'cal_nav_text' => '#FFFFFF',
                'event_bg' => '#140a05', 'event_title_color' => '#FFFFFF', 'event_date_color' => '#ff8833', 'event_time_bg' => 'rgba(255,102,0,0.2)',
                'event_time_color' => '#FFFFFF', 'event_cover_bg' => 'rgba(255,102,0,0.2)', 'event_cover_color' => '#ff8833',
                'event_desc_bg' => 'rgba(255,255,255,0.03)', 'event_desc_color' => '#CCCCCC', 'event_back_bg' => '#2a1a14', 'event_back_color' => '#ff8833',
                'event_flyer_shadow' => '1', 'event_flyer_shadow_color' => '#ff8833',
            ),
        );
        // Add custom themes
        $custom = get_option('nc_custom_themes', array());
        return array_merge($themes, $custom);
    }
    
    public static function get_all_settings() {
        return array(
            // ===== HOMEPAGE =====
            'home_bg' => '#000000',
            'home_section_title' => 'UPCOMING EVENTS',
            'home_section_title_color' => '#EAE396',
            'home_section_title_size' => '24',
            'home_flyer_count' => '10',
            'home_flyer_width' => '160',
            'home_flyer_height' => '200',
            'home_flyer_radius' => '12',
            'home_flyer_spacing' => '15',
            'home_flyer_blur' => '1',
            'home_flyer_blur_amount' => '25',
            'home_flyer_glow' => '1',
            'home_flyer_glow_color' => '#EAE396',
            'home_flyer_glow_size' => '20',
            'home_flyer_border' => 'rgba(234,227,150,0.3)',
            'home_flyer_border_width' => '2',
            'home_show_day' => '1',
            'home_show_date' => '1',
            'home_show_title' => '0',
            'home_show_time' => '0',
            'home_date_format' => 'm/d/Y',
            'home_day_color' => '#FFFFFF',
            'home_day_size' => '12',
            'home_date_color' => '#EAE396',
            'home_date_size' => '14',
            'home_title_color' => '#FFFFFF',
            'home_info_bg' => 'rgba(0,0,0,0.8)',
            'home_scroll_color' => '#EAE396',
            
            // ===== CALENDAR =====
            'cal_bg' => '#000000',
            'cal_header_bg' => '#111111',
            'cal_header_text' => '#EAE396',
            'cal_header_size' => '28',
            'cal_weekday_bg' => '#1a1a1a',
            'cal_weekday_text' => '#EAE396',
            'cal_day_bg' => '#1a1a1a',
            'cal_day_text' => '#FFFFFF',
            'cal_day_border' => '#333333',
            'cal_today_bg' => '#CA9134',
            'cal_today_text' => '#000000',
            'cal_event_bg' => '#CA9134',
            'cal_event_text' => '#000000',
            'cal_event_radius' => '4',
            'cal_nav_bg' => '#CA9134',
            'cal_nav_text' => '#000000',
            'cal_nav_radius' => '20',
            
            // ===== EVENT PAGE =====
            'event_bg' => '#000000',
            'event_content_width' => '900',
            'event_content_padding' => '40',
            'event_flyer_max_width' => '500',
            'event_flyer_radius' => '12',
            'event_flyer_shadow' => '1',
            'event_flyer_shadow_color' => '#EAE396',
            'event_flyer_shadow_size' => '30',
            'event_flyer_border' => '1',
            'event_flyer_border_color' => 'rgba(234,227,150,0.3)',
            'event_flyer_border_width' => '3',
            'event_double_width' => '280',
            'event_double_gap' => '30',
            'event_title_color' => '#FFFFFF',
            'event_title_size' => '32',
            'event_title_weight' => '600',
            'event_title_transform' => 'none',
            'event_date_color' => '#EAE396',
            'event_date_size' => '18',
            'event_date_weight' => '400',
            'event_date_format' => 'l, F j, Y',
            'event_date_style' => 'italic',
            'event_time_show' => '1',
            'event_time_bg' => 'rgba(0,0,0,0.5)',
            'event_time_color' => '#FFFFFF',
            'event_time_size' => '16',
            'event_time_radius' => '20',
            'event_time_border' => '1',
            'event_time_border_color' => 'rgba(234,227,150,0.3)',
            'event_cover_show' => '1',
            'event_cover_label' => 'Cover:',
            'event_cover_bg' => 'rgba(202,145,52,0.2)',
            'event_cover_color' => '#EAE396',
            'event_cover_size' => '16',
            'event_cover_radius' => '8',
            'event_cover_border' => '1',
            'event_cover_border_color' => '#CA9134',
            'event_guestlist_show' => '1',
            'event_guestlist_label' => 'Guest List:',
            'event_guestlist_bg' => 'rgba(0,255,136,0.1)',
            'event_guestlist_color' => '#00ff88',
            'event_guestlist_size' => '14',
            'event_guestlist_radius' => '8',
            'event_age_show' => '1',
            'event_age_color' => '#CCCCCC',
            'event_age_size' => '14',
            'event_dresscode_show' => '1',
            'event_dresscode_color' => '#CCCCCC',
            'event_desc_show' => '1',
            'event_desc_bg' => 'rgba(255,255,255,0.05)',
            'event_desc_color' => '#CCCCCC',
            'event_desc_size' => '15',
            'event_desc_radius' => '12',
            'event_desc_padding' => '20',
            'event_desc_border' => '0',
            'event_desc_border_color' => 'rgba(255,255,255,0.1)',
            'event_back_show' => '1',
            'event_back_text' => '← BACK',
            'event_back_bg' => '#333333',
            'event_back_color' => '#EAE396',
            'event_back_size' => '14',
            'event_back_radius' => '20',
            'event_back_border' => '1',
            'event_back_border_color' => 'rgba(234,227,150,0.3)',
            'event_venue_show' => '1',
            'event_venue_color' => '#AAAAAA',
            'event_venue_size' => '14',
            'event_share_show' => '0',
            'event_share_color' => '#EAE396',
        );
    }
    
    public static function get_option($key) {
        $defaults = self::get_all_settings();
        return get_option('nc_display_' . $key, $defaults[$key] ?? '');
    }
    
    // Output CSS for calendar and event pages
    public static function output_frontend_css() {
        if (!is_singular('nc_event') && !is_page('calendar')) return;
        
        $o = array();
        foreach (self::get_all_settings() as $k => $d) {
            $o[$k] = get_option('nc_display_' . $k, $d);
        }
        
        echo '<style id="nc-display-css">';
        
        // Calendar page CSS
        if (is_page('calendar')) {
            echo "
            body, .site-content, .entry-content { background: {$o['cal_bg']} !important; }
            .nc-calendar-header, .calendar-header { background: {$o['cal_header_bg']} !important; color: {$o['cal_header_text']} !important; }
            .nc-calendar-header h2, .calendar-month-title { color: {$o['cal_header_text']} !important; font-size: {$o['cal_header_size']}px !important; }
            .nc-calendar th, .calendar-weekdays th { background: {$o['cal_weekday_bg']} !important; color: {$o['cal_weekday_text']} !important; }
            .nc-calendar td, .calendar-day { background: {$o['cal_day_bg']} !important; color: {$o['cal_day_text']} !important; border-color: {$o['cal_day_border']} !important; }
            .nc-calendar td.today, .calendar-day.today { background: {$o['cal_today_bg']} !important; color: {$o['cal_today_text']} !important; }
            .nc-calendar .event, .calendar-event { background: {$o['cal_event_bg']} !important; color: {$o['cal_event_text']} !important; border-radius: {$o['cal_event_radius']}px !important; }
            .nc-calendar-nav button, .calendar-nav button, .calendar-nav a { background: {$o['cal_nav_bg']} !important; color: {$o['cal_nav_text']} !important; border-radius: {$o['cal_nav_radius']}px !important; }
            ";
        }
        
        // Single event page CSS
        if (is_singular('nc_event')) {
            $shadow = $o['event_flyer_shadow'] === '1' ? "box-shadow: 0 0 {$o['event_flyer_shadow_size']}px {$o['event_flyer_shadow_color']}40;" : '';
            $border = $o['event_flyer_border'] === '1' ? "border: {$o['event_flyer_border_width']}px solid {$o['event_flyer_border_color']};" : '';
            $time_border = $o['event_time_border'] === '1' ? "border: 1px solid {$o['event_time_border_color']};" : '';
            $cover_border = $o['event_cover_border'] === '1' ? "border: 1px solid {$o['event_cover_border_color']};" : '';
            $desc_border = $o['event_desc_border'] === '1' ? "border: 1px solid {$o['event_desc_border_color']};" : '';
            $back_border = $o['event_back_border'] === '1' ? "border: 1px solid {$o['event_back_border_color']};" : '';
            
            echo "
            body.single-nc_event, body.single-nc_event .site-content { background: {$o['event_bg']} !important; }
            .nc-event-wrap { max-width: {$o['event_content_width']}px !important; padding: {$o['event_content_padding']}px !important; margin: 0 auto !important; }
            .nc-event-flyer, .event-flyer img { max-width: {$o['event_flyer_max_width']}px !important; border-radius: {$o['event_flyer_radius']}px !important; {$shadow} {$border} }
            .nc-event-flyers { gap: {$o['event_double_gap']}px !important; }
            .nc-event-flyers img { width: {$o['event_double_width']}px !important; }
            .nc-event-title, .event-title, .entry-title { color: {$o['event_title_color']} !important; font-size: {$o['event_title_size']}px !important; font-weight: {$o['event_title_weight']} !important; text-transform: {$o['event_title_transform']} !important; }
            .nc-event-date, .event-date { color: {$o['event_date_color']} !important; font-size: {$o['event_date_size']}px !important; font-weight: {$o['event_date_weight']} !important; font-style: {$o['event_date_style']} !important; }
            .nc-event-time, .event-time { background: {$o['event_time_bg']} !important; color: {$o['event_time_color']} !important; font-size: {$o['event_time_size']}px !important; border-radius: {$o['event_time_radius']}px !important; {$time_border} }
            .nc-event-cover, .event-cover { background: {$o['event_cover_bg']} !important; color: {$o['event_cover_color']} !important; font-size: {$o['event_cover_size']}px !important; border-radius: {$o['event_cover_radius']}px !important; {$cover_border} }
            .nc-event-guestlist, .event-guestlist { background: {$o['event_guestlist_bg']} !important; color: {$o['event_guestlist_color']} !important; font-size: {$o['event_guestlist_size']}px !important; border-radius: {$o['event_guestlist_radius']}px !important; }
            .nc-event-age, .event-age { color: {$o['event_age_color']} !important; font-size: {$o['event_age_size']}px !important; }
            .nc-event-dresscode, .event-dresscode { color: {$o['event_dresscode_color']} !important; }
            .nc-event-desc, .event-description, .entry-content { background: {$o['event_desc_bg']} !important; color: {$o['event_desc_color']} !important; font-size: {$o['event_desc_size']}px !important; border-radius: {$o['event_desc_radius']}px !important; padding: {$o['event_desc_padding']}px !important; {$desc_border} }
            .nc-event-back, .event-back, a.back-link { background: {$o['event_back_bg']} !important; color: {$o['event_back_color']} !important; font-size: {$o['event_back_size']}px !important; border-radius: {$o['event_back_radius']}px !important; {$back_border} }
            .nc-event-venue, .event-venue { color: {$o['event_venue_color']} !important; font-size: {$o['event_venue_size']}px !important; }
            .nc-event-share a, .event-share a { color: {$o['event_share_color']} !important; }
            ";
        }
        
        echo '</style>';
    }
    
    public static function ajax_save() {
        check_ajax_referer('nc_display_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        foreach (self::get_all_settings() as $key => $default) {
            if (isset($_POST[$key])) update_option('nc_display_' . $key, sanitize_text_field($_POST[$key]));
        }
        wp_send_json_success(array('message' => 'Settings saved'));
    }
    
    public static function ajax_apply_theme() {
        check_ajax_referer('nc_display_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $theme_id = sanitize_text_field($_POST['theme'] ?? '');
        $themes = self::get_themes();
        if (!isset($themes[$theme_id])) wp_send_json_error();
        $t = $themes[$theme_id];
        unset($t['name']);
        foreach ($t as $k => $v) {
            update_option('nc_display_' . $k, $v);
        }
        wp_send_json_success(array('message' => 'Theme applied: ' . $themes[$theme_id]['name']));
    }
    
    public static function ajax_save_custom_theme() {
        check_ajax_referer('nc_display_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $name = sanitize_text_field($_POST['theme_name'] ?? '');
        if (empty($name)) wp_send_json_error(array('message' => 'Theme name required'));
        $id = 'custom_' . sanitize_title($name);
        $theme = array('name' => $name);
        foreach (self::get_all_settings() as $k => $d) {
            $theme[$k] = get_option('nc_display_' . $k, $d);
        }
        $custom = get_option('nc_custom_themes', array());
        $custom[$id] = $theme;
        update_option('nc_custom_themes', $custom);
        wp_send_json_success(array('message' => 'Custom theme saved: ' . $name));
    }
    
    public static function ajax_delete_custom_theme() {
        check_ajax_referer('nc_display_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $id = sanitize_text_field($_POST['theme_id'] ?? '');
        $custom = get_option('nc_custom_themes', array());
        if (isset($custom[$id])) {
            unset($custom[$id]);
            update_option('nc_custom_themes', $custom);
            wp_send_json_success(array('message' => 'Theme deleted'));
        }
        wp_send_json_error();
    }
    
    public static function render_page() {
        $opts = array();
        foreach (self::get_all_settings() as $key => $default) {
            $opts[$key] = get_option('nc_display_' . $key, $default);
        }
        $themes = self::get_themes();
        $custom_themes = get_option('nc_custom_themes', array());
        $site_url = home_url();
        $cal_url = $site_url . '/calendar/';
        
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $now = time() - 21600;
        $event = $wpdb->get_row($wpdb->prepare("SELECT DISTINCT i.post_id FROM $table i JOIN {$wpdb->posts} p ON i.post_id=p.ID WHERE p.post_status IN ('publish','future') AND i.start>=%d ORDER BY i.start LIMIT 1", $now));
        $event_url = $event ? get_permalink($event->post_id) : $site_url;
        ?>
        <style>
        .nc-wrap{padding:12px;background:#1d1d1d;min-height:100vh;color:#fff;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:12px}
        .nc-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #333}
        .nc-header h1{margin:0;font-size:18px;color:#EAE396}
        .nc-themes-section{margin-bottom:12px;padding:12px;background:#252525;border-radius:8px}
        .nc-themes-section h3{margin:0 0 10px;font-size:11px;color:#888;text-transform:uppercase}
        .nc-themes{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
        .nc-theme-btn{padding:8px 12px;background:#333;border:1px solid #444;border-radius:6px;color:#ccc;cursor:pointer;font-size:10px;transition:all .2s}
        .nc-theme-btn:hover{border-color:#EAE396;color:#fff;transform:translateY(-1px)}
        .nc-theme-btn.custom{background:#1a3a1a;border-color:#2a5a2a}
        .nc-theme-btn .del{margin-left:6px;color:#ff6666;font-weight:bold}
        .nc-save-theme{display:flex;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid #333}
        .nc-save-theme input{flex:1;padding:8px;background:#1a1a1a;border:1px solid #444;border-radius:4px;color:#fff;font-size:11px}
        .nc-save-theme button{padding:8px 16px;background:#2a5a2a;border:none;border-radius:4px;color:#fff;cursor:pointer;font-size:11px}
        .nc-layout{display:grid;grid-template-columns:420px 1fr;gap:12px}
        .nc-page-tabs{display:flex;gap:4px;margin-bottom:12px}
        .nc-page-tab{flex:1;padding:10px 8px;background:#333;border:none;border-radius:6px;color:#888;cursor:pointer;font-size:11px;text-align:center}
        .nc-page-tab:hover{background:#3a3a3a;color:#fff}
        .nc-page-tab.active{background:#CA9134;color:#000;font-weight:600}
        .nc-settings{background:#252525;border-radius:8px;padding:12px;border:1px solid #333;max-height:calc(100vh - 220px);overflow-y:auto}
        .nc-page-settings{display:none}.nc-page-settings.active{display:block}
        .nc-section{margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #333}
        .nc-section:last-child{border-bottom:none;margin-bottom:0}
        .nc-section h3{margin:0 0 10px;font-size:10px;color:#EAE396;text-transform:uppercase;letter-spacing:1px}
        .nc-row{display:flex;align-items:center;margin-bottom:6px}
        .nc-row label{flex:0 0 95px;font-size:10px;color:#999}
        .nc-row input[type=number],.nc-row input[type=text],.nc-row select{flex:1;padding:6px 8px;border:1px solid #444;border-radius:4px;font-size:10px;background:#1a1a1a;color:#fff}
        .nc-row input[type=color]{width:36px;height:24px;padding:1px;border:1px solid #444;border-radius:3px;cursor:pointer;background:#1a1a1a}
        .nc-row .val{min-width:28px;text-align:right;font-size:9px;color:#666;margin-left:5px}
        .nc-save-btn{background:linear-gradient(135deg,#CA9134,#EAE396);color:#000;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;width:100%;margin-top:8px}
        .nc-preview{background:#252525;border-radius:8px;overflow:hidden;border:1px solid #333}
        .nc-preview-header{padding:8px 12px;background:#1a1a1a;border-bottom:1px solid #333;display:flex;justify-content:space-between;align-items:center}
        .nc-preview-header h2{margin:0;font-size:12px;color:#EAE396}
        .nc-refresh{background:#444;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:10px}
        .nc-preview-body{height:calc(100vh - 260px);background:#000}
        .nc-preview-body iframe{width:100%;height:100%;border:none}
        .nc-settings::-webkit-scrollbar{width:5px}.nc-settings::-webkit-scrollbar-track{background:#1a1a1a}.nc-settings::-webkit-scrollbar-thumb{background:#444;border-radius:3px}
        .nc-cols{display:grid;grid-template-columns:1fr 1fr;gap:5px}
        .nc-cols-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:5px}
        </style>
        <div class="nc-wrap">
        <div class="nc-header"><h1>Display & Theme Settings</h1></div>
        
        <div class="nc-themes-section">
            <h3>Preset Themes (Click to Apply)</h3>
            <div class="nc-themes">
            <?php foreach($themes as $id=>$t): if(strpos($id,'custom_')===0) continue; ?>
                <button class="nc-theme-btn" data-theme="<?php echo $id; ?>"><?php echo $t['name']; ?></button>
            <?php endforeach; ?>
            </div>
            <?php if(!empty($custom_themes)): ?>
            <h3>Your Custom Themes</h3>
            <div class="nc-themes">
            <?php foreach($custom_themes as $id=>$t): ?>
                <button class="nc-theme-btn custom" data-theme="<?php echo $id; ?>"><?php echo $t['name']; ?><span class="del" data-del="<?php echo $id; ?>">×</span></button>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="nc-save-theme">
                <input type="text" id="custom-theme-name" placeholder="Enter custom theme name...">
                <button id="save-custom-theme">Save Current as Theme</button>
            </div>
        </div>
        
        <div class="nc-page-tabs">
            <button class="nc-page-tab active" data-page="home" data-url="<?php echo esc_url($site_url); ?>">🏠 Homepage</button>
            <button class="nc-page-tab" data-page="calendar" data-url="<?php echo esc_url($cal_url); ?>">📅 Calendar</button>
            <button class="nc-page-tab" data-page="event" data-url="<?php echo esc_url($event_url); ?>">🖼 Event Page</button>
        </div>
        <div class="nc-layout">
        <div class="nc-settings">
        <form id="nc-form"><?php wp_nonce_field('nc_display_nonce', 'nonce'); ?>
        <!-- HOMEPAGE -->
        <div class="nc-page-settings active" data-page="home">
            <div class="nc-section"><h3>Section Header</h3>
                <div class="nc-row"><label>Title</label><input type="text" name="home_section_title" value="<?php echo esc_attr($opts['home_section_title']); ?>"></div>
                <div class="nc-cols">
                <div class="nc-row"><label>Color</label><input type="color" name="home_section_title_color" value="<?php echo esc_attr($opts['home_section_title_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="home_section_title_size" value="<?php echo esc_attr($opts['home_section_title_size']); ?>" min="14" max="48"><span class="val">px</span></div>
                </div>
                <div class="nc-row"><label>Background</label><input type="color" name="home_bg" value="<?php echo esc_attr($opts['home_bg']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Flyer Size</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Count</label><input type="number" name="home_flyer_count" value="<?php echo esc_attr($opts['home_flyer_count']); ?>" min="1" max="50"></div>
                <div class="nc-row"><label>Width</label><input type="number" name="home_flyer_width" value="<?php echo esc_attr($opts['home_flyer_width']); ?>" min="80" max="400"><span class="val">px</span></div>
                <div class="nc-row"><label>Height</label><input type="number" name="home_flyer_height" value="<?php echo esc_attr($opts['home_flyer_height']); ?>" min="80" max="500"><span class="val">px</span></div>
                <div class="nc-row"><label>Corners</label><input type="number" name="home_flyer_radius" value="<?php echo esc_attr($opts['home_flyer_radius']); ?>" min="0" max="50"><span class="val">px</span></div>
                <div class="nc-row"><label>Spacing</label><input type="number" name="home_flyer_spacing" value="<?php echo esc_attr($opts['home_flyer_spacing']); ?>" min="0" max="60"><span class="val">px</span></div>
                <div class="nc-row"><label>Border W</label><input type="number" name="home_flyer_border_width" value="<?php echo esc_attr($opts['home_flyer_border_width']); ?>" min="0" max="10"><span class="val">px</span></div>
                </div>
                <div class="nc-row"><label>Border Color</label><input type="text" name="home_flyer_border" value="<?php echo esc_attr($opts['home_flyer_border']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Blur Fill</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Enable</label><select name="home_flyer_blur"><option value="1" <?php selected($opts['home_flyer_blur'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_flyer_blur'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Amount</label><input type="number" name="home_flyer_blur_amount" value="<?php echo esc_attr($opts['home_flyer_blur_amount']); ?>" min="5" max="60"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Glow Effect</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Enable</label><select name="home_flyer_glow"><option value="1" <?php selected($opts['home_flyer_glow'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_flyer_glow'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Color</label><input type="color" name="home_flyer_glow_color" value="<?php echo esc_attr($opts['home_flyer_glow_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="home_flyer_glow_size" value="<?php echo esc_attr($opts['home_flyer_glow_size']); ?>" min="5" max="60"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Text Display</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show Day</label><select name="home_show_day"><option value="1" <?php selected($opts['home_show_day'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_show_day'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Show Date</label><select name="home_show_date"><option value="1" <?php selected($opts['home_show_date'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_show_date'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Show Title</label><select name="home_show_title"><option value="1" <?php selected($opts['home_show_title'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_show_title'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Show Time</label><select name="home_show_time"><option value="1" <?php selected($opts['home_show_time'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['home_show_time'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>Date Format</label><select name="home_date_format">
                    <option value="m/d/Y" <?php selected($opts['home_date_format'],'m/d/Y'); ?>>12/05/2025</option>
                    <option value="M j, Y" <?php selected($opts['home_date_format'],'M j, Y'); ?>>Dec 5, 2025</option>
                    <option value="F j" <?php selected($opts['home_date_format'],'F j'); ?>>December 5</option>
                    <option value="M j" <?php selected($opts['home_date_format'],'M j'); ?>>Dec 5</option>
                    <option value="n/j" <?php selected($opts['home_date_format'],'n/j'); ?>>12/5</option>
                </select></div>
            </div>
            <div class="nc-section"><h3>Text Colors & Sizes</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Day</label><input type="color" name="home_day_color" value="<?php echo esc_attr($opts['home_day_color']); ?>"></div>
                <div class="nc-row"><label>Day Size</label><input type="number" name="home_day_size" value="<?php echo esc_attr($opts['home_day_size']); ?>" min="8" max="24"><span class="val">px</span></div>
                <div class="nc-row"><label>Date</label><input type="color" name="home_date_color" value="<?php echo esc_attr($opts['home_date_color']); ?>"></div>
                <div class="nc-row"><label>Date Size</label><input type="number" name="home_date_size" value="<?php echo esc_attr($opts['home_date_size']); ?>" min="8" max="24"><span class="val">px</span></div>
                <div class="nc-row"><label>Title</label><input type="color" name="home_title_color" value="<?php echo esc_attr($opts['home_title_color']); ?>"></div>
                <div class="nc-row"><label>Scrollbar</label><input type="color" name="home_scroll_color" value="<?php echo esc_attr($opts['home_scroll_color']); ?>"></div>
                </div>
                <div class="nc-row"><label>Info BG</label><input type="text" name="home_info_bg" value="<?php echo esc_attr($opts['home_info_bg']); ?>"></div>
            </div>
        </div>
        <!-- CALENDAR -->
        <div class="nc-page-settings" data-page="calendar">
            <div class="nc-section"><h3>Page Background</h3>
                <div class="nc-row"><label>Background</label><input type="color" name="cal_bg" value="<?php echo esc_attr($opts['cal_bg']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Header / Month Title</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_header_bg" value="<?php echo esc_attr($opts['cal_header_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_header_text" value="<?php echo esc_attr($opts['cal_header_text']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="cal_header_size" value="<?php echo esc_attr($opts['cal_header_size']); ?>" min="16" max="48"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Weekday Row</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_weekday_bg" value="<?php echo esc_attr($opts['cal_weekday_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_weekday_text" value="<?php echo esc_attr($opts['cal_weekday_text']); ?>"></div>
                </div>
            </div>
            <div class="nc-section"><h3>Day Cells</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_day_bg" value="<?php echo esc_attr($opts['cal_day_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_day_text" value="<?php echo esc_attr($opts['cal_day_text']); ?>"></div>
                <div class="nc-row"><label>Border</label><input type="color" name="cal_day_border" value="<?php echo esc_attr($opts['cal_day_border']); ?>"></div>
                </div>
            </div>
            <div class="nc-section"><h3>Today Highlight</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_today_bg" value="<?php echo esc_attr($opts['cal_today_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_today_text" value="<?php echo esc_attr($opts['cal_today_text']); ?>"></div>
                </div>
            </div>
            <div class="nc-section"><h3>Event Items</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_event_bg" value="<?php echo esc_attr($opts['cal_event_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_event_text" value="<?php echo esc_attr($opts['cal_event_text']); ?>"></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="cal_event_radius" value="<?php echo esc_attr($opts['cal_event_radius']); ?>" min="0" max="20"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Navigation Buttons</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>BG</label><input type="color" name="cal_nav_bg" value="<?php echo esc_attr($opts['cal_nav_bg']); ?>"></div>
                <div class="nc-row"><label>Text</label><input type="color" name="cal_nav_text" value="<?php echo esc_attr($opts['cal_nav_text']); ?>"></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="cal_nav_radius" value="<?php echo esc_attr($opts['cal_nav_radius']); ?>" min="0" max="30"><span class="val">px</span></div>
                </div>
            </div>
        </div>
        <!-- EVENT PAGE -->
        <div class="nc-page-settings" data-page="event">
            <div class="nc-section"><h3>Page Layout</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Background</label><input type="color" name="event_bg" value="<?php echo esc_attr($opts['event_bg']); ?>"></div>
                <div class="nc-row"><label>Width</label><input type="number" name="event_content_width" value="<?php echo esc_attr($opts['event_content_width']); ?>" min="400" max="1400"><span class="val">px</span></div>
                <div class="nc-row"><label>Padding</label><input type="number" name="event_content_padding" value="<?php echo esc_attr($opts['event_content_padding']); ?>" min="10" max="100"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Flyer Image</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Max Width</label><input type="number" name="event_flyer_max_width" value="<?php echo esc_attr($opts['event_flyer_max_width']); ?>" min="150" max="900"><span class="val">px</span></div>
                <div class="nc-row"><label>Corners</label><input type="number" name="event_flyer_radius" value="<?php echo esc_attr($opts['event_flyer_radius']); ?>" min="0" max="50"><span class="val">px</span></div>
                <div class="nc-row"><label>Shadow</label><select name="event_flyer_shadow"><option value="1" <?php selected($opts['event_flyer_shadow'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_flyer_shadow'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Shadow Color</label><input type="color" name="event_flyer_shadow_color" value="<?php echo esc_attr($opts['event_flyer_shadow_color']); ?>"></div>
                <div class="nc-row"><label>Shadow Size</label><input type="number" name="event_flyer_shadow_size" value="<?php echo esc_attr($opts['event_flyer_shadow_size']); ?>" min="5" max="80"><span class="val">px</span></div>
                <div class="nc-row"><label>Border</label><select name="event_flyer_border"><option value="1" <?php selected($opts['event_flyer_border'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_flyer_border'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>Border Color</label><input type="text" name="event_flyer_border_color" value="<?php echo esc_attr($opts['event_flyer_border_color']); ?>"></div>
                <div class="nc-row"><label>Border Width</label><input type="number" name="event_flyer_border_width" value="<?php echo esc_attr($opts['event_flyer_border_width']); ?>" min="0" max="10"><span class="val">px</span></div>
            </div>
            <div class="nc-section"><h3>Double Flyer Layout</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Each Width</label><input type="number" name="event_double_width" value="<?php echo esc_attr($opts['event_double_width']); ?>" min="100" max="500"><span class="val">px</span></div>
                <div class="nc-row"><label>Gap</label><input type="number" name="event_double_gap" value="<?php echo esc_attr($opts['event_double_gap']); ?>" min="5" max="80"><span class="val">px</span></div>
                </div>
            </div>
            <div class="nc-section"><h3>Event Title</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Color</label><input type="color" name="event_title_color" value="<?php echo esc_attr($opts['event_title_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_title_size" value="<?php echo esc_attr($opts['event_title_size']); ?>" min="14" max="60"><span class="val">px</span></div>
                <div class="nc-row"><label>Weight</label><select name="event_title_weight">
                    <option value="400" <?php selected($opts['event_title_weight'],'400'); ?>>Normal</option>
                    <option value="500" <?php selected($opts['event_title_weight'],'500'); ?>>Medium</option>
                    <option value="600" <?php selected($opts['event_title_weight'],'600'); ?>>Semi-Bold</option>
                    <option value="700" <?php selected($opts['event_title_weight'],'700'); ?>>Bold</option>
                </select></div>
                <div class="nc-row"><label>Transform</label><select name="event_title_transform">
                    <option value="none" <?php selected($opts['event_title_transform'],'none'); ?>>None</option>
                    <option value="uppercase" <?php selected($opts['event_title_transform'],'uppercase'); ?>>UPPERCASE</option>
                    <option value="capitalize" <?php selected($opts['event_title_transform'],'capitalize'); ?>>Capitalize</option>
                </select></div>
                </div>
            </div>
            <div class="nc-section"><h3>Date</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Color</label><input type="color" name="event_date_color" value="<?php echo esc_attr($opts['event_date_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_date_size" value="<?php echo esc_attr($opts['event_date_size']); ?>" min="10" max="36"><span class="val">px</span></div>
                <div class="nc-row"><label>Weight</label><select name="event_date_weight">
                    <option value="400" <?php selected($opts['event_date_weight'],'400'); ?>>Normal</option>
                    <option value="600" <?php selected($opts['event_date_weight'],'600'); ?>>Semi-Bold</option>
                </select></div>
                <div class="nc-row"><label>Style</label><select name="event_date_style">
                    <option value="normal" <?php selected($opts['event_date_style'],'normal'); ?>>Normal</option>
                    <option value="italic" <?php selected($opts['event_date_style'],'italic'); ?>>Italic</option>
                </select></div>
                </div>
                <div class="nc-row"><label>Format</label><select name="event_date_format">
                    <option value="l, F j, Y" <?php selected($opts['event_date_format'],'l, F j, Y'); ?>>Friday, December 5, 2025</option>
                    <option value="F j, Y" <?php selected($opts['event_date_format'],'F j, Y'); ?>>December 5, 2025</option>
                    <option value="M j, Y" <?php selected($opts['event_date_format'],'M j, Y'); ?>>Dec 5, 2025</option>
                    <option value="m/d/Y" <?php selected($opts['event_date_format'],'m/d/Y'); ?>>12/05/2025</option>
                </select></div>
            </div>
            <div class="nc-section"><h3>Time</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show</label><select name="event_time_show"><option value="1" <?php selected($opts['event_time_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_time_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Color</label><input type="color" name="event_time_color" value="<?php echo esc_attr($opts['event_time_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_time_size" value="<?php echo esc_attr($opts['event_time_size']); ?>" min="10" max="28"><span class="val">px</span></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="event_time_radius" value="<?php echo esc_attr($opts['event_time_radius']); ?>" min="0" max="30"><span class="val">px</span></div>
                <div class="nc-row"><label>Border</label><select name="event_time_border"><option value="1" <?php selected($opts['event_time_border'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_time_border'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>BG</label><input type="text" name="event_time_bg" value="<?php echo esc_attr($opts['event_time_bg']); ?>"></div>
                <div class="nc-row"><label>Border Color</label><input type="text" name="event_time_border_color" value="<?php echo esc_attr($opts['event_time_border_color']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Cover / Tickets</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show</label><select name="event_cover_show"><option value="1" <?php selected($opts['event_cover_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_cover_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Label</label><input type="text" name="event_cover_label" value="<?php echo esc_attr($opts['event_cover_label']); ?>"></div>
                <div class="nc-row"><label>Color</label><input type="color" name="event_cover_color" value="<?php echo esc_attr($opts['event_cover_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_cover_size" value="<?php echo esc_attr($opts['event_cover_size']); ?>" min="10" max="24"><span class="val">px</span></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="event_cover_radius" value="<?php echo esc_attr($opts['event_cover_radius']); ?>" min="0" max="20"><span class="val">px</span></div>
                <div class="nc-row"><label>Border</label><select name="event_cover_border"><option value="1" <?php selected($opts['event_cover_border'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_cover_border'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>BG</label><input type="text" name="event_cover_bg" value="<?php echo esc_attr($opts['event_cover_bg']); ?>"></div>
                <div class="nc-row"><label>Border Color</label><input type="color" name="event_cover_border_color" value="<?php echo esc_attr($opts['event_cover_border_color']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Guest List</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show</label><select name="event_guestlist_show"><option value="1" <?php selected($opts['event_guestlist_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_guestlist_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Label</label><input type="text" name="event_guestlist_label" value="<?php echo esc_attr($opts['event_guestlist_label']); ?>"></div>
                <div class="nc-row"><label>Color</label><input type="color" name="event_guestlist_color" value="<?php echo esc_attr($opts['event_guestlist_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_guestlist_size" value="<?php echo esc_attr($opts['event_guestlist_size']); ?>" min="10" max="20"><span class="val">px</span></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="event_guestlist_radius" value="<?php echo esc_attr($opts['event_guestlist_radius']); ?>" min="0" max="20"><span class="val">px</span></div>
                </div>
                <div class="nc-row"><label>BG</label><input type="text" name="event_guestlist_bg" value="<?php echo esc_attr($opts['event_guestlist_bg']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Age / Dress Code</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show Age</label><select name="event_age_show"><option value="1" <?php selected($opts['event_age_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_age_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Age Color</label><input type="color" name="event_age_color" value="<?php echo esc_attr($opts['event_age_color']); ?>"></div>
                <div class="nc-row"><label>Age Size</label><input type="number" name="event_age_size" value="<?php echo esc_attr($opts['event_age_size']); ?>" min="10" max="20"><span class="val">px</span></div>
                <div class="nc-row"><label>Show Dress</label><select name="event_dresscode_show"><option value="1" <?php selected($opts['event_dresscode_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_dresscode_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Dress Color</label><input type="color" name="event_dresscode_color" value="<?php echo esc_attr($opts['event_dresscode_color']); ?>"></div>
                </div>
            </div>
            <div class="nc-section"><h3>Description Box</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show</label><select name="event_desc_show"><option value="1" <?php selected($opts['event_desc_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_desc_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Color</label><input type="color" name="event_desc_color" value="<?php echo esc_attr($opts['event_desc_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_desc_size" value="<?php echo esc_attr($opts['event_desc_size']); ?>" min="12" max="22"><span class="val">px</span></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="event_desc_radius" value="<?php echo esc_attr($opts['event_desc_radius']); ?>" min="0" max="30"><span class="val">px</span></div>
                <div class="nc-row"><label>Padding</label><input type="number" name="event_desc_padding" value="<?php echo esc_attr($opts['event_desc_padding']); ?>" min="5" max="50"><span class="val">px</span></div>
                <div class="nc-row"><label>Border</label><select name="event_desc_border"><option value="1" <?php selected($opts['event_desc_border'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_desc_border'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>BG</label><input type="text" name="event_desc_bg" value="<?php echo esc_attr($opts['event_desc_bg']); ?>"></div>
                <div class="nc-row"><label>Border Color</label><input type="text" name="event_desc_border_color" value="<?php echo esc_attr($opts['event_desc_border_color']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Back Button</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show</label><select name="event_back_show"><option value="1" <?php selected($opts['event_back_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_back_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Text</label><input type="text" name="event_back_text" value="<?php echo esc_attr($opts['event_back_text']); ?>"></div>
                <div class="nc-row"><label>BG</label><input type="color" name="event_back_bg" value="<?php echo esc_attr($opts['event_back_bg']); ?>"></div>
                <div class="nc-row"><label>Color</label><input type="color" name="event_back_color" value="<?php echo esc_attr($opts['event_back_color']); ?>"></div>
                <div class="nc-row"><label>Size</label><input type="number" name="event_back_size" value="<?php echo esc_attr($opts['event_back_size']); ?>" min="10" max="20"><span class="val">px</span></div>
                <div class="nc-row"><label>Radius</label><input type="number" name="event_back_radius" value="<?php echo esc_attr($opts['event_back_radius']); ?>" min="0" max="30"><span class="val">px</span></div>
                <div class="nc-row"><label>Border</label><select name="event_back_border"><option value="1" <?php selected($opts['event_back_border'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_back_border'],'0'); ?>>No</option></select></div>
                </div>
                <div class="nc-row"><label>Border Color</label><input type="text" name="event_back_border_color" value="<?php echo esc_attr($opts['event_back_border_color']); ?>"></div>
            </div>
            <div class="nc-section"><h3>Venue & Share</h3>
                <div class="nc-cols">
                <div class="nc-row"><label>Show Venue</label><select name="event_venue_show"><option value="1" <?php selected($opts['event_venue_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_venue_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Venue Color</label><input type="color" name="event_venue_color" value="<?php echo esc_attr($opts['event_venue_color']); ?>"></div>
                <div class="nc-row"><label>Venue Size</label><input type="number" name="event_venue_size" value="<?php echo esc_attr($opts['event_venue_size']); ?>" min="10" max="20"><span class="val">px</span></div>
                <div class="nc-row"><label>Show Share</label><select name="event_share_show"><option value="1" <?php selected($opts['event_share_show'],'1'); ?>>Yes</option><option value="0" <?php selected($opts['event_share_show'],'0'); ?>>No</option></select></div>
                <div class="nc-row"><label>Share Color</label><input type="color" name="event_share_color" value="<?php echo esc_attr($opts['event_share_color']); ?>"></div>
                </div>
            </div>
        </div>
        <button type="submit" class="nc-save-btn">Save Settings</button>
        </form>
        </div>
        <div class="nc-preview">
            <div class="nc-preview-header"><h2>Live Preview</h2><button class="nc-refresh" onclick="refreshPreview()">↻ Refresh</button></div>
            <div class="nc-preview-body"><iframe id="preview-frame" src="<?php echo esc_url($site_url); ?>"></iframe></div>
        </div>
        </div></div>
        <script>
        (function($){
            $('.nc-page-tab').on('click',function(){
                var page=$(this).data('page'),url=$(this).data('url');
                $('.nc-page-tab').removeClass('active');$(this).addClass('active');
                $('.nc-page-settings').removeClass('active');$('.nc-page-settings[data-page="'+page+'"]').addClass('active');
                $('#preview-frame').attr('src',url);
            });
            window.refreshPreview=function(){var f=document.getElementById('preview-frame');f.src=f.src.split('?')[0]+'?t='+Date.now();};
            $('.nc-theme-btn').on('click',function(e){
                if($(e.target).hasClass('del')){
                    e.stopPropagation();
                    if(!confirm('Delete this theme?'))return;
                    $.post(ajaxurl,{action:'nc_delete_custom_theme',theme_id:$(e.target).data('del'),nonce:$('#nonce').val()},function(r){
                        if(r.success)location.reload();
                    });
                    return;
                }
                var t=$(this).data('theme');
                $.post(ajaxurl,{action:'nc_apply_theme',theme:t,nonce:$('#nonce').val()},function(r){
                    if(r.success){alert(r.data.message);location.reload();}
                });
            });
            $('#save-custom-theme').on('click',function(){
                var name=$('#custom-theme-name').val().trim();
                if(!name){alert('Enter a theme name');return;}
                $.post(ajaxurl,{action:'nc_save_custom_theme',theme_name:name,nonce:$('#nonce').val()},function(r){
                    if(r.success){alert(r.data.message);location.reload();}else{alert(r.data.message||'Error');}
                });
            });
            $('#nc-form').on('submit',function(e){
                e.preventDefault();var $btn=$('.nc-save-btn');$btn.text('Saving...');
                $.post(ajaxurl,$(this).serialize()+'&action=nc_save_display',function(r){
                    if(r.success){$btn.text('Saved!');setTimeout(function(){$btn.text('Save Settings');refreshPreview();},1000);}
                    else $btn.text('Error');
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
NC_Display_Settings::init();
