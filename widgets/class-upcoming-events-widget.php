<?php
if (!defined('ABSPATH')) exit;

class NC_Upcoming_Events_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { return 'nc_upcoming_events'; }
    public function get_title() { return 'Upcoming Events List'; }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return ['nightclub-events']; }
    
    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => 'Settings',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('count', [
            'label' => 'Number of Events',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
            'min' => 1,
            'max' => 20,
        ]);
        
        $this->add_control('use_global', [
            'label' => 'Use Global Theme Settings',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'description' => 'Uses settings from Events → Display & Themes',
        ]);
        
        $this->end_controls_section();
        
        $this->start_controls_section('style_section', [
            'label' => 'Custom Colors',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => ['use_global' => ''],
        ]);
        
        $this->add_control('bg_color', [
            'label' => 'Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#000000',
        ]);
        
        $this->add_control('title_color', [
            'label' => 'Title Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
        ]);
        
        $this->add_control('date_color', [
            'label' => 'Date Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#EAE396',
        ]);
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $count = $settings['count'] ?: 5;
        
        if ($settings['use_global'] === 'yes') {
            echo do_shortcode('[nc_upcoming count="' . intval($count) . '"]');
            return;
        }
        
        $bgColor = $settings['bg_color'] ?: '#000000';
        $titleColor = $settings['title_color'] ?: '#FFFFFF';
        $dateColor = $settings['date_color'] ?: '#EAE396';
        
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        
        $now = new DateTime('now', $tz);
        $now->setTime(0, 0, 0);
        
        $instances = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.post_title, p.ID as post_id 
             FROM $table i 
             JOIN {$wpdb->posts} p ON i.post_id = p.ID 
             WHERE p.post_status = 'publish' AND i.start >= %d 
             ORDER BY i.start ASC LIMIT %d",
            $now->getTimestamp(), $count
        ));
        ?>
        <div style="background:<?php echo $bgColor; ?>;padding:20px;border-radius:10px;">
            <?php foreach ($instances as $inst): 
                $dt = new DateTime('@' . $inst->start);
                $dt->setTimezone($tz);
            ?>
            <a href="<?php echo get_permalink($inst->post_id); ?>" style="display:flex;gap:15px;padding:12px;margin-bottom:10px;background:rgba(255,255,255,0.05);border-radius:8px;text-decoration:none;">
                <div style="min-width:50px;text-align:center;">
                    <div style="color:<?php echo $dateColor; ?>;font-size:24px;font-weight:bold;"><?php echo $dt->format('j'); ?></div>
                    <div style="color:<?php echo $dateColor; ?>;font-size:12px;"><?php echo $dt->format('M'); ?></div>
                </div>
                <div>
                    <div style="color:<?php echo $titleColor; ?>;font-weight:bold;"><?php echo esc_html($inst->post_title); ?></div>
                    <div style="color:<?php echo $dateColor; ?>;font-size:13px;"><?php echo $dt->format('l'); ?> • <?php echo get_post_meta($inst->post_id, '_nc_start_time', true); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
