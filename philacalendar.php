<?php
/*
Plugin Name: PhilaCalendar
Description: Display Google Calendar entries.
Version: 1.0
*/

class PhilaCalendar extends WP_Widget {

  function PhilaCalendar() {
     /* Widget settings. */
    $widget_ops = array(
      'classname' => 'PhilaCalendar',
      'description' => 'Display Google Calendar entries.');

     /* Widget control settings. */
    $control_ops = array(
       'width' => 250,
       'height' => 250,
       'id_base' => 'PhilaCalendar');

    /* Create the widget. */
   $this->WP_Widget('PhilaCalendar', 'Google Calendar entries.', $widget_ops, $control_ops );
  }

  function form ($instance) {
    /* Set up some default widget settings. */
    $defaults = array('numberposts' => '5','title'=>'','url'=>'');
    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

  <p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
    <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20">
  </p>
 
  <p>
   <label for="<?php echo $this->get_field_id('numberposts'); ?>">Number of posts:</label>
   <select id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>">
   <?php for ($i=1;$i<=20;$i++) {
     echo '<option value="'.$i.'"';
     if ($i==$instance['numberposts']) echo ' selected="selected"';
       echo '>'.$i.'</option>';
     } ?>
   </select>
  </p>

  <p>
    <label for="<?php echo $this->get_field_id('url'); ?>">Google Calendar Address:</label>
    <input type="text" name="<?php echo $this->get_field_name('url') ?>" id="<?php echo $this->get_field_id('url') ?> " value="<?php echo $instance['url'] ?>" size="50">
  </p>

  <?php
}

function update ($new_instance, $old_instance) {
  $instance = $old_instance;

  $instance['numberposts'] = $new_instance['numberposts'];
  $instance['title'] = $new_instance['title'];
  $instance['url'] = $new_instance['url'];

  return $instance;
}

function PhilaCalendarGetFeedFromProxy($url){
	//Use local proxy server when runnign on dev machine
	$aContext = array(
	    'http' => array(
	        'proxy' => 'tcp://127.0.0.1:3128',
	        'request_fulluri' => true,
	    ),
	);
	$cxContext = stream_context_create($aContext);
	$data = json_decode(file_get_contents($url, True, $cxContext));

	return $data;
}

function PhilaCalendarGetFeed($url){
	//When running on server, no proxy is required
	$data = json_decode(file_get_contents($url, True));

	return $data;
}

function philaCalendarWidget_handler($calendar, $itemLimit, $url){


	//Working locally we need to use a proxy server to return data. When deploying on server, change to direct read.
	//$data = PhilaCalendarGetFeed($currentURL);
	
	$calID = $url;
	$calID = str_ireplace("http://www.google.com/calendar/feeds/", "", $calID);
	$calID = str_ireplace("%40group.calendar.google.com/public/full", "", $calID);
	
	$url = $url . '?orderby=starttime&sortorder=ascending&max-results=' . $itemLimit . '&futureevents=true&alt=json';
	$data = $calendar->PhilaCalendarGetFeedFromProxy($url);
	foreach ($data->feed->entry as $item)
	{
		$array_item = (array) $item;
		
		$title = (array) $item->title;		
		$start = $array_item['gd$when'][0]->startTime;			
		
		$eventArray[] = array('title' => $title['$t'], 'startDate' => $start);
	}
	
	
	//Sort our array of events by date
	function date_compare($a, $b)
	{
	    $t1 = strtotime($a['startDate']);
	    $t2 = strtotime($b['startDate']);
	    return $t1 - $t2;
	}    
	usort($eventArray, 'date_compare');
	
	//Protect against when event array is smaller than item limit
	if(sizeof($eventArray) < $itemLimit)
	{
		$itemLimit = sizeof($eventArray);
	}
	
	$eventCount = (int)0;
	$eventString = "<div id=\"PhilaGoogleCalendarEventSection\">";
	
	
	//Loop through the events until we hit the item limit
	while($eventCount < $itemLimit){
		$currentEvent = (array)$eventArray[$eventCount];
		$startDate = new DateTime($currentEvent['startDate']);//convert startdate string to DateTime object

		$eventString .= "<div class=\"PhilaGoogleCalendarDateRow\">".date_format($startDate, 'l').", ".date_format($startDate, 'm/d/Y')."</div>"; 
		$eventString .= "<div class=\"PhilaGoogleCalendarTitleRow\">".date_format($startDate, 'g:i A')." - ".$currentEvent['title']."<A href=\"./CalendarDetails?calendar=$calID&limit=$itemLimit#Event$eventCount\">View Details >></A>"."</div>";
		$eventCount++;
	}
	
	$eventString .= "</div>";
	
	$output = "<div id=\"PhilaGoogleCalendarWidget\" class=\"PhilaWidget\">";
	$output .= "	<span id=\"PhilaGoogleCalendarMainWindow\">";
	$output .= "		<h1 class=\"PhilaWidgetTitle\">Events</h1>";
	$output .= $eventString;
	$output .= "	</span>";
	$output .= "</div>";
	
	return $output;
}

function widget ($args,$instance) {
    extract($args);
    
    $calendar = new PhilaCalendar();

    $title = $instance['title'];
    $numberposts = $instance['numberposts'];
    $url = $instance['url'];
    
    //Add the widget to the page
    echo $before_widget;
    echo $before_title.$title.$after_title;    
  	echo $calendar->philaCalendarWidget_handler($calendar, $numberposts, $url);
    echo $after_widget;
 }
}


//add_action('widgets_init', 'ahspfc_load_widgets');
add_action(
'widgets_init',
create_function('','return register_widget("PhilaCalendar");')
);

?>
