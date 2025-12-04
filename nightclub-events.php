<?php
/**
 * Plugin Name: Nightclub Events Calendar
 * Description: Event management for nightclubs with repeat events, flyers, tickets, and archives.
 * Version: 0.5.0-beta
 * Author: Flex Nightclub
 */

if (!defined('ABSPATH')) exit;
// GitHub Auto-Updates
require_once plugin_dir_path(__FILE__) . "includes/plugin-update-checker.php";
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$ncUpdateChecker = PucFactory::buildUpdateChecker(
    "https://github.com/dfwofford/nightclub-events/",
    __FILE__,
    "nightclub-events"
);
$ncUpdateChecker->setBranch("main");

require_once plugin_dir_path(__FILE__) . 'nc-display-settings.php';
require_once plugin_dir_path(__FILE__) . 'nc-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'nc-elementor.php';
define('NC_VERSION', '0.5.0-beta');

class Nightclub_Events {
    
    private static $instance = null;
    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'admin_menus'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_nc_event', array($this, 'save_event'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_filter('use_block_editor_for_post_type', array($this, 'disable_gutenberg'), 10, 2);
        add_filter('manage_nc_event_posts_columns', array($this, 'add_columns'));
        add_action('manage_nc_event_posts_custom_column', array($this, 'column_content'), 10, 2);
        add_action('pre_get_posts', array($this, 'sort_by_date'));
        add_action('nc_daily_cleanup', array($this, 'auto_archive_old_events'));
        if (!wp_next_scheduled('nc_daily_cleanup')) wp_schedule_event(time(), 'daily', 'nc_daily_cleanup');
    }
    
    public function disable_gutenberg($use, $post_type) { return $post_type === 'nc_event' ? false : $use; }
    
    public function register_post_type() {
        register_post_type('nc_event', array(
            'labels' => array('name'=>'Events','singular_name'=>'Event','add_new'=>'Add New Event','edit_item'=>'Edit Event','all_items'=>'All Events'),
            'public' => true, 'has_archive' => true, 'menu_icon' => 'dashicons-calendar-alt', 'menu_position' => 5,
            'supports' => array('title', 'editor'), 'rewrite' => array('slug' => 'event'),
        ));
    }
    
    public function add_columns($cols) {
        return array('cb'=>$cols['cb'],'title'=>$cols['title'],'event_date'=>'Date','event_time'=>'Time','tickets'=>'Tickets','repeat'=>'Repeat');
    }
    
    public function column_content($col, $id) {
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        switch($col) {
            case 'event_date':
                $ts = get_post_meta($id, '_nc_start_timestamp', true);
                if ($ts) { $dt = new DateTime('@'.$ts); $dt->setTimezone($tz); echo '<strong>'.$dt->format('m/d/Y').'</strong><br><small>'.$dt->format('l').'</small>'; }
                break;
            case 'event_time':
                echo get_post_meta($id, '_nc_start_time', true) . ' - ' . get_post_meta($id, '_nc_end_time', true);
                break;
            case 'tickets':
                $type = get_post_meta($id, '_nc_ticket_type', true);
                if ($type) echo '<span style="background:#28a745;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">'.ucfirst($type).'</span>';
                else echo '—';
                break;
            case 'repeat':
                echo get_post_meta($id, '_nc_repeat', true) ? '<span style="background:#9b59b6;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">Weekly</span>' : '—';
                break;
        }
    }
    
    public function sort_by_date($q) {
        if (!is_admin() || !$q->is_main_query() || $q->get('post_type') !== 'nc_event') return;
        $q->set('meta_key', '_nc_start_timestamp'); $q->set('orderby', 'meta_value_num');
        if (!$q->get('order')) $q->set('order', 'DESC');
    }
    
    public function admin_menus() {
        add_submenu_page('edit.php?post_type=nc_event', 'Settings', 'Settings', 'manage_options', 'nc-settings', array($this, 'settings_page'));
        add_submenu_page('edit.php?post_type=nc_event', 'Archive', 'Archive & Cleanup', 'manage_options', 'nc-archive', array($this, 'archive_page'));
        add_submenu_page('edit.php?post_type=nc_event', 'Upcoming', 'Upcoming Instances', 'edit_posts', 'nc-upcoming', array($this, 'upcoming_page'));
    }
    
    public function add_meta_boxes() {
        add_meta_box('nc_flyers', 'Event Flyers', array($this, 'flyers_box'), 'nc_event', 'normal', 'high');
        add_meta_box('nc_details', 'Event Details', array($this, 'details_box'), 'nc_event', 'normal', 'high');
        add_meta_box('nc_tickets', 'Tickets & Guest List', array($this, 'tickets_box'), 'nc_event', 'normal', 'default');
        add_meta_box('nc_cover', 'Cover / Admission', array($this, 'cover_box'), 'nc_event', 'normal', 'default');
        add_meta_box('nc_preview', 'Event Preview', array($this, 'preview_box'), 'nc_event', 'normal', 'low');
    }
    
    public function flyers_box($post) {
        $f1 = get_post_meta($post->ID, '_nc_flyer_1', true);
        $f2 = get_post_meta($post->ID, '_nc_flyer_2', true);
        if (!$f1 && has_post_thumbnail($post->ID)) $f1 = get_post_thumbnail_id($post->ID);
        ?>
        <style>.nc-flyers{display:flex;gap:40px;padding:20px 0}.nc-flyer{flex:1;text-align:center}.nc-flyer h4{margin:0 0 15px}.nc-preview{width:200px;height:200px;margin:0 auto 15px;border:3px dashed #c3c4c7;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f6f7f7;overflow:hidden}.nc-preview img{max-width:100%;max-height:100%;object-fit:cover}.nc-btns{display:flex;gap:10px;justify-content:center}</style>
        <div class="nc-flyers">
            <?php foreach(array(1=>'Primary',2=>'Secondary') as $n=>$label): $f=$n==1?$f1:$f2; ?>
            <div class="nc-flyer">
                <h4><?php echo $label; ?> Flyer</h4>
                <div class="nc-preview" id="fp<?php echo $n; ?>"><?php echo $f?'<img src="'.esc_url(wp_get_attachment_image_url($f,'medium')).'">':'<span>No flyer</span>'; ?></div>
                <input type="hidden" name="nc_flyer_<?php echo $n; ?>" id="fi<?php echo $n; ?>" value="<?php echo esc_attr($f); ?>">
                <div class="nc-btns">
                    <button type="button" class="button button-primary" onclick="ncFlyer(<?php echo $n; ?>)">Select</button>
                    <button type="button" class="button" onclick="ncRmFlyer(<?php echo $n; ?>)" id="fr<?php echo $n; ?>" <?php echo $f?'':'style="display:none"'; ?>>Remove</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <script>
        function ncFlyer(n){var f=wp.media({title:'Select Flyer',multiple:false,library:{type:'image'}});f.on('select',function(){var a=f.state().get('selection').first().toJSON();var u=a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url;document.getElementById('fi'+n).value=a.id;document.getElementById('fp'+n).innerHTML='<img src="'+u+'">';document.getElementById('fr'+n).style.display='inline-block'});f.open()}
        function ncRmFlyer(n){document.getElementById('fi'+n).value='';document.getElementById('fp'+n).innerHTML='<span>No flyer</span>';document.getElementById('fr'+n).style.display='none'}
        </script>
        <?php
    }
    
                        public function details_box($post) {
        wp_nonce_field("nc_save", "nc_nonce");
        $start_date = get_post_meta($post->ID, "_nc_start_date", true) ?: date("m/d/Y");
        $start_time = get_post_meta($post->ID, "_nc_start_time", true) ?: get_option("nc_default_start", "8:00 PM");
        $end_date = get_post_meta($post->ID, "_nc_end_date", true) ?: date("m/d/Y", strtotime("+1 day"));
        $end_time = get_post_meta($post->ID, "_nc_end_time", true) ?: get_option("nc_default_end", "2:00 AM");
        $hide_time = get_post_meta($post->ID, "_nc_hide_time", true);
        $repeat = get_post_meta($post->ID, "_nc_repeat", true);
        $repeat_type = get_post_meta($post->ID, "_nc_repeat_type", true) ?: "weekly";
        $repeat_days = get_post_meta($post->ID, "_nc_repeat_days", true) ?: array();
        $repeat_until = get_post_meta($post->ID, "_nc_repeat_until", true);
        $repeat_forever = get_post_meta($post->ID, "_nc_repeat_forever", true);
        $multiday_type = get_post_meta($post->ID, "_nc_multiday_type", true) ?: "single";
        $day_times = get_post_meta($post->ID, "_nc_day_times", true) ?: array();
        
        $start_dow = date("w", strtotime($start_date));
        $dow_map = array("sun","mon","tue","wed","thu","fri","sat");
        if (empty($repeat_days) || !is_array($repeat_days)) $repeat_days = array($dow_map[$start_dow]);
        
        $time_opts = "";
        for($h=0;$h<24;$h++) foreach(array("00","30") as $m) {
            $t = ($h%12==0?12:$h%12).":".$m." ".($h<12?"AM":"PM");
            $time_opts .= "<option value=\"".esc_attr($t)."\">".esc_html($t)."</option>";
        }
        ?>
        <style>
        .nc-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px}
        .nc-field label{display:block;font-weight:600;margin-bottom:5px}
        .nc-field select,.nc-field input[type=text]{width:100%;padding:8px}
        .nc-box{padding:15px;border-radius:8px;margin-bottom:15px}
        .nc-box.yellow{background:#fff3cd;border:1px solid #ffc107}
        .nc-box.blue{background:#cce5ff;border:1px solid #007bff}
        .nc-box.purple{background:#e8daef;border:1px solid #9b59b6}
        .nc-sub{margin-top:15px;padding:15px;background:rgba(255,255,255,0.5);border-radius:5px}
        .nc-days{display:flex;gap:5px;margin:10px 0}
        .nc-day-btn{width:40px;height:40px;border:2px solid #007bff;border-radius:50%;background:#fff;cursor:pointer;font-weight:bold}
        .nc-day-btn.active{background:#007bff;color:#fff}
        .nc-multiday-schedule{margin-top:15px;border:1px solid #ddd;border-radius:8px;overflow:hidden}
        .nc-day-row{display:grid;grid-template-columns:120px 1fr 1fr 1fr;gap:10px;padding:12px 15px;border-bottom:1px solid #eee;align-items:center}
        .nc-day-row:last-child{border-bottom:none}
        .nc-day-row.header{background:#f5f5f5;font-weight:600}
        .nc-day-row select,.nc-day-row input{padding:6px;font-size:13px}
        .nc-day-row input[type=text]{width:80px}
        </style>
        
        <div class="nc-grid">
            <div class="nc-field"><label>Start Date</label><input type="text" name="nc_start_date" id="nc_start_date" value="<?php echo esc_attr($start_date); ?>" class="nc-datepicker" autocomplete="off"></div>
            <div class="nc-field"><label>Start Time</label><?php echo $this->time_dropdown("nc_start_time", $start_time); ?></div>
            <div class="nc-field"><label>End Date</label><input type="text" name="nc_end_date" id="nc_end_date" value="<?php echo esc_attr($end_date); ?>" class="nc-datepicker" autocomplete="off"></div>
            <div class="nc-field"><label>End Time</label><?php echo $this->time_dropdown("nc_end_time", $end_time); ?></div>
        </div>
        
        <div class="nc-box purple" id="nc_multiday_box" style="display:none;">
            <strong>Multi-Day Event</strong>
            <div style="margin-top:15px;">
                <label style="display:block;margin-bottom:10px;"><input type="radio" name="nc_multiday_type" value="single" <?php checked($multiday_type,"single"); ?> onchange="updateMultiDayUI()"> <strong>Single Listing</strong></label>
                <label style="display:block;"><input type="radio" name="nc_multiday_type" value="separate" <?php checked($multiday_type,"separate"); ?> onchange="updateMultiDayUI()"> <strong>Per-Day Schedule</strong> (times & cover for each day)</label>
            </div>
            <div id="nc_multiday_schedule" style="display:none;margin-top:15px;">
                <div class="nc-multiday-schedule">
                    <div class="nc-day-row header"><div>Day</div><div>Start</div><div>End</div><div>Cover $</div></div>
                    <div id="nc_day_rows"></div>
                </div>
            </div>
            <input type="hidden" name="nc_day_times" id="nc_day_times_input" value="<?php echo esc_attr(is_array($day_times)?json_encode($day_times):'{}'); ?>">
        </div>
        
        <div class="nc-box yellow">
            <label><input type="checkbox" name="nc_hide_time" value="1" <?php checked($hide_time,"1"); ?>> Hide Date/Time</label>
        </div>
        
        <div class="nc-box blue">
            <label><input type="checkbox" name="nc_repeat" id="nc_repeat" value="1" <?php checked($repeat,"1"); ?>> Repeat Event</label>
            <div class="nc-sub" id="nc_repeat_opts" style="<?php echo $repeat?"":"display:none"; ?>">
                <div style="margin-bottom:15px;">
                    <select name="nc_repeat_type" style="padding:8px;">
                        <option value="daily" <?php selected($repeat_type,"daily"); ?>>Daily</option>
                        <option value="weekly" <?php selected($repeat_type,"weekly"); ?>>Weekly</option>
                        <option value="monthly" <?php selected($repeat_type,"monthly"); ?>>Monthly</option>
                    </select>
                    <span> on:</span>
                </div>
                <div class="nc-days">
                    <?php $dl=array("S","M","T","W","T","F","S");$dn=array("sun","mon","tue","wed","thu","fri","sat");
                    foreach($dn as $i=>$d):$a=is_array($repeat_days)&&in_array($d,$repeat_days); ?>
                    <button type="button" class="nc-day-btn <?php echo $a?"active":""; ?>" data-day="<?php echo $d; ?>" onclick="this.classList.toggle('active');updateDays()"><?php echo $dl[$i]; ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="nc_repeat_days" id="nc_repeat_days_input" value="<?php echo esc_attr(is_array($repeat_days)?implode(",",$repeat_days):""); ?>">
                <div style="margin-top:15px;">
                    <label style="display:block;margin-bottom:8px;"><input type="radio" name="nc_repeat_end" value="date" <?php checked(!$repeat_forever); ?>> Until: <input type="text" name="nc_repeat_until" class="nc-datepicker" value="<?php echo esc_attr($repeat_until); ?>" style="width:120px" autocomplete="off"></label>
                    <label><input type="radio" name="nc_repeat_end" value="forever" <?php checked($repeat_forever,"1"); ?>> Indefinitely</label>
                </div>
            </div>
        </div>
        
        <script>
        var timeOpts = '<?php echo $time_opts; ?>';
        var savedTimes = <?php echo is_array($day_times)&&!empty($day_times)?json_encode($day_times):"{}"; ?>;
        var defStart = "<?php echo esc_js($start_time); ?>";
        var defEnd = "<?php echo esc_js($end_time); ?>";
        
        function updateDays(){var d=[];document.querySelectorAll(".nc-day-btn.active").forEach(function(b){d.push(b.dataset.day)});document.getElementById("nc_repeat_days_input").value=d.join(",");}
        
        function updateMultiDayUI(){
            var t=document.querySelector("input[name=nc_multiday_type]:checked");
            document.getElementById("nc_multiday_schedule").style.display=(t&&t.value==="separate")?"block":"none";
            if(t&&t.value==="separate")buildDayRows();
        }
        
        function buildDayRows(){
            var s=new Date(document.getElementById("nc_start_date").value);
            var e=new Date(document.getElementById("nc_end_date").value);
            var days=Math.min(14,Math.max(1,Math.ceil((e-s)/(1000*60*60*24))));
            var c=document.getElementById("nc_day_rows");c.innerHTML="";
            var dn=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
            for(var i=0;i<days;i++){
                var d=new Date(s);d.setDate(d.getDate()+i);
                var ds=(d.getMonth()+1)+"/"+d.getDate();
                var st=savedTimes["day_"+i+"_start"]||defStart;
                var et=savedTimes["day_"+i+"_end"]||defEnd;
                var cv=savedTimes["day_"+i+"_cover"]||"";
                var r=document.createElement("div");r.className="nc-day-row";
                r.innerHTML="<div><strong>Day "+(i+1)+"</strong><br><small>"+dn[d.getDay()]+" "+ds+"</small></div>"+
                    "<div><select name='day_"+i+"_start' onchange='saveTimes()'>"+timeOpts+"</select></div>"+
                    "<div><select name='day_"+i+"_end' onchange='saveTimes()'>"+timeOpts+"</select></div>"+
                    "<div><input type='text' name='day_"+i+"_cover' value='"+cv+"' placeholder='e.g. 10' onchange='saveTimes()'></div>";
                c.appendChild(r);
                setVal(r.querySelector("select[name='day_"+i+"_start']"),st);
                setVal(r.querySelector("select[name='day_"+i+"_end']"),et);
            }
        }
        
        function setVal(sel,v){for(var i=0;i<sel.options.length;i++)if(sel.options[i].value.replace(/\s/g,"").toLowerCase()===v.replace(/\s/g,"").toLowerCase()){sel.selectedIndex=i;break;}}
        function saveTimes(){var d={};document.querySelectorAll("#nc_day_rows select, #nc_day_rows input").forEach(function(s){d[s.name]=s.value});document.getElementById("nc_day_times_input").value=JSON.stringify(d);}
        
        function checkMulti(){
            var s=new Date(document.getElementById("nc_start_date").value);
            var e=new Date(document.getElementById("nc_end_date").value);
            var diff=Math.ceil((e-s)/(1000*60*60*24));
            document.getElementById("nc_multiday_box").style.display=diff>1?"block":"none";
            if(diff>1)updateMultiDayUI();
            if(typeof updateFullPreview==="function")updateFullPreview();
        }
        
        jQuery(function($){
            if($.fn.datepicker)$(".nc-datepicker").datepicker({dateFormat:"m/d/yy",changeMonth:true,changeYear:true});
            $("#nc_start_date,#nc_end_date").on("change",function(){
                if(this.id==="nc_start_date"){var d=new Date($(this).val());d.setDate(d.getDate()+1);$("#nc_end_date").val((d.getMonth()+1)+"/"+d.getDate()+"/"+d.getFullYear());}
                checkMulti();
            });
            $("#nc_repeat").on("change",function(){$("#nc_repeat_opts").toggle(this.checked)});
            checkMulti();
        });
        </script>
        <?php
    }


    public function tickets_box($post) {
        $ticket_url = get_post_meta($post->ID, "_nc_ticket_url", true);
        $guestlist_url = get_post_meta($post->ID, "_nc_guestlist_url", true);
        $combo_url = get_post_meta($post->ID, "_nc_combo_url", true);
        $enable_ticket = get_post_meta($post->ID, "_nc_enable_ticket", true);
        $enable_guestlist = get_post_meta($post->ID, "_nc_enable_guestlist", true);
        $enable_combo = get_post_meta($post->ID, "_nc_enable_combo", true);
        ?>
        <style>.nc-link-box{margin:10px 0;padding:15px;background:#f9f9f9;border-radius:8px;border:1px solid #ddd}.nc-link-box.active{background:#e8f5e9;border-color:#4caf50}.nc-link-box label.main{font-weight:600;font-size:14px;cursor:pointer}.nc-link-box input[type=text]{width:100%;padding:8px;margin-top:10px;border:1px solid #ccc;border-radius:4px}</style>
        
        <p class="description">Enable any links you want to show for this event:</p>
        
        <div class="nc-link-box <?php echo $enable_ticket?"active":""; ?>" id="ticket_box">
            <label class="main"><input type="checkbox" name="nc_enable_ticket" value="1" <?php checked($enable_ticket,"1"); ?> onchange="this.closest(.nc-link-box).classList.toggle(active,this.checked)"> <?php echo esc_html(get_option("nc_label_tickets_btn", "Ticket Link")); ?></label>
            <input type="text" name="nc_ticket_url" value="<?php echo esc_attr($ticket_url); ?>" placeholder="https://...">
        </div>
        
        <div class="nc-link-box <?php echo $enable_guestlist?"active":""; ?>" id="guestlist_box">
            <label class="main"><input type="checkbox" name="nc_enable_guestlist" value="1" <?php checked($enable_guestlist,"1"); ?> onchange="this.closest(.nc-link-box).classList.toggle(active,this.checked)"> <?php echo esc_html(get_option("nc_label_guestlist_btn", "Guest List Link")); ?></label>
            <input type="text" name="nc_guestlist_url" value="<?php echo esc_attr($guestlist_url); ?>" placeholder="https://...">
        </div>
        
        <div class="nc-link-box <?php echo $enable_combo?"active":""; ?>" id="combo_box">
            <label class="main"><input type="checkbox" name="nc_enable_combo" value="1" <?php checked($enable_combo,"1"); ?> onchange="this.closest(.nc-link-box).classList.toggle(active,this.checked)"> <?php echo esc_html(get_option("nc_label_combo_btn", "Guest List / Tickets Link")); ?></label>
            <input type="text" name="nc_combo_url" value="<?php echo esc_attr($combo_url); ?>" placeholder="https://...">
        </div>
        <?php
    }


        public function cover_box($post) {
        $cover_type = get_post_meta($post->ID, "_nc_cover_type", true) ?: "none";
        $cover_single = get_post_meta($post->ID, "_nc_cover_single", true);
        $cover_presale = get_post_meta($post->ID, "_nc_cover_presale", true);
        $cover_door = get_post_meta($post->ID, "_nc_cover_door", true);
        $cover_under21 = get_post_meta($post->ID, "_nc_cover_under21", true);
        $cover_before = get_post_meta($post->ID, "_nc_cover_before", true);
        $cover_before_time = get_post_meta($post->ID, "_nc_cover_before_time", true) ?: "11:00 PM";
        $cover_after = get_post_meta($post->ID, "_nc_cover_after", true);
        
        $lbl_cover = esc_attr(get_option("nc_label_cover", "Cover"));
        $lbl_presale = esc_attr(get_option("nc_label_presale", "Presale"));
        $lbl_door = esc_attr(get_option("nc_label_door", "Door"));
        $lbl_under21 = esc_attr(get_option("nc_label_under21", "Under 21"));
        $lbl_before = esc_attr(get_option("nc_label_before", "Before"));
        $lbl_after = esc_attr(get_option("nc_label_after", "After"));
        ?>
        <style>
        .nc-cover-section{margin:15px 0;padding:15px;background:#f9f9f9;border-radius:8px}
        .nc-cover-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-top:15px}
        .nc-cover-item{display:flex;align-items:center;gap:8px}
        .nc-cover-item label{min-width:120px;font-weight:500}
        .nc-cover-item input[type=text]{width:80px;padding:6px;text-align:center}
        .nc-preview-box{margin-top:20px;padding:20px;background:#1a1a1a;border-radius:10px;color:#fff}
        .nc-preview-title{color:#eae396;font-size:12px;margin-bottom:10px;text-transform:uppercase}
        .nc-preview-cover{font-size:16px;color:#fff}
        .nc-preview-cover span{margin:0 8px}
        .nc-preview-cover .sep{color:#eae396}
        </style>
        
        <div class="nc-ticket-row">
            <label>Cover Type:</label><br>
            <select name="nc_cover_type" id="nc_cover_type" style="margin-top:5px;padding:8px;">
                <option value="none" <?php selected($cover_type,"none"); ?>>No Cover</option>
                <option value="single" <?php selected($cover_type,"single"); ?>>Single Price</option>
                <option value="multi" <?php selected($cover_type,"multi"); ?>>Multiple Prices</option>
            </select>
        </div>
        
        <div id="nc_cover_single_wrap" class="nc-cover-section" style="<?php echo $cover_type=="single"?"":"display:none"; ?>">
            <div class="nc-cover-item">
                <label><?php echo $lbl_cover; ?>: $</label>
                <input type="text" name="nc_cover_single" id="nc_cover_single" value="<?php echo esc_attr($cover_single); ?>" oninput="updateCoverPreview()">
            </div>
        </div>
        
        <div id="nc_cover_multi_wrap" class="nc-cover-section" style="<?php echo $cover_type=="multi"?"":"display:none"; ?>">
            <p><strong>Fill in prices you want to show (leave blank to hide):</strong></p>
            <div class="nc-cover-grid">
                <div class="nc-cover-item">
                    <label><input type="checkbox" id="chk_presale" <?php checked(!empty($cover_presale)); ?> onchange="updateCoverPreview()"> <?php echo $lbl_presale; ?>: $</label>
                    <input type="text" name="nc_cover_presale" id="nc_cover_presale" value="<?php echo esc_attr($cover_presale); ?>" oninput="updateCoverPreview()">
                </div>
                <div class="nc-cover-item">
                    <label><input type="checkbox" id="chk_door" <?php checked(!empty($cover_door)); ?> onchange="updateCoverPreview()"> <?php echo $lbl_door; ?>: $</label>
                    <input type="text" name="nc_cover_door" id="nc_cover_door" value="<?php echo esc_attr($cover_door); ?>" oninput="updateCoverPreview()">
                </div>
                <div class="nc-cover-item">
                    <label><input type="checkbox" id="chk_before" <?php checked(!empty($cover_before)); ?> onchange="updateCoverPreview()"> <?php echo $lbl_before; ?></label>
                    <?php echo $this->time_dropdown("nc_cover_before_time", $cover_before_time, "width:100px;padding:4px;", "onchange=\"updateCoverPreview()\""); ?>
                    <span>$</span>
                    <input type="text" name="nc_cover_before" id="nc_cover_before" value="<?php echo esc_attr($cover_before); ?>" oninput="updateCoverPreview()">
                </div>
                <div class="nc-cover-item">
                    <label><input type="checkbox" id="chk_after" <?php checked(!empty($cover_after)); ?> onchange="updateCoverPreview()"> <?php echo $lbl_after; ?>: $</label>
                    <input type="text" name="nc_cover_after" id="nc_cover_after" value="<?php echo esc_attr($cover_after); ?>" oninput="updateCoverPreview()">
                </div>
                <div class="nc-cover-item">
                    <label><input type="checkbox" id="chk_under21" <?php checked(!empty($cover_under21)); ?> onchange="updateCoverPreview()"> <?php echo $lbl_under21; ?>: $</label>
                    <input type="text" name="nc_cover_under21" id="nc_cover_under21" value="<?php echo esc_attr($cover_under21); ?>" oninput="updateCoverPreview()">
                </div>
            </div>
        </div>
        
        <div class="nc-preview-box" id="cover_preview_box" style="<?php echo $cover_type=="none"?"display:none":""; ?>">
            <div class="nc-preview-title">Cover Preview</div>
            <div class="nc-preview-cover" id="cover_preview_text"></div>
        </div>
        
        <script>
        var lbls = {cover:"<?php echo $lbl_cover; ?>",presale:"<?php echo $lbl_presale; ?>",door:"<?php echo $lbl_door; ?>",under21:"<?php echo $lbl_under21; ?>",before:"<?php echo $lbl_before; ?>",after:"<?php echo $lbl_after; ?>"};
        
        function updateCoverPreview() {
            var type = document.getElementById("nc_cover_type").value;
            var preview = document.getElementById("cover_preview_text");
            var box = document.getElementById("cover_preview_box");
            
            if (type === "none") { box.style.display = "none"; return; }
            box.style.display = "block";
            
            if (type === "single") {
                var val = document.getElementById("nc_cover_single").value;
                if (!val) { preview.innerHTML = ''; return; } preview.innerHTML = lbls.cover + ": $" + val;
                return;
            }
            
            var parts = [];
            var presale = document.getElementById("nc_cover_presale").value;
            var door = document.getElementById("nc_cover_door").value;
            var before = document.getElementById("nc_cover_before").value;
            var beforeTime = document.querySelector("[name=nc_cover_before_time]").value;
            var after = document.getElementById("nc_cover_after").value;
            var under21 = document.getElementById("nc_cover_under21").value;
            
            if (presale && document.getElementById("chk_presale").checked) parts.push("$" + presale + " " + lbls.presale);
            if (door && document.getElementById("chk_door").checked) parts.push("$" + door + " " + lbls.door);
            if (before && document.getElementById("chk_before").checked) parts.push("$" + before + " " + lbls.before + " " + beforeTime);
            if (after && document.getElementById("chk_after").checked) parts.push("$" + after + " " + lbls.after);
            if (under21 && document.getElementById("chk_under21").checked) parts.push("$" + under21 + " " + lbls.under21);
            
            if (parts.length === 0) {
                preview.innerHTML = '';
            } else {
                preview.innerHTML = lbls.cover + ": " + parts.join(" <span class=\"sep\">|</span> ");
            }
        }
        
        jQuery(function($){
            $("#nc_cover_type").on("change", function(){
                var v = $(this).val();
                $("#nc_cover_single_wrap").toggle(v==="single");
                $("#nc_cover_multi_wrap").toggle(v==="multi");
                updateCoverPreview();
            });
            updateCoverPreview();
        });
        </script>
        <?php
    }


    public function time_dropdown($name, $sel, $style='', $extra='') {
        $html = '<select name="'.esc_attr($name).'"'.($style?' style="'.$style.'"':'').($extra?' '.$extra:'').'>';
        for($h=0;$h<24;$h++) foreach(array('00','30') as $m) {
            $t = ($h%12==0?12:$h%12).':'.$m.' '.($h<12?'AM':'PM');
            $html .= '<option value="'.esc_attr($t).'"'.(strcasecmp(str_replace(' ','',$t),str_replace(' ','',$sel))==0?' selected':'').'>'.esc_html($t).'</option>';
        }
        return $html.'</select>';
    }
    
            public function preview_box($post) {
        ?>
        <style>
        .nc-full-preview{background:#1a1a1a;border-radius:15px;padding:30px;color:#fff;font-family:sans-serif}
        .nc-fp-flyers{display:flex;gap:20px;justify-content:center;margin-bottom:20px}
        .nc-fp-flyer{width:200px;height:250px;background:#333;border-radius:10px;overflow:hidden;box-shadow:0 0 20px rgba(234,227,150,0.3)}
        .nc-fp-flyer img{width:100%;height:100%;object-fit:cover}
        .nc-fp-title{font-size:24px;font-weight:bold;text-align:center;margin-bottom:10px;color:#fff}
        .nc-fp-datetime{text-align:center;color:#eae396;font-size:16px;margin-bottom:10px}
        .nc-fp-cover{text-align:center;font-size:14px;margin-bottom:15px;color:#ccc}
        .nc-fp-cover .sep{color:#eae396;margin:0 8px}
        .nc-fp-desc{text-align:center;color:#aaa;font-size:14px;margin-bottom:20px;max-height:100px;overflow:hidden}
        .nc-fp-buttons{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .nc-fp-btn{padding:10px 25px;border-radius:25px;text-decoration:none;font-weight:bold;font-size:14px;display:inline-block}
        .nc-fp-btn.ticket{background:#eae396;color:#000}
        .nc-fp-btn.guestlist{background:transparent;border:2px solid #eae396;color:#eae396}
        </style>
        
        <div class="nc-full-preview" id="nc_full_preview">
            <div class="nc-fp-flyers" id="fp_flyers">
                <div class="nc-fp-flyer"><span style="color:#666;display:flex;height:100%;align-items:center;justify-content:center">No Flyer</span></div>
            </div>
            <div class="nc-fp-title" id="fp_title">Event Title</div>
            <div class="nc-fp-datetime" id="fp_datetime"></div>
            <div class="nc-fp-cover" id="fp_cover"></div>
            <div class="nc-fp-desc" id="fp_desc"></div>
            <div class="nc-fp-buttons" id="fp_buttons"></div>
        </div>
        
        <script>
        function updateFullPreview() {
            // Title
            var titleEl = document.getElementById("title");
            var title = titleEl ? titleEl.value : "Event Title";
            document.getElementById("fp_title").textContent = title || "Event Title";
            
            // Flyers
            var flyersDiv = document.getElementById("fp_flyers");
            var flyer1src = null, flyer2src = null;
            var fp1 = document.getElementById("fp1");
            var fp2 = document.getElementById("fp2");
            if (fp1) { var img1 = fp1.querySelector("img"); if (img1) flyer1src = img1.src; }
            if (fp2) { var img2 = fp2.querySelector("img"); if (img2) flyer2src = img2.src; }
            
            var flyerHtml = "";
            if (flyer1src) {
                flyerHtml += "<div class=\"nc-fp-flyer\"><img src=\"" + flyer1src + "\"></div>";
            } else {
                flyerHtml += "<div class=\"nc-fp-flyer\"><span style=\"color:#666;display:flex;height:100%;align-items:center;justify-content:center\">No Flyer</span></div>";
            }
            if (flyer2src && flyer2src !== flyer1src) {
                flyerHtml += "<div class=\"nc-fp-flyer\"><img src=\"" + flyer2src + "\"></div>";
            }
            flyersDiv.innerHTML = flyerHtml;
            
            // Date/Time
            var startDate = document.querySelector("[name=nc_start_date]");
            var endDate = document.querySelector("[name=nc_end_date]");
            var startTime = document.querySelector("[name=nc_start_time]");
            var endTime = document.querySelector("[name=nc_end_time]");
            var hideTime = document.querySelector("[name=nc_hide_time]");
            
            var dtEl = document.getElementById("fp_datetime");
            if (hideTime && hideTime.checked) {
                dtEl.style.display = "none";
            } else {
                dtEl.style.display = "block";
                var sDate = startDate ? startDate.value : "";
                var eDate = endDate ? endDate.value : "";
                var sTime = startTime ? startTime.value : "";
                var eTime = endTime ? endTime.value : "";
                
                // Check if multi-day (more than 1 day difference)
                var start = new Date(sDate);
                var end = new Date(eDate);
                var diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                
                if (diffDays > 1) {
                    dtEl.textContent = sDate + " - " + eDate + " | " + sTime + " - " + eTime;
                } else {
                    dtEl.textContent = sDate + " | " + sTime + " - " + eTime;
                }
            }
            
            // Cover
            var coverPreview = document.getElementById("cover_preview_text");
            var fpCover = document.getElementById("fp_cover");
            if (coverPreview && coverPreview.innerHTML) {
                fpCover.innerHTML = coverPreview.innerHTML;
                fpCover.style.display = "block";
            } else {
                fpCover.style.display = "none";
            }
            
            // Description - get from WordPress editor
            var desc = "";
            if (typeof tinymce !== "undefined" && tinymce.get("content")) {
                desc = tinymce.get("content").getContent({format: "text"}).substring(0, 200);
            } else {
                var contentEl = document.getElementById("content");
                if (contentEl) desc = contentEl.value.substring(0, 200);
            }
            var fpDesc = document.getElementById("fp_desc");
            if (desc) {
                fpDesc.textContent = desc + (desc.length >= 200 ? "..." : "");
                fpDesc.style.display = "block";
            } else {
                fpDesc.style.display = "none";
            }
            
            // Buttons
            var btns = "";
            var ticketEnabled = document.querySelector("[name=nc_enable_ticket]");
            var guestEnabled = document.querySelector("[name=nc_enable_guestlist]");
            var comboEnabled = document.querySelector("[name=nc_enable_combo]");
            
            if (ticketEnabled && ticketEnabled.checked) btns += "<a class=\"nc-fp-btn ticket\" href=\"#\">Get Tickets</a>";
            if (guestEnabled && guestEnabled.checked) btns += "<a class=\"nc-fp-btn guestlist\" href=\"#\">Guest List</a>";
            if (comboEnabled && comboEnabled.checked) btns += "<a class=\"nc-fp-btn ticket\" href=\"#\">Tickets / Guest List</a>";
            
            document.getElementById("fp_buttons").innerHTML = btns;
        }
        
        jQuery(function($){
            // Watch for changes
            $(document).on("input change", "#title, [name=nc_start_date], [name=nc_end_date], [name=nc_start_time], [name=nc_end_time], [name=nc_hide_time], [name=nc_enable_ticket], [name=nc_enable_guestlist], [name=nc_enable_combo]", updateFullPreview);
            
            // Watch TinyMCE
            if (typeof tinymce !== "undefined") {
                tinymce.on("AddEditor", function(e){
                    e.editor.on("keyup change", updateFullPreview);
                });
            }
            
            // Watch flyer changes with MutationObserver
            setTimeout(function(){
                var observer = new MutationObserver(updateFullPreview);
                var fp1 = document.getElementById("fp1");
                var fp2 = document.getElementById("fp2");
                if(fp1) observer.observe(fp1, {childList:true, subtree:true});
                if(fp2) observer.observe(fp2, {childList:true, subtree:true});
                updateFullPreview();
            }, 1000);
            
            // Also update on cover changes
            $(document).on("input change", "[name^=nc_cover]", function(){
                setTimeout(updateFullPreview, 100);
            });
        });
        </script>
        <?php
    }


    public function admin_scripts($hook) {
        global $post_type;
        if ($post_type !== 'nc_event') return;
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
        echo '<script>jQuery(function($){if($.fn.datepicker)$(".nc-datepicker").datepicker({dateFormat:"m/d/yy"})});</script>';
    }
    
    public function save_event($post_id) {
        if (!isset($_POST['nc_nonce']) || !wp_verify_nonce($_POST['nc_nonce'], 'nc_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        // Basic fields
        $fields = array('nc_start_date'=>'_nc_start_date','nc_start_time'=>'_nc_start_time','nc_end_date'=>'_nc_end_date','nc_end_time'=>'_nc_end_time',
            'nc_flyer_1'=>'_nc_flyer_1','nc_flyer_2'=>'_nc_flyer_2','nc_repeat_until'=>'_nc_repeat_until','nc_multiday_type'=>'_nc_multiday_type','nc_day_times'=>'_nc_day_times',
            'nc_ticket_type'=>'_nc_ticket_type','nc_ticket_url'=>'_nc_ticket_url','nc_guestlist_url'=>'_nc_guestlist_url','nc_combo_url'=>'_nc_combo_url','nc_enable_ticket'=>'_nc_enable_ticket','nc_enable_guestlist'=>'_nc_enable_guestlist','nc_enable_combo'=>'_nc_enable_combo','nc_is_multiday'=>'_nc_is_multiday','nc_repeat_type'=>'_nc_repeat_type','nc_repeat_days'=>'_nc_repeat_days','nc_repeat_until'=>'_nc_repeat_until','nc_multiday_type'=>'_nc_multiday_type','nc_day_times'=>'_nc_day_times',
            'nc_cover_type'=>'_nc_cover_type','nc_cover_single'=>'_nc_cover_single','nc_cover_presale'=>'_nc_cover_presale',
            'nc_cover_door'=>'_nc_cover_door','nc_cover_under21'=>'_nc_cover_under21','nc_cover_before'=>'_nc_cover_before',
            'nc_cover_after'=>'_nc_cover_after','nc_cover_before_time'=>'_nc_cover_before_time');
        foreach($fields as $k=>$m) if(isset($_POST[$k])) update_post_meta($post_id,$m,sanitize_text_field($_POST[$k]));
        
        update_post_meta($post_id, '_nc_hide_time', isset($_POST['nc_hide_time'])?'1':'0');
        update_post_meta($post_id, '_nc_repeat', isset($_POST['nc_repeat'])?'1':'0');
        update_post_meta($post_id, '_nc_repeat_forever', (isset($_POST['nc_repeat_end']) && $_POST['nc_repeat_end']==='forever')?'1':'0');
        
        // Timestamp
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $dt = DateTime::createFromFormat('m/d/Y g:i A', $_POST['nc_start_date'].' '.$_POST['nc_start_time'], $tz);
        if ($dt) update_post_meta($post_id, '_nc_start_timestamp', $dt->getTimestamp());
        
        $this->generate_instances($post_id);
    }
    
    public function generate_instances($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'nc_event_instances';
        $wpdb->delete($table, array('post_id'=>$post_id));
        
        $tz = new DateTimeZone(get_option('nc_timezone', 'America/New_York'));
        $start_date = get_post_meta($post_id, '_nc_start_date', true);
        $start_time = get_post_meta($post_id, '_nc_start_time', true);
        $end_date = get_post_meta($post_id, '_nc_end_date', true);
        $end_time = get_post_meta($post_id, '_nc_end_time', true);
        $repeat = get_post_meta($post_id, '_nc_repeat', true);
        $repeat_until = get_post_meta($post_id, '_nc_repeat_until', true);
        
        $start_dt = DateTime::createFromFormat('m/d/Y g:i A', $start_date.' '.$start_time, $tz);
        $end_dt = DateTime::createFromFormat('m/d/Y g:i A', $end_date.' '.$end_time, $tz);
        if (!$start_dt || !$end_dt) return;
        
        $start_ts = $start_dt->getTimestamp();
        $end_ts = $end_dt->getTimestamp();
        $duration = $end_ts - $start_ts;
        
        // Multi-day event
        $days = max(1, ceil(($end_ts - $start_ts) / 86400));
        if ($days > 1 && !$repeat) {
            for ($d = 0; $d < $days; $d++) {
                $day_start = $start_ts + ($d * 86400);
                $wpdb->insert($table, array('post_id'=>$post_id, 'start'=>$day_start, 'end'=>$day_start + 86400));
            }
        } elseif ($repeat) {
            $until = $repeat_until ? strtotime($repeat_until.' 23:59:59') : strtotime('+6 months');
            $cur = $start_ts;
            $n = 0;
            while ($cur <= $until && $n < 100) {
                $wpdb->insert($table, array('post_id'=>$post_id, 'start'=>$cur, 'end'=>$cur+$duration));
                $cur += 604800;
                $n++;
            }
        } else {
            $wpdb->insert($table, array('post_id'=>$post_id, 'start'=>$start_ts, 'end'=>$end_ts));
        }
    }
    
    public function settings_page() {
        if (isset($_POST['nc_save']) && wp_verify_nonce($_POST['_wpnonce'], 'nc_settings')) {
            $opts = array('nc_default_start','nc_default_end','nc_timezone','nc_day_cutoff_hour','nc_auto_archive_days','nc_events_count',
                'nc_flyer_glow_color','nc_date_color','nc_day_color','nc_title_color','nc_bg_color','nc_button_color','nc_button_text_color',
                'nc_label_upcoming','nc_label_hide_time','nc_label_repeat','nc_label_cover','nc_label_presale','nc_label_door',
                'nc_label_under21','nc_label_before','nc_label_after','nc_label_ticket_url','nc_label_guestlist_url',
                'nc_label_tickets_btn','nc_label_guestlist_btn','nc_label_combo_btn');
            foreach($opts as $o) if(isset($_POST[$o])) update_option($o, sanitize_text_field($_POST[$o]));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $timezones = array('America/New_York'=>'Eastern','America/Chicago'=>'Central','America/Denver'=>'Mountain','America/Los_Angeles'=>'Pacific');
        ?>
        <div class="wrap">
            <h1>Event Settings <small style="color:#666">v<?php echo NC_VERSION; ?></small></h1>
            <form method="post"><?php wp_nonce_field('nc_settings'); ?>
                
                <h2>Time Settings</h2>
                <table class="form-table">
                    <tr><th>Timezone</th><td><select name="nc_timezone"><?php foreach($timezones as $tz=>$l): ?><option value="<?php echo $tz; ?>" <?php selected(get_option('nc_timezone','America/New_York'),$tz); ?>><?php echo $l; ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>Default Start</th><td><?php echo $this->time_dropdown('nc_default_start', get_option('nc_default_start','8:00 PM')); ?></td></tr>
                    <tr><th>Default End</th><td><?php echo $this->time_dropdown('nc_default_end', get_option('nc_default_end','2:00 AM')); ?></td></tr>
                    <tr><th>Day Changes At</th><td><select name="nc_day_cutoff_hour"><?php for($h=1;$h<=6;$h++): ?><option value="<?php echo $h; ?>" <?php selected(get_option('nc_day_cutoff_hour',3),$h); ?>><?php echo $h; ?>:00 AM</option><?php endfor; ?></select></td></tr>
                </table>
                
                <h2>Auto-Archive</h2>
                <table class="form-table">
                    <tr><th>Archive After</th><td><select name="nc_auto_archive_days">
                        <?php foreach(array(0=>'Disabled',7=>'1 Week',14=>'2 Weeks',30=>'1 Month',60=>'2 Months',90=>'3 Months',180=>'6 Months',365=>'1 Year') as $v=>$l): ?>
                        <option value="<?php echo $v; ?>" <?php selected(get_option('nc_auto_archive_days',30),$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?></select></td></tr>
                </table>
                
                <h2>Display Colors</h2>
                <table class="form-table">
                    <tr><th>Events Count</th><td><input type="number" name="nc_events_count" value="<?php echo esc_attr(get_option('nc_events_count',10)); ?>" min="1" max="50" style="width:80px"></td></tr>
                    <tr><th>Flyer Glow</th><td><input type="color" name="nc_flyer_glow_color" value="<?php echo esc_attr(get_option('nc_flyer_glow_color','#EAE396')); ?>"></td></tr>
                    <tr><th>Date Color</th><td><input type="color" name="nc_date_color" value="<?php echo esc_attr(get_option('nc_date_color','#EAE396')); ?>"></td></tr>
                    <tr><th>Day Color</th><td><input type="color" name="nc_day_color" value="<?php echo esc_attr(get_option('nc_day_color','#EAE396')); ?>"></td></tr>
                    <tr><th>Title Color</th><td><input type="color" name="nc_title_color" value="<?php echo esc_attr(get_option('nc_title_color','#FFFFFF')); ?>"></td></tr>
                    <tr><th>Background</th><td><input type="color" name="nc_bg_color" value="<?php echo esc_attr(get_option('nc_bg_color','#000000')); ?>"></td></tr>
                    <tr><th>Button Color</th><td><input type="color" name="nc_button_color" value="<?php echo esc_attr(get_option('nc_button_color','#EAE396')); ?>"></td></tr>
                    <tr><th>Button Text</th><td><input type="color" name="nc_button_text_color" value="<?php echo esc_attr(get_option('nc_button_text_color','#000000')); ?>"></td></tr>
                </table>
                
                <h2>Custom Labels</h2>
                <table class="form-table">
                    <tr><th>Section Title</th><td><input type="text" name="nc_label_upcoming" value="<?php echo esc_attr(get_option('nc_label_upcoming','UPCOMING EVENTS')); ?>" style="width:300px"></td></tr>
                    <tr><th>Hide Time</th><td><input type="text" name="nc_label_hide_time" value="<?php echo esc_attr(get_option('nc_label_hide_time','Hide Date/Time on Website')); ?>" style="width:300px"></td></tr>
                    <tr><th>Repeat</th><td><input type="text" name="nc_label_repeat" value="<?php echo esc_attr(get_option('nc_label_repeat','Repeat Weekly')); ?>" style="width:300px"></td></tr>
                </table>
                
                <h2>Cover Labels</h2>
                <table class="form-table">
                    <tr><th>Cover</th><td><input type="text" name="nc_label_cover" value="<?php echo esc_attr(get_option('nc_label_cover','Cover')); ?>"></td></tr>
                    <tr><th>Presale</th><td><input type="text" name="nc_label_presale" value="<?php echo esc_attr(get_option('nc_label_presale','Presale')); ?>"></td></tr>
                    <tr><th>Door</th><td><input type="text" name="nc_label_door" value="<?php echo esc_attr(get_option('nc_label_door','Door')); ?>"></td></tr>
                    <tr><th>Under 21</th><td><input type="text" name="nc_label_under21" value="<?php echo esc_attr(get_option('nc_label_under21','Under 21')); ?>"></td></tr>
                    <tr><th>Before Time</th><td><input type="text" name="nc_label_before" value="<?php echo esc_attr(get_option('nc_label_before','Before')); ?>"></td></tr>
                    <tr><th>After Midnight</th><td><input type="text" name="nc_label_after" value="<?php echo esc_attr(get_option('nc_label_after','After Midnight')); ?>"></td></tr>
                </table>
                
                <h2>Ticket Labels</h2>
                <table class="form-table">
                    <tr><th>Ticket URL Label</th><td><input type="text" name="nc_label_ticket_url" value="<?php echo esc_attr(get_option('nc_label_ticket_url','Ticket URL')); ?>"></td></tr>
                    <tr><th>Guest List URL Label</th><td><input type="text" name="nc_label_guestlist_url" value="<?php echo esc_attr(get_option('nc_label_guestlist_url','Guest List URL')); ?>"></td></tr>
                    <tr><th>Tickets Button</th><td><input type="text" name="nc_label_tickets_btn" value="<?php echo esc_attr(get_option('nc_label_tickets_btn','Get Tickets')); ?>"></td></tr>
                    <tr><th>Guest List Button</th><td><input type="text" name="nc_label_guestlist_btn" value="<?php echo esc_attr(get_option('nc_label_guestlist_btn','Join Guest List')); ?>"></td></tr>
                    <tr><th>Combo Button</th><td><input type="text" name="nc_label_combo_btn" value="<?php echo esc_attr(get_option('nc_label_combo_btn','Guest List / Tickets Link')); ?>"></td></tr>
                </table>
                
                <p class="submit"><input type="submit" name="nc_save" class="button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }
    
    public function archive_page() {
        global $wpdb;
        $msg = '';
        
        if (isset($_POST['nc_archive_action']) && wp_verify_nonce($_POST['_wpnonce'],'nc_archive')) {
            $action = $_POST['nc_archive_action'];
            if ($action === 'archive_before' && !empty($_POST['archive_before_date'])) {
                $ts = strtotime($_POST['archive_before_date'].' 23:59:59');
                $ids = $wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_nc_start_timestamp' WHERE p.post_type='nc_event' AND p.post_status='publish' AND pm.meta_value<%d",$ts));
                foreach($ids as $id) wp_update_post(array('ID'=>$id,'post_status'=>'nc_archived'));
                $msg = 'Archived '.count($ids).' events';
            } elseif ($action === 'delete_archived') {
                $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='nc_event' AND post_status='nc_archived'");
                foreach($ids as $id) { 
                    $f1=get_post_meta($id,'_nc_flyer_1',true); $f2=get_post_meta($id,'_nc_flyer_2',true);
                    if($f1)wp_delete_attachment($f1,true); if($f2)wp_delete_attachment($f2,true);
                    wp_delete_post($id,true); 
                }
                $msg = 'Deleted '.count($ids).' events and images';
            } elseif ($action === 'restore_all') {
                $wpdb->update($wpdb->posts,array('post_status'=>'publish'),array('post_type'=>'nc_event','post_status'=>'nc_archived'));
                $msg = 'All restored';
            }
        }
        if (isset($_GET['restore'])) { wp_update_post(array('ID'=>intval($_GET['restore']),'post_status'=>'publish')); $msg='Restored'; }
        
        $archived = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type='nc_event' AND post_status='nc_archived' ORDER BY post_modified DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>Archive & Cleanup</h1>
            <?php if($msg): ?><div class="notice notice-success"><p><?php echo esc_html($msg); ?></p></div><?php endif; ?>
            
            <div style="display:flex;gap:20px;margin-bottom:30px;">
                <div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:5px;flex:1;">
                    <h3>Archive Events</h3>
                    <form method="post"><?php wp_nonce_field('nc_archive'); ?>
                        <p>Archive before: <input type="text" name="archive_before_date" class="nc-datepicker" style="width:120px">
                        <button type="submit" name="nc_archive_action" value="archive_before" class="button">Archive</button></p>
                    </form>
                </div>
                <div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:5px;flex:1;">
                    <h3 style="color:#a00">Cleanup</h3>
                    <form method="post" onsubmit="return confirm('Delete all archived + images?')"><?php wp_nonce_field('nc_archive'); ?>
                        <button type="submit" name="nc_archive_action" value="delete_archived" class="button" style="color:#a00">Delete All Archived (<?php echo count($archived); ?>)</button>
                    </form>
                </div>
            </div>
            
            <h2>Archived (<?php echo count($archived); ?>)</h2>
            <?php if($archived): ?>
            <form method="post"><?php wp_nonce_field('nc_archive'); ?><button type="submit" name="nc_archive_action" value="restore_all" class="button">Restore All</button></form>
            <table class="wp-list-table widefat striped" style="margin-top:10px"><thead><tr><th>Event</th><th>Date</th><th></th></tr></thead><tbody>
            <?php foreach($archived as $e): ?><tr><td><?php echo esc_html($e->post_title); ?></td><td><?php echo get_post_meta($e->ID,'_nc_start_date',true); ?></td><td><a href="?post_type=nc_event&page=nc-archive&restore=<?php echo $e->ID; ?>" class="button button-small">Restore</a></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
        <script>jQuery(function($){if($.fn.datepicker)$('.nc-datepicker').datepicker({dateFormat:'m/d/yy'})});</script>
        <?php
    }
    
    public function upcoming_page() {
        global $wpdb;
        $tz = new DateTimeZone(get_option('nc_timezone','America/New_York'));
        $cutoff = intval(get_option('nc_day_cutoff_hour',3));
        $now = new DateTime('now',$tz);
        if(intval($now->format('G'))<$cutoff) $now->modify('-1 day');
        $now->setTime(0,0,0);
        $table = $wpdb->prefix.'nc_event_instances';
        $instances = $wpdb->get_results($wpdb->prepare("SELECT i.*,p.post_title FROM $table i JOIN {$wpdb->posts} p ON i.post_id=p.ID WHERE p.post_status='publish' AND i.start>=%d ORDER BY i.start LIMIT 100",$now->getTimestamp()));
        ?>
        <div class="wrap">
            <h1>Upcoming Instances</h1>
            <table class="wp-list-table widefat striped"><thead><tr><th>Date</th><th>Day</th><th>Time</th><th>Event</th><th></th></tr></thead><tbody>
            <?php foreach($instances as $i): $dt=new DateTime('@'.$i->start);$dt->setTimezone($tz); ?>
            <tr><td><strong><?php echo $dt->format('m/d/Y'); ?></strong></td><td><?php echo $dt->format('l'); ?></td><td><?php echo $dt->format('g:i A'); ?></td>
            <td><a href="<?php echo get_edit_post_link($i->post_id); ?>"><?php echo esc_html($i->post_title); ?></a></td>
            <td><a href="<?php echo get_edit_post_link($i->post_id); ?>" class="button button-small">Edit</a></td></tr>
            <?php endforeach; ?></tbody></table>
        </div>
        <?php
    }
    
    public function get_archive_count() { global $wpdb; return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='nc_event' AND post_status='nc_archived'"); }
    
    public function auto_archive_old_events() {
        $days = get_option('nc_auto_archive_days',30); if($days<=0) return;
        global $wpdb; $cutoff = time()-($days*86400);
        $ids = $wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_nc_start_timestamp' WHERE p.post_type='nc_event' AND p.post_status='publish' AND pm.meta_value<%d",$cutoff));
        foreach($ids as $id) wp_update_post(array('ID'=>$id,'post_status'=>'nc_archived'));
    }
    
    public static function register_archive_status() { register_post_status('nc_archived',array('label'=>'Archived','public'=>false,'exclude_from_search'=>true,'show_in_admin_all_list'=>false,'show_in_admin_status_list'=>true)); }
}

add_action('init',array('Nightclub_Events','register_archive_status'));
add_action('plugins_loaded',array('Nightclub_Events','instance'));
register_activation_hook(__FILE__,'nc_activate');
function nc_activate() {
    global $wpdb; $table = $wpdb->prefix.'nc_event_instances';
    $sql = "CREATE TABLE IF NOT EXISTS $table (id bigint(20) AUTO_INCREMENT,post_id bigint(20),start bigint(20),end bigint(20),PRIMARY KEY(id),KEY post_id(post_id),KEY start(start)) ".$wpdb->get_charset_collate();
    require_once(ABSPATH.'wp-admin/includes/upgrade.php'); dbDelta($sql); update_option('nc_version',NC_VERSION);
}
