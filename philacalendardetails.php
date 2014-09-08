<?php
/* Plugin Name: Phila Calendar Details Widget
Plugin URI: localhost/wordpress
Description: Shows Calendar Event Details.
Version: 1.0
Author: Andrew Kennel
Author URI: localhost/wordpress
*/
add_shortcode('PhilaCalendarDetailsWidget', 'philaCalendarDetailsWidget_handler');

function philaCalendarDetailsWidget_handler(){
	
$message = <<<EOM
	
<head>	
<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
<script type="text/javascript">

	function getUrlVars()
	{
	    var vars = [], hash;
	    vars["limit"] = 1;
	    vars["anchor"] = 0;
	    var calendars = [];
	    var hashes = location.search.slice(location.search.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++)
	    {
	        hash = hashes[i].split('=');
	        if (hash[0] == "calendar")
	        {
	        	calendars.push(hash[1]);
	        }
	        else
	        {
		        vars.push(hash[0]);
		        vars[hash[0]] = hash[1];
	        }	        
	    }
	    
	    if (calendars.length == 0)
	    {
	    	calendars.push("3efanutsrofqu273785lh789ko");	    	
	    }
	    
	    if (location.hash != null){
			vars["anchor"] = location.hash.substring(6);
		}
	    
	    vars.push("calendar");
	    vars["calendar"] = calendars;
	    
	    return vars;
	}


	function updateCalendarDetails(parameters){
		
		var calendarArray = parameters["calendar"];
		var calendarID = calendarArray[0];
		var anchorTag = parameters["anchor"];
		var calendarFeedURL = "http://www.google.com/calendar/feeds/" + calendarID + "%40group.calendar.google.com/public/full?orderby=starttime&sortorder=ascending&alt=json&futureevents=true&max-results=" + parameters["limit"];

		//ConsumeAPI(calendarFeedURL);
		$.ajax({
			type: "POST",
			dataType: 'jsonp',
			url: calendarFeedURL,
			crossDomain : true,
			})
			.success(function( data ) {
				var calendarTitle = data.feed.title["\$t"];
				$('#PhilaWidgetTitle').text(calendarTitle);
				
					for (var i = 0; i<data.feed.entry.length; i++){
						
						var title = data.feed.entry[i].title["\$t"];
						var content = data.feed.entry[i].content["\$t"];
						var startTime = new Date(data.feed.entry[i].gd\$when[0].startTime);
						var location = data.feed.entry[i].gd\$where[0].valueString;
						var dateLine = "<div class=\"PhilaGoogleCalendarDateRow\">" + getDayString(startTime) + "</div>";
						var titleLine = "<div class=\"PhilaGoogleCalendarTitleRow\"><span class=\"PhilaGoogleCalendarTime\">" + getHourString(startTime) + "<\span> - " + title + "</div>";
						var locationLine = "<div class=\"PhilaGoogleCalendarLocationRow\">" + location + "</div>";
						var contentLine = "<div class=\"PhilaGoogleCalendarContentRow\">" + content + "</div>";
						var openLi = "";
						if (i == anchorTag){
							openLi = "<li id=\"Event" + i + "\" class=\"PhilaGoogleCalendarSelectedItem\" class=\"phila-event-details\">";
						}
						else{
							openLi = "<li id=\"Event" + i + "\" class=\"phila-event-details\">";
						}
						
						var newEntry = openLi + dateLine + titleLine + locationLine + contentLine + "</li>";
						$("#ResultList").append(newEntry);
					}
			})
			.fail( function(xhr, textStatus, errorThrown) {
				alert(xhr.responseText);
				alert(textStatus);
			}); 		
	}
	
	function getDayString(startTime){
		var d = startTime.getDay();
		var weekday = new Array(7);
		weekday[0]=  "Sunday";
		weekday[1] = "Monday";
		weekday[2] = "Tuesday";
		weekday[3] = "Wednesday";
		weekday[4] = "Thursday";
		weekday[5] = "Friday";
		weekday[6] = "Saturday";

		var dayOfWeek = weekday[d];	
		var calendarDate = startTime.toLocaleDateString();

		return dayOfWeek + ", " + calendarDate;
	}
	
	function getHourString(startTime){
		var timeString = startTime.toLocaleTimeString();
		var AMPM = timeString.substring(timeString.length - 2, timeString.length + 1);
		timeString = timeString.substring(0,timeString.length - 6);
		return timeString + " " + AMPM;
	}
	

jQuery(document).ready(function($) {	
		//updateCalendarDetails();
		var parameters = getUrlVars();
		updateCalendarDetails(parameters);
    });
</script>
	
</head>
<body>




<div id="PhilaGoogleCalendarEventSection">
	<div id="PhilaGoogleCalendarWidget" class="PhilaWidget">
		<span id="PhilaGoogleCalendarMainWindow">
			<h1 id="PhilaWidgetTitle">Events</h1>
			<ul id="ResultList"></ul>
		</span>
	</div>
</div>
	
EOM;
	
	
	
	return $message;
}

function philaCalendarDetailsWidget($args, $instance) { // widget sidebar output
  extract($args, EXTR_SKIP);
  echo $before_widget; // pre-widget code from theme
  echo philaCalendarDetailsWidget_handler();
  echo $after_widget; // post-widget code from theme
}
?>
