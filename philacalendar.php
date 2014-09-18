<?php

/* Plugin Name: Phila Calendar Widget
Plugin URI: localhost/wordpress
Description: Displays Google Calendar entries.
Version: 1.0
Author: Andrew Kennel
Author URI: localhost/wordpress
*/
add_shortcode( 'PhilaCalendar', 'PhilaCalendar' );

function PhilaCalendar( $atts ) {
    $a = shortcode_atts( array(
    	'numberposts' => '',
        'title' => '',
        'url' => ''
    ), $atts );

  echo $before_widget; // pre-widget code from theme
  echo BuildPhilaCalendarString($a);	
  echo $after_widget; // post-widget code from theme
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

function BuildPhilaCalendarString($a){

	//Get parameters from short code. Supply default values if any are blank.
	$widgetTitle = $a['title'];
	$itemLimit = $a['numberposts'];
	$url = $a['url'];
	if ($widgetTitle == ""){
		$widgetTitle = "Calendar Events";
	}
	if ($itemLimit == ""){
		$itemLimit = 5;
	}
	if ($url == ""){
		$url = "http://www.google.com/calendar/feeds/3efanutsrofqu273785lh789ko%40group.calendar.google.com/public/full";
	}
	
	//Adjust URL to set item limit and force JSON connection	
	$calID = $url;
	$calID = str_ireplace("http://www.google.com/calendar/feeds/", "", $calID);
	$calID = str_ireplace("%40group.calendar.google.com/public/full", "", $calID);	
	$url = $url . '?orderby=starttime&sortorder=ascending&max-results=' . $itemLimit . '&futureevents=true&alt=json';
	
	//If server is localhost, use proxy to get feed, otherwise use direct connection
	$serverName = $_SERVER['HTTP_HOST'];
	if ($serverName == "localhost"){
		$data = PhilaCalendarGetFeedFromProxy($url);
	}
	else{
		$data = PhilaCalendarGetFeed($url);
	}
	
	//build array of events pulling title and start date
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
	
	//Loop through the events until we hit the item limit
	$eventCount = (int)0;
	$eventString = "<div id=\"PhilaGoogleCalendarEventSection\">";
	while($eventCount < $itemLimit){
		$currentEvent = (array)$eventArray[$eventCount];
		$startDate = new DateTime($currentEvent['startDate']);//convert startdate string to DateTime object

		$eventString .= "<div class=\"PhilaGoogleCalendarDateRow\">".date_format($startDate, 'l').", ".date_format($startDate, 'm/d/Y')."</div>"; 
		$eventString .= "<div class=\"PhilaGoogleCalendarTitleRow\">".date_format($startDate, 'g:i A')." - ".$currentEvent['title']."<A href=\"./CalendarDetails?calendar=$calID&limit=$itemLimit#Event$eventCount\">View Details >></A>"."</div>";
		$eventCount++;
	}	
	$eventString .= "</div>";
	
	//build outputHTML with event items and title embedded
	$output = "<div id=\"PhilaGoogleCalendarWidget\" class=\"PhilaWidget\">";
	$output .= "	<span id=\"PhilaGoogleCalendarMainWindow\">";
	$output .= "		<h1 class=\"PhilaWidgetTitle\">".$widgetTitle."</h1>";
	$output .= $eventString;
	$output .= "	</span>";
	$output .= "</div>";
	
	return $output;
}



?>
