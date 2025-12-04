<?php
/**
 * Elementor Widgets for Nightclub Events
 * Uses Display Settings from nc-display-settings.php
 */

if (!defined('ABSPATH')) exit;

class NC_Elementor_Integration {
    
    public static function init() {
        add_action('elementor/widgets/register', array(__CLASS__, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array(__CLASS__, 'add_category'));
    }
    
    public static function add_category($elements_manager) {
        $elements_manager->add_category('nightclub-events', array(
            'title' => 'Nightclub Events',
            'icon' => 'fa fa-calendar',
        ));
    }
    
    public static function register_widgets($widgets_manager) {
        require_once(__DIR__ . '/widgets/class-event-flyers-widget.php');
        require_once(__DIR__ . '/widgets/class-event-calendar-widget.php');
        require_once(__DIR__ . '/widgets/class-upcoming-events-widget.php');
        
        $widgets_manager->register(new NC_Event_Flyers_Widget());
        $widgets_manager->register(new NC_Event_Calendar_Widget());
        $widgets_manager->register(new NC_Upcoming_Events_Widget());
    }
}

// Initialize if Elementor is active
add_action('plugins_loaded', function() {
    if (did_action('elementor/loaded')) {
        NC_Elementor_Integration::init();
    }
});
