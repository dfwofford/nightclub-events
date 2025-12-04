<?php
if (!defined('ABSPATH')) exit;

class NC_Shortcodes {
    
    public static function init() {
        add_shortcode('nc_events', array(__CLASS__, 'events_shortcode'));
        add_shortcode('nc_calendar', array(__CLASS__, 'calendar_shortcode'));
        add_shortcode('nc_upcoming', array(__CLASS__, 'upcoming_shortcode'));
    }
    
    public static function opt($key) {
        return NC_Display_Settings::get_option($key);
    }
    
    /**
     * Get upcoming events - unique by post_id, ordered by next occurrence
     */
    public static function get_upcoming_events($count = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $now = new DateTime('now', $tz);
        $now->modify('-6 hours'); // Same as homepage widget
        
        // Get unique events by post_id, ordered by their next occurrence
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT i.post_id, MIN(i.start) as start, p.post_title
            FROM $table i
            JOIN {$wpdb->posts} p ON i.post_id = p.ID
            WHERE p.post_status IN ('publish', 'future')
            AND i.start >= %d
            GROUP BY i.post_id
            ORDER BY start ASC
            LIMIT %d
        ", $now->getTimestamp(), $count));
        
        return $results;
    }
    
    /**
     * Get flyer image URL for an event
     */
    public static function get_flyer_url($post_id, $flyer_num = 1) {
        $flyer_id = get_post_meta($post_id, '_nc_flyer_' . $flyer_num, true);
        if ($flyer_id) {
            return wp_get_attachment_image_url($flyer_id, 'medium');
        }
        if ($flyer_num === 1) {
            // Fall back to featured image
            $thumb_id = get_post_thumbnail_id($post_id);
            if ($thumb_id) {
                return wp_get_attachment_image_url($thumb_id, 'medium');
            }
        }
        return '';
    }
    
    public static function events_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 10), $atts);
        
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $events = self::get_upcoming_events(intval($atts['count']));
        
        if (empty($events)) return '<p style="color:#999;text-align:center;">No upcoming events</p>';
        
        $size = self::opt('flyer_size');
        $blur = self::opt('flyer_blur');
        $blurAmt = self::opt('flyer_blur_amount');
        $radius = self::opt('flyer_radius');
        $glow = self::opt('flyer_glow');
        $glowColor = self::opt('flyer_glow_color');
        $glowSize = self::opt('flyer_glow_size');
        $spacing = self::opt('flyer_spacing');
        $bg = self::opt('flyer_bg');
        $containerBorder = self::opt('flyer_container_border');
        $containerBorderColor = self::opt('flyer_container_border_color');
        $showDate = self::opt('flyer_show_date');
        $showDay = self::opt('flyer_show_day');
        $dateFmt = self::opt('flyer_date_format');
        $dateColor = self::opt('flyer_date_color');
        $dayColor = self::opt('flyer_day_color');
        
        $sizes = array('small' => 150, 'medium' => 180, 'large' => 220);
        $w = $sizes[$size] ?? 180;
        $h = intval($w * 1.22);
        
        $shadow = $glow === '1' ? "0 0 {$glowSize}px {$glowColor}40" : 'none';
        $containerStyle = $containerBorder === '1' ? "border: 2px solid {$containerBorderColor}; border-radius: 12px; padding: 20px;" : '';
        
        $id = 'nc-' . uniqid();
        
        ob_start();
        ?>
        <style>
        #<?php echo $id; ?> { background: <?php echo $bg; ?>; padding: 20px; <?php echo $containerStyle; ?> }
        #<?php echo $id; ?> .nc-wrap { display: flex; gap: <?php echo $spacing; ?>px; overflow-x: auto; padding: 10px 0 20px; scroll-behavior: smooth; }
        #<?php echo $id; ?> .nc-wrap::-webkit-scrollbar { height: 8px; }
        #<?php echo $id; ?> .nc-wrap::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 4px; }
        #<?php echo $id; ?> .nc-wrap::-webkit-scrollbar-thumb { background: <?php echo $containerBorderColor; ?>; border-radius: 4px; }
        #<?php echo $id; ?> .nc-item { flex: 0 0 auto; width: <?php echo $w; ?>px; text-align: center; text-decoration: none; }
        #<?php echo $id; ?> .nc-flyer { 
            width: <?php echo $w; ?>px; 
            height: <?php echo $h; ?>px; 
            border-radius: <?php echo $radius; ?>px; 
            box-shadow: <?php echo $shadow; ?>; 
            position: relative; 
            overflow: hidden; 
            background: #222;
            margin-bottom: 10px;
        }
        #<?php echo $id; ?> .nc-blur {
            position: absolute;
            top: -40px; left: -40px; right: -40px; bottom: -40px;
            background-size: cover;
            background-position: center;
            filter: blur(<?php echo $blurAmt; ?>px);
            opacity: 0.7;
            z-index: 1;
        }
        #<?php echo $id; ?> .nc-flyer img {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            object-fit: <?php echo $blur === '1' ? 'contain' : 'cover'; ?>;
            display: block;
        }
        #<?php echo $id; ?> .nc-day { color: <?php echo $dayColor; ?>; font-size: 14px; font-weight: 700; text-transform: uppercase; }
        #<?php echo $id; ?> .nc-date { color: <?php echo $dateColor; ?>; font-size: 13px; }
        </style>
        <div id="<?php echo $id; ?>"><div class="nc-wrap">
        <?php foreach ($events as $ev): 
            $dt = new DateTime('@' . $ev->start);
            $dt->setTimezone($tz);
            $link = get_permalink($ev->post_id);
            
            // Get flyer 1
            $img1 = self::get_flyer_url($ev->post_id, 1);
            // Get flyer 2 (if exists)
            $img2 = self::get_flyer_url($ev->post_id, 2);
            
            // Show flyer 1
            if ($img1):
        ?>
        <a href="<?php echo esc_url($link); ?>" class="nc-item">
            <div class="nc-flyer">
                <?php if ($blur === '1'): ?>
                <div class="nc-blur" style="background-image: url(<?php echo esc_url($img1); ?>);"></div>
                <?php endif; ?>
                <img src="<?php echo esc_url($img1); ?>" alt="">
            </div>
            <?php if ($showDay === '1'): ?><div class="nc-day"><?php echo esc_html(strtoupper($dt->format('l'))); ?></div><?php endif; ?>
            <?php if ($showDate === '1'): ?><div class="nc-date"><?php echo esc_html($dt->format($dateFmt)); ?></div><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php 
            // Show flyer 2 as separate item if it exists
            if ($img2):
        ?>
        <a href="<?php echo esc_url($link); ?>" class="nc-item">
            <div class="nc-flyer">
                <?php if ($blur === '1'): ?>
                <div class="nc-blur" style="background-image: url(<?php echo esc_url($img2); ?>);"></div>
                <?php endif; ?>
                <img src="<?php echo esc_url($img2); ?>" alt="">
            </div>
            <?php if ($showDay === '1'): ?><div class="nc-day"><?php echo esc_html(strtoupper($dt->format('l'))); ?></div><?php endif; ?>
            <?php if ($showDate === '1'): ?><div class="nc-date"><?php echo esc_html($dt->format($dateFmt)); ?></div><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
        </div></div>
        <?php
        return ob_get_clean();
    }
    
    public static function calendar_shortcode($atts) {
        $bg = self::opt('cal_bg');
        $radius = self::opt('cal_radius');
        $headerBg = self::opt('cal_header_bg');
        $headerText = self::opt('cal_header_text');
        $dayBg = self::opt('cal_day_bg');
        $dayText = self::opt('cal_day_text');
        $todayBg = self::opt('cal_today_bg');
        $todayText = self::opt('cal_today_text');
        $eventBg = self::opt('cal_event_bg');
        $eventText = self::opt('cal_event_text');
        $showTime = self::opt('cal_show_time');
        
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
            "SELECT i.*, p.post_title, p.ID as post_id FROM $table i JOIN {$wpdb->posts} p ON i.post_id = p.ID WHERE p.post_status = 'publish' AND i.start >= %d AND i.start <= %d ORDER BY i.start",
            $month_start, $month_end
        ));
        
        $events_by_day = array();
        foreach ($events as $e) {
            $dt = new DateTime('@' . $e->start);
            $dt->setTimezone($tz);
            $day = intval($dt->format('j'));
            if (!isset($events_by_day[$day])) $events_by_day[$day] = array();
            $e->time = get_post_meta($e->post_id, '_nc_start_time', true);
            $events_by_day[$day][] = $e;
        }
        
        $today = intval(date('j'));
        $current_month = intval(date('n'));
        $current_year = intval(date('Y'));
        
        $prev_month = $month - 1; $prev_year = $year;
        if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
        $next_month = $month + 1; $next_year = $year;
        if ($next_month > 12) { $next_month = 1; $next_year++; }
        
        $id = 'nc-cal-' . uniqid();
        
        ob_start();
        ?>
        <style>
        #<?php echo $id; ?> { background: <?php echo $bg; ?>; padding: 15px; border-radius: <?php echo $radius; ?>px; }
        #<?php echo $id; ?> .cal-header { display: flex; justify-content: space-between; align-items: center; background: <?php echo $headerBg; ?>; padding: 12px; border-radius: 5px; margin-bottom: 10px; }
        #<?php echo $id; ?> .cal-header h2 { margin: 0; color: <?php echo $headerText; ?>; font-size: 18px; }
        #<?php echo $id; ?> .cal-nav { border: 1px solid <?php echo $headerText; ?>; color: <?php echo $headerText; ?>; padding: 6px 12px; border-radius: 4px; text-decoration: none; background: transparent; font-size: 12px; }
        #<?php echo $id; ?> .cal-nav:hover { background: <?php echo $headerText; ?>; color: <?php echo $headerBg; ?>; }
        #<?php echo $id; ?> .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
        #<?php echo $id; ?> .cal-dow { text-align: center; padding: 8px; color: <?php echo $headerText; ?>; font-weight: bold; font-size: 12px; background: <?php echo $headerBg; ?>; }
        #<?php echo $id; ?> .cal-day { background: <?php echo $dayBg; ?>; min-height: 70px; padding: 6px; }
        #<?php echo $id; ?> .cal-day.empty { background: transparent; }
        #<?php echo $id; ?> .cal-day.today { background: <?php echo $todayBg; ?>; }
        #<?php echo $id; ?> .cal-day-num { color: <?php echo $dayText; ?>; font-weight: bold; font-size: 12px; }
        #<?php echo $id; ?> .cal-day.today .cal-day-num { color: <?php echo $todayText; ?>; }
        #<?php echo $id; ?> .cal-event { background: <?php echo $eventBg; ?>; color: <?php echo $eventText; ?>; padding: 2px 4px; border-radius: 3px; font-size: 10px; margin-top: 3px; display: block; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        </style>
        <div id="<?php echo $id; ?>">
            <div class="cal-header">
                <a href="?cal_month=<?php echo $prev_month; ?>&cal_year=<?php echo $prev_year; ?>" class="cal-nav">← Prev</a>
                <h2><?php echo date('F Y', $first_day); ?></h2>
                <a href="?cal_month=<?php echo $next_month; ?>&cal_year=<?php echo $next_year; ?>" class="cal-nav">Next →</a>
            </div>
            <div class="cal-grid">
                <?php foreach (array('S','M','T','W','T','F','S') as $dow): ?><div class="cal-dow"><?php echo $dow; ?></div><?php endforeach; ?>
                <?php for ($i = 0; $i < $start_dow; $i++): ?><div class="cal-day empty"></div><?php endfor; ?>
                <?php for ($d = 1; $d <= $days_in_month; $d++): $is_today = ($d === $today && $month === $current_month && $year === $current_year); ?>
                <div class="cal-day <?php echo $is_today ? 'today' : ''; ?>">
                    <div class="cal-day-num"><?php echo $d; ?></div>
                    <?php if (isset($events_by_day[$d])): foreach (array_slice($events_by_day[$d], 0, 3) as $ev): ?>
                    <a href="<?php echo get_permalink($ev->post_id); ?>" class="cal-event"><?php if ($showTime === '1' && $ev->time): ?><span style="opacity:0.7"><?php echo esc_html($ev->time); ?></span> <?php endif; ?><?php echo esc_html($ev->post_title); ?></a>
                    <?php endforeach; if (count($events_by_day[$d]) > 3) echo '<div style="font-size:9px;color:#999;margin-top:2px">+' . (count($events_by_day[$d]) - 3) . ' more</div>'; endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function upcoming_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $events = self::get_upcoming_events(intval($atts['count']));
        
        $radius = self::opt('upcoming_radius');
        $dayColor = self::opt('flyer_day_color');
        $dateColor = self::opt('flyer_date_color');
        $dateFmt = self::opt('flyer_date_format');
        $containerBorder = self::opt('upcoming_container_border');
        $containerBorderColor = self::opt('upcoming_container_border_color');
        $showDay = self::opt('upcoming_show_day');
        $showTime = self::opt('upcoming_show_time');
        $bg = self::opt('upcoming_bg');
        
        $containerStyle = $containerBorder === '1' ? "border: 2px solid {$containerBorderColor}; border-radius: 12px; padding: 20px;" : '';
        $id = 'nc-up-' . uniqid();
        
        ob_start();
        ?>
        <style>
        #<?php echo $id; ?> { background: <?php echo $bg; ?>; padding: 20px; <?php echo $containerStyle; ?> }
        #<?php echo $id; ?> .nc-scroll { display: flex; gap: 20px; overflow-x: auto; padding: 10px 0 20px; scroll-behavior: smooth; }
        #<?php echo $id; ?> .nc-scroll::-webkit-scrollbar { height: 8px; }
        #<?php echo $id; ?> .nc-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 4px; }
        #<?php echo $id; ?> .nc-scroll::-webkit-scrollbar-thumb { background: <?php echo $containerBorderColor; ?>; border-radius: 4px; }
        #<?php echo $id; ?> .nc-item { flex: 0 0 auto; width: 180px; text-align: center; text-decoration: none; }
        #<?php echo $id; ?> .nc-flyer { 
            width: 180px; 
            height: 220px; 
            border-radius: <?php echo $radius; ?>px; 
            box-shadow: 0 0 20px <?php echo $containerBorderColor; ?>40;
            overflow: hidden; 
            background: #222;
            margin-bottom: 10px;
        }
        #<?php echo $id; ?> .nc-flyer img { width: 100%; height: 100%; object-fit: cover; }
        #<?php echo $id; ?> .nc-day { color: <?php echo $dayColor; ?>; font-size: 14px; font-weight: 700; text-transform: uppercase; }
        #<?php echo $id; ?> .nc-date { color: <?php echo $dateColor; ?>; font-size: 13px; }
        </style>
        <div id="<?php echo $id; ?>"><div class="nc-scroll">
        <?php foreach ($events as $ev): 
            $dt = new DateTime('@' . $ev->start);
            $dt->setTimezone($tz);
            $link = get_permalink($ev->post_id);
            $img = self::get_flyer_url($ev->post_id, 1);
            $time = get_post_meta($ev->post_id, '_nc_start_time', true);
            
            if (!$img) continue; // Skip events without flyers
        ?>
        <a href="<?php echo esc_url($link); ?>" class="nc-item">
            <div class="nc-flyer">
                <img src="<?php echo esc_url($img); ?>" alt="">
            </div>
            <?php if ($showDay === '1'): ?><div class="nc-day"><?php echo esc_html(strtoupper($dt->format('l'))); ?></div><?php endif; ?>
            <div class="nc-date"><?php echo esc_html($dt->format($dateFmt)); ?></div>
            <?php if ($showTime === '1'): ?><div class="nc-time"><?php echo esc_html($time); ?></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
        </div></div>
        <?php
        return ob_get_clean();
    }
}

NC_Shortcodes::init();
