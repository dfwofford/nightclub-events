<?php
if (!defined('ABSPATH')) exit;

class NC_Event_Flyers_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { return 'nc_event_flyers'; }
    public function get_title() { return 'Event Flyers'; }
    public function get_icon() { return 'eicon-gallery-grid'; }
    public function get_categories() { return ['nightclub-events']; }
    
    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => 'Settings',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('count', [
            'label' => 'Number of Events',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 10,
            'min' => 1,
            'max' => 50,
        ]);
        
        $this->add_control('use_global', [
            'label' => 'Use Global Theme Settings',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'description' => 'Uses settings from Events → Display & Themes',
        ]);
        
        $this->add_control('override_note', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => '<div style="background:#f0f7fc;padding:10px;border-radius:5px;font-size:12px;">💡 Turn off "Use Global Theme Settings" to customize this widget individually, or go to <strong>Events → Display & Themes</strong> to change the global look.</div>',
            'condition' => ['use_global' => 'yes'],
        ]);
        
        $this->end_controls_section();
        
        // Override controls (shown when use_global is off)
        $this->start_controls_section('override_section', [
            'label' => 'Custom Style',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => ['use_global' => ''],
        ]);
        
        $this->add_control('flyer_format', [
            'label' => 'Flyer Format',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'portrait',
            'options' => [
                'square' => 'Square (1:1)',
                'portrait' => 'Portrait (4:5)',
                'story' => 'Story (9:16)',
                'landscape' => 'Landscape (16:9)',
            ],
        ]);
        
        $this->add_control('flyer_size', [
            'label' => 'Flyer Size',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'medium',
            'options' => [
                'small' => 'Small',
                'medium' => 'Medium',
                'large' => 'Large',
                'xlarge' => 'X-Large',
            ],
        ]);
        
        $this->add_control('layout_style', [
            'label' => 'Layout',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'slider',
            'options' => [
                'slider' => 'Horizontal Slider',
                'grid' => 'Grid',
            ],
        ]);
        
        $this->add_control('text_position', [
            'label' => 'Text Position',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'below',
            'options' => [
                'below' => 'Below Flyer',
                'overlay-bottom' => 'Overlay Bottom',
                'overlay-top' => 'Overlay Top',
                'hidden' => 'Hidden',
            ],
        ]);
        
        $this->add_control('bg_color', [
            'label' => 'Background Color',
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
        $count = $settings['count'] ?: 10;
        
        // Use shortcode which already uses global settings
        if ($settings['use_global'] === 'yes') {
            echo do_shortcode('[nc_events count="' . intval($count) . '"]');
            return;
        }
        
        // Custom rendering with widget-specific settings
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $cutoff = intval(get_option('nc_day_cutoff_hour', 3));
        
        $now = new DateTime('now', $tz);
        if (intval($now->format('G')) < $cutoff) $now->modify('-1 day');
        $now->setTime(0, 0, 0);
        
        $instances = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.post_title, p.ID as post_id 
             FROM $table i 
             JOIN {$wpdb->posts} p ON i.post_id = p.ID 
             WHERE p.post_status = 'publish' AND i.start >= %d 
             ORDER BY i.start ASC LIMIT %d",
            $now->getTimestamp(), $count
        ));
        
        if (empty($instances)) {
            echo '<p style="color:#999;text-align:center;">No upcoming events</p>';
            return;
        }
        
        $format = $settings['flyer_format'] ?: 'portrait';
        $size = $settings['flyer_size'] ?: 'medium';
        $layout = $settings['layout_style'] ?: 'slider';
        $textPos = $settings['text_position'] ?: 'below';
        $bgColor = $settings['bg_color'] ?: '#000000';
        $titleColor = $settings['title_color'] ?: '#FFFFFF';
        $dateColor = $settings['date_color'] ?: '#EAE396';
        
        $sizes = ['small'=>150, 'medium'=>220, 'large'=>300, 'xlarge'=>380];
        $ratios = ['square'=>1, 'portrait'=>1.25, 'story'=>1.78, 'landscape'=>0.5625];
        $w = $sizes[$size] ?? 220;
        $h = $w * ($ratios[$format] ?? 1.25);
        
        $dateFormat = NC_Display_Settings::get_option('date_format');
        
        $id = 'nc-el-' . uniqid();
        ?>
        <style>
        #<?php echo $id; ?> { background: <?php echo $bgColor; ?>; padding: 20px; }
        #<?php echo $id; ?> .nc-container { display: flex; gap: 20px; justify-content: center; flex-wrap: <?php echo $layout === 'grid' ? 'wrap' : 'nowrap'; ?>; overflow-x: <?php echo $layout === 'slider' ? 'auto' : 'visible'; ?>; }
        #<?php echo $id; ?> .nc-item { flex: 0 0 auto; width: <?php echo $w; ?>px; text-decoration: none; }
        #<?php echo $id; ?> .nc-flyer { width: <?php echo $w; ?>px; height: <?php echo $h; ?>px; border-radius: 12px; position: relative; overflow: hidden; background: #222; box-shadow: 0 0 20px <?php echo $dateColor; ?>40; }
        #<?php echo $id; ?> .nc-blur { position: absolute; top: -30px; left: -30px; right: -30px; bottom: -30px; background-size: cover; background-position: center; filter: blur(20px); opacity: 0.7; }
        #<?php echo $id; ?> .nc-flyer img { position: relative; width: 100%; height: 100%; object-fit: contain; }
        #<?php echo $id; ?> .nc-text { padding: 10px; text-align: center; }
        #<?php echo $id; ?> .nc-text.overlay { position: absolute; left: 0; right: 0; bottom: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 25px 10px 10px; }
        #<?php echo $id; ?> .nc-title { color: <?php echo $titleColor; ?>; font-size: 16px; font-weight: bold; }
        #<?php echo $id; ?> .nc-date { color: <?php echo $dateColor; ?>; font-size: 14px; margin-top: 3px; }
        </style>
        
        <div id="<?php echo $id; ?>">
            <div class="nc-container">
                <?php foreach ($instances as $inst): 
                    $flyer = get_post_meta($inst->post_id, '_nc_flyer_1', true);
                    if (!$flyer) $flyer = get_post_thumbnail_id($inst->post_id);
                    $img = $flyer ? wp_get_attachment_image_url($flyer, 'large') : '';
                    $dt = new DateTime('@' . $inst->start);
                    $dt->setTimezone($tz);
                    $link = get_permalink($inst->post_id);
                ?>
                <a href="<?php echo esc_url($link); ?>" class="nc-item">
                    <div class="nc-flyer">
                        <?php if ($img): ?>
                        <div class="nc-blur" style="background-image:url(<?php echo esc_url($img); ?>)"></div>
                        <img src="<?php echo esc_url($img); ?>" alt="">
                        <?php endif; ?>
                        <?php if ($textPos === 'overlay-bottom'): ?>
                        <div class="nc-text overlay">
                            <div class="nc-title"><?php echo esc_html($inst->post_title); ?></div>
                            <div class="nc-date"><?php echo $dt->format($dateFormat); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($textPos === 'below'): ?>
                    <div class="nc-text">
                        <div class="nc-title"><?php echo esc_html($inst->post_title); ?></div>
                        <div class="nc-date"><?php echo $dt->format($dateFormat); ?></div>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
