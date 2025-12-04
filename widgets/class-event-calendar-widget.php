<?php
if (!defined('ABSPATH')) exit;

class NC_Event_Calendar_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { return 'nc_event_calendar'; }
    public function get_title() { return 'Event Calendar'; }
    public function get_icon() { return 'eicon-calendar'; }
    public function get_categories() { return ['nightclub-events']; }
    
    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => 'Settings',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
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
        
        // Override controls
        $this->start_controls_section('override_section', [
            'label' => 'Custom Colors',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => ['use_global' => ''],
        ]);
        
        $this->add_control('cal_bg', [
            'label' => 'Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#1a1a1a',
        ]);
        
        $this->add_control('cal_header_bg', [
            'label' => 'Header Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#000000',
        ]);
        
        $this->add_control('cal_header_text', [
            'label' => 'Header Text',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#EAE396',
        ]);
        
        $this->add_control('cal_day_bg', [
            'label' => 'Day Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#222222',
        ]);
        
        $this->add_control('cal_day_text', [
            'label' => 'Day Text',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
        ]);
        
        $this->add_control('cal_today_bg', [
            'label' => 'Today Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#EAE396',
        ]);
        
        $this->add_control('cal_event_bg', [
            'label' => 'Event Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#333333',
        ]);
        
        $this->add_control('cal_event_text', [
            'label' => 'Event Text',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
        ]);
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if ($settings['use_global'] === 'yes') {
            echo do_shortcode('[nc_calendar]');
            return;
        }
        
        // Custom calendar with widget settings
        $bgColor = $settings['cal_bg'] ?: '#1a1a1a';
        $headerBg = $settings['cal_header_bg'] ?: '#000000';
        $headerText = $settings['cal_header_text'] ?: '#EAE396';
        $dayBg = $settings['cal_day_bg'] ?: '#222222';
        $dayText = $settings['cal_day_text'] ?: '#FFFFFF';
        $todayBg = $settings['cal_today_bg'] ?: '#EAE396';
        $todayText = '#000000';
        $eventBg = $settings['cal_event_bg'] ?: '#333333';
        $eventText = $settings['cal_event_text'] ?: '#FFFFFF';
        
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        
        $month = isset($_GET['cal_month']) ? intval($_GET['cal_month']) : intval(date('n'));
        $year = isset($_GET['cal_year']) ? intval($_GET['cal_year']) : intval(date('Y'));
        
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $start_dow = date('w', $first_day);
        
        $month_start = strtotime("$year-$month-01 00:00:00");
        $month_end = strtotime("$year-$month-$days_in_month 23:59:59");
        
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.post_title, p.ID as post_id 
             FROM $table i 
             JOIN {$wpdb->posts} p ON i.post_id = p.ID 
             WHERE p.post_status = 'publish' AND i.start >= %d AND i.start <= %d 
             ORDER BY i.start",
            $month_start, $month_end
        ));
        
        $events_by_day = [];
        foreach ($events as $e) {
            $dt = new DateTime('@' . $e->start);
            $dt->setTimezone($tz);
            $day = intval($dt->format('j'));
            if (!isset($events_by_day[$day])) $events_by_day[$day] = [];
            $events_by_day[$day][] = $e;
        }
        
        $today = intval(date('j'));
        $current_month = intval(date('n'));
        $current_year = intval(date('Y'));
        
        $prev_month = $month - 1; $prev_year = $year;
        if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
        $next_month = $month + 1; $next_year = $year;
        if ($next_month > 12) { $next_month = 1; $next_year++; }
        
        $id = 'nc-cal-el-' . uniqid();
        ?>
        <style>
        #<?php echo $id; ?> { background: <?php echo $bgColor; ?>; padding: 20px; border-radius: 10px; }
        #<?php echo $id; ?> .cal-header { display: flex; justify-content: space-between; align-items: center; background: <?php echo $headerBg; ?>; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        #<?php echo $id; ?> .cal-header h2 { margin: 0; color: <?php echo $headerText; ?>; }
        #<?php echo $id; ?> .cal-nav { border: 1px solid <?php echo $headerText; ?>; color: <?php echo $headerText; ?>; padding: 8px 15px; border-radius: 5px; text-decoration: none; background: transparent; }
        #<?php echo $id; ?> .cal-nav:hover { background: <?php echo $headerText; ?>; color: <?php echo $headerBg; ?>; }
        #<?php echo $id; ?> .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        #<?php echo $id; ?> .cal-dow { text-align: center; padding: 10px; color: <?php echo $headerText; ?>; font-weight: bold; }
        #<?php echo $id; ?> .cal-day { background: <?php echo $dayBg; ?>; min-height: 80px; padding: 8px; border-radius: 5px; }
        #<?php echo $id; ?> .cal-day.today { background: <?php echo $todayBg; ?>; }
        #<?php echo $id; ?> .cal-day-num { color: <?php echo $dayText; ?>; font-weight: bold; }
        #<?php echo $id; ?> .cal-day.today .cal-day-num { color: <?php echo $todayText; ?>; }
        #<?php echo $id; ?> .cal-event { background: <?php echo $eventBg; ?>; color: <?php echo $eventText; ?>; padding: 3px 6px; border-radius: 3px; font-size: 11px; margin-top: 3px; display: block; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        </style>
        
        <div id="<?php echo $id; ?>">
            <div class="cal-header">
                <a href="?cal_month=<?php echo $prev_month; ?>&cal_year=<?php echo $prev_year; ?>" class="cal-nav">← Prev</a>
                <h2><?php echo date('F Y', $first_day); ?></h2>
                <a href="?cal_month=<?php echo $next_month; ?>&cal_year=<?php echo $next_year; ?>" class="cal-nav">Next →</a>
            </div>
            <div class="cal-grid">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow): ?>
                <div class="cal-dow"><?php echo $dow; ?></div>
                <?php endforeach; ?>
                
                <?php for ($i = 0; $i < $start_dow; $i++): ?>
                <div class="cal-day" style="background:transparent"></div>
                <?php endfor; ?>
                
                <?php for ($d = 1; $d <= $days_in_month; $d++): 
                    $is_today = ($d === $today && $month === $current_month && $year === $current_year);
                ?>
                <div class="cal-day <?php echo $is_today ? 'today' : ''; ?>">
                    <div class="cal-day-num"><?php echo $d; ?></div>
                    <?php if (isset($events_by_day[$d])): 
                        foreach (array_slice($events_by_day[$d], 0, 2) as $ev): ?>
                    <a href="<?php echo get_permalink($ev->post_id); ?>" class="cal-event"><?php echo esc_html($ev->post_title); ?></a>
                    <?php endforeach; 
                        if (count($events_by_day[$d]) > 2) echo '<div style="font-size:10px;color:#999">+' . (count($events_by_day[$d]) - 2) . ' more</div>';
                    endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php
    }
}
