<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("global_intermediate", "inplaykalender_global");
$plugins->add_hook("newthread_start", "inplaykalender_newthread");
$plugins->add_hook("newthread_do_newthread_end", "inplaykalender_do_newthread");
$plugins->add_hook("newthread_do_newthread_end", "inplaykalender_do_newthread");
$plugins->add_hook("editpost_start", "inplaykalender_editpost_start");
$plugins->add_hook("editpost_do_editpost_end", "inplaykalender_do_editpost");

function inplaykalender_info(){
    return array(
        "name"			=> "Inplaykalender",
        "description"	=> "Fügt dem Forum einen Kalender hinzu, der extra auf das Ingame eines RPGs abgestimmt ist.",
        "website"		=> "http://github.com/user/its-sparks-fly",
        "author"		=> "sparks fly",
        "authorsite"	=> "http://github.com/user/its-sparks-fly",
        "version"		=> "1.0",
        "compatibility" => "*"
    );
}

function inplaykalender_install() {
    global $mybb, $db;

    if(!$db->table_exists("events")) {
        $db->query("CREATE TABLE `mybb_events` (
            `eid` int(11) NOT NULL AUTO_INCREMENT,
            `uid` int(11) NOT NULL,
            `name` text NOT NULL,
            `description` text NOT NULL,
            `starttime` varchar(20) NOT NULL,
            `endtime` varchar(20) NOT NULL,
            `accepted` int(1) NOT NULL,
            PRIMARY KEY (`eid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
    }

  switch($db->type)
  {
    case "pgsql":
      $db->add_column("threads", "event", "text NOT NULL");
      $db->add_column("posts", "event", "text NOT NULL");
    break;
        case "sqlite":
      $db->add_column("threads", "event", "text NOT NULL");
      $db->add_column("posts", "event", "text NOT NULL");
    break;
        default:
    $db->add_column("threads", "event", "text NOT NULL");
    $db->add_column("posts", "event", "text NOT NULL");
    break;
  }

    $setting_group = array(
        'name' => 'inplaykalender',
        'title' => 'Inplaykalender Einstellungen',
        'description' => 'Fügt dem Forum einen Kalender hinzu, der extra auf das Ingame eines RPGs abgestimmt ist.',
        'disporder' => 5, // The order your setting group will display
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
    // A text setting
    'inplaykalender_year' => array(
        'title' => 'Spieljahr',
        'description' => 'In welchem Jahr spielt dein RPG?',
        'optionscode' => 'text',
        'value' => '2017', // Default
        'disporder' => 1
    ),
    'inplaykalender_months' => array(
        'title' => 'Spieljahr',
        'description' => 'In welchen Monaten spielt dein RPG? Monate mit "," trennen.',
        'optionscode' => 'text',
        'value' => 'April,Mai,Juni', // Default
        'disporder' => 1
    ),
    'inplaykalender_text' => array(
        'title' => 'Spieljahr',
        'description' => 'Text für den aktuellen Inplayzeitraum',
        'optionscode' => 'textarea',
        'value' => '', // Default
        'disporder' => 1
    ),
    );

    foreach($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    rebuild_settings();
    #TODO:Templates hinzufügen
}

function inplaykalender_is_installed() {
    global $db;

    if($db->table_exists("events")) {
        return true;
    }
    return false;
}

function inplaykalender_uninstall() {
    global $db;  

    if($db->table_exists("events")) {
        $db->query("DROP TABLE `mybb_events`");
    }

    if($db->field_exists("event", "threads")) {
        $db->drop_column("threads", "event");
    }
    if($db->field_exists("event", "posts"))
    {
        $db->drop_column("posts", "event");
    }

    $db->delete_query('settings', "name LIKE 'inplaykalender%'");
    $db->delete_query('settinggroups', "name = 'inplaykalender'");
    rebuild_settings();
}

function inplaykalender_activate() {
    global $mybb, $db;

        // CSS  
    $css = array(
        'name' => 'inplaykalender.css',
        'tid' => 1,
        "stylesheet" => '       .inplaykalender_tag { text-align: center; font-size: 8px; letter-spacing: 1px; } 
        .szenen { background-color: #C8B6CC; } 
        .szenen strong { color: #543D59 !important; } 
        .geburtstag { background-color: #EBD39D; } 
        .geburtstag strong { color: #6E644E !important; } 
        .timeline { background-color: #BADBAF;}
        .timeline strong { color: #3D4F37 !important; }
        .event { background-color: #ABD9D8; }
        .event strong { color: #4D6E6D !important; }
        
        .szenentimeline { background: linear-gradient(to left top, #EBD39D 50%, #BADBAF 50%); }
        .szenentimeline strong { color: #3D4F37 !important; }
        .szenengeburtstag { background: linear-gradient(to left top, #EBD39D 50%, #C8B6CC 50%); }
        .szenenevent { background: linear-gradient(to left top, #EBD39D 50%, #ABD9D8 50%); }
        .szenenevent strong { }
        .szenengeburtstag strong { color: #543D59 !important; }
        .geburtstagtimeline { background: linear-gradient(to left top, #C8B6CC 50%, #BADBAF 50%); }
        .geburtstagtimeline strong { color: #6E644E !important;  }
        .geburtstagevent { background: linear-gradient(to left top, #C8B6CC 50%, #ABD9D8 50%); }
        .geburtstagevent strong { }
        .timelineevent { background: linear-gradient(to left top, #BADBAF 50%, #ABD9D8 50%);  }
        .timelineevent strong { }
        
        .szenengeburtstagtimeline { background: linear-gradient(to left top, #EBD39D 33%, #C8B6CC 33%, #C8B6CC 66%, #BADBAF 66%); }
        .szenengeburtstagtimeline strong { color: #543D59 !important; }
        .szenengeburtstagevent { background: linear-gradient(to left top, #EBD39D 33%, #C8B6CC 33%, #C8B6CC 66%, #ABD9D8 66%); }
        .szenengeburtstagevent strong { }
        .szenentimelineevent {  background: linear-gradient(to left top, #EBD39D 33%, #BADBAF 33%, #BADBAF 66%, #ABD9D8 66%); }
        .szenentimelineevent strong { }
        .geburtstagtimelineevent { background: linear-gradient(to left top, #C8B6CC 33%, #BADBAF 33%, #BADBAF 66%, #ABD9D8 66%); }
        .geburtstagtimelineevent strong { }
        
        .szenengeburtstagtimelineevent { background: linear-gradient(to left top, #EBD39D 25%, #C8B6CC 25%, #C8B6CC 50%, #BADBAF 50%, #BADBAF 75%, #ABD9D8 75%); }

        #mini-kalender { font-size: 7px; }
        #mini-kalender td { padding: 5px; }',
        'cachefile' => $db->escape_string(str_replace('/', '', inplaykalender.css)),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

    $tids = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("newthread", "#".preg_quote('{$loginbox}')."#i", '{$select_event}');
    find_replace_templatesets("editpost", "#".preg_quote('{$loginbox}')."#i", '{$select_event}');
}

function inplaykalender_deactivate() {
    global $mybb, $db;

    // drop css
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'inplaykalender.css'");
    $query = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("newthread", "#".preg_quote('{$select_event}')."#i", '', 0);
    find_replace_templatesets("editpost", "#".preg_quote('{$select_event}')."#i", '', 0);
}

function inplaykalender_global() {
    global $lang, $mybb, $db, $templates, $theme, $day_calendar_bit, $header_inplaykalender_bit, $header_inplaykalender;

    $lang->load('inplaykalender');

    // get inplay months
    $inplay_months = explode(",", $mybb->settings['inplaykalender_months']);
    $months_count = count($inplay_months);

    // set up months array
    $months = array(1 => $lang->inplaykalender_januar, $lang->inplaykalender_februar, $lang->inplaykalender_maerz, $lang->inplaykalender_april, $lang->inplaykalender_mai, $lang->inplaykalender_juni, $lang->inplaykalender_juli, $lang->inplaykalender_august, $lang->inplaykalender_september, $lang->inplaykalender_oktober, $lang->inplaykalender_november, $lang->inplaykalender_dezember);
    $months_en = array(1 => $lang->inplaykalender_januar_en, $lang->inplaykalender_februar_en, $lang->inplaykalender_maerz_en, $lang->inplaykalender_april_en, $lang->inplaykalender_mai_en, $lang->inplaykalender_juni_en, $lang->inplaykalender_juli_en, $lang->inplaykalender_august_en, $lang->inplaykalender_september_en, $lang->inplaykalender_oktober_en, $lang->inplaykalender_november_en, $lang->inplaykalender_dezember_en);

    foreach($inplay_months as $month)
    {
        $key = array_search($month, $months);
        $month_en = $months_en[$key];
        $day_calendar_bit = "";
        $event = "";

        // get days in month
        $number_days = cal_days_in_month(CAL_GREGORIAN, $key, $mybb->settings['inplaykalender_year']);

        // get first day of month
        $time_str = "01-{$months_en[$key]}-{$mybb->settings['inplaykalender_year']}"; // pattern: d-F-Y
        $first_day = date('w', strtotime($time_str));
        
        //get last day of month
        $time_str = "{$number_days}-{$months_en[$key]}-{$mybb->settings['inplaykalender_year']}"; // pattern: d-F-Y
        $last_day = date('w', strtotime($time_str));
        
        // get empty table datas (e.g. month starts on thursday)
        for($j = 0; $j < $first_day; $j++) {
            eval("\$day_calendar_bit .= \"".$templates->get("inplaykalender_no_day_bit")."\";");
            $days++;
            if($days == 7) {
                $day_calendar_bit .= "</tr><tr>";
                $days = 0;
            }
        }

        // get month's days table datas            
        for($i = 1; $i <= $number_days; $i++) {
            $date = strtotime("{$i}-{$months_en[$key]}-{$mybb->settings['inplaykalender_year']}");
            $title = $i;
            $event = "";
            
            // get inplay scenes
            $szenen = false;
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."threads WHERE ipdate = '$date'");
            if(mysqli_num_rows($query) > 0) {
                $szenen = true;
            }
            
            // get birthdays
            $birthday = false;
            $fulldate = date("j.m.", $date);                
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."characters WHERE birthday LIKE '$fulldate%'");
            if(mysqli_num_rows($query) > 0) {
                $birthday = true;
            }
            
            // get timeline events
            $timeline = false;
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."timeline WHERE date = '$date'");
            if(mysqli_num_rows($query) > 0) {
                $timeline = true;
            }
            
            // get calendar events
            $events = false;
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."events");
            while($event_list = $db->fetch_array($query)) {
                if($event_list['starttime'] <= $date && $event_list['endtime'] >= $date) {
                    $title = "<a href=\"inplaykalender.php#{$date}\" target=\"blank\"><strong>{$i}</strong></a>";
                    $events = true;
                }
            }
            
            // set css class and format day link 
            $list_of_events = array("$lang->inplaykalender_class_scenes" => $szenen, "$lang->inplaykalender_class_birthday" => $birthday, "$lang->inplaykalender_class_timeline" => $timeline, "$lang->inplaykalender_class_event" => $events);
            foreach($list_of_events as $class => $single_event) {
                if($single_event) {
                    $event .= $class;
                    $title = "<a href=\"inplaykalender.php#{$date}\" target=\"blank\"><strong>{$i}</strong></a>";
                }
            }

            // get day template
            eval("\$day_calendar_bit .= \"".$templates->get("inplaykalender_day_bit")."\";");
            $days++;
            // check for full week
            if($days == 7) {
                $day_calendar_bit .= "</tr><tr>";
                $days = 0;
            }
        }
        
        // get empty table datas (e.g. month ends on saturday)
        for($k = $last_day + 1; $k <= 6; $k++) {
            eval("\$day_calendar_bit .= \"".$templates->get("inplaykalender_no_day_bit")."\";");
            $days++;
            if($days == 7) {
                $day_calendar_bit .= "</tr><tr>";
                $days = 0;
            }
        }
        // get table width & max 3 months per row
        $width = 100 / $months_count;
        $width = $width - 1;
        if($width > 32) {
            $width = 32;
        }
        eval("\$header_inplaykalender_bit .= \"".$templates->get("header_inplaykalender_bit")."\";");
    }
    if($mybb->usergroup['cancp'] == "1") {
        eval("\$header_inplaykalender = \"".$templates->get("header_inplaykalender")."\";");
    }
}

function inplaykalender_newthread()
{
    global $lang, $mybb, $cache, $db, $templates, $forum, $select_event;
    $lang->load('inplaykalender');
    $board = $mybb->settings['inplaytracker_forum'];
    $year = $mybb->settings['inplaykalender_year'];

    if(preg_match("/$board/i", $forum['parentlist']))
    {
    $query = $db->query("SELECT name, eid FROM ".TABLE_PREFIX."events");
    while($eventlist = $db->fetch_array($query)) {
      $select_event .= "<option value=\"$eventlist[eid]\">$eventlist[name]</option>";
    }

    eval("\$select_event = \"".$templates->get("newthread_inplaykalender_event")."\";");

    }
}

function inplaykalender_do_newthread()
{
  global $mybb, $cache, $db, $tid;

  $new_record = array(
        "event" => (int)$mybb->get_input('event')
    );
    $db->update_query("threads", $new_record, "tid='{$tid}'");
}

function inplaykalender_editpost_start()
{
  global $lang, $mybb, $db, $cache, $templates, $forum, $select_event, $thread, $pid, $checked_event;
  $lang->load('inplaykalender');
    $board = $mybb->settings['inplaytracker_forum'];
    $year = $mybb->settings['inplaykalender_year'];

    if($pid == $thread['firstpost'] && preg_match("/$board/i", $forum['parentlist'])) {
        $query = $db->query("SELECT name, eid FROM ".TABLE_PREFIX."events");
        while($eventlist = $db->fetch_array($query)) {
            $select_event .= "<option value=\"$eventlist[eid]\">$eventlist[name]</option>";
        }
        eval("\$select_event = \"".$templates->get("newthread_inplaykalender_event")."\";");
    }
}   

function inplaykalender_do_editpost()
{
    global $db, $mybb, $tid, $pid, $thread;

    $new_record = array(
        "event" => (int)$mybb->get_input('event')
    );
    $db->update_query("threads", $new_record, "tid='{$tid}'");
}