// ===================================================================
// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// NOTICE: You may use this code for any purpose, commercial or
// private, without any further permission from the author. You may
// remove this notice from your final code if you wish, however it is
// appreciated by the author if at least my web site address is kept.
//
// You may *NOT* re-distribute this code in any way except through its
// use. That means, you can include it in your product, or your web
// site, or any other form where the code is actually being used. You
// may not put the plain javascript up on your site for download or
// include it in your javascript libraries for download. Instead,
// please just point to my URL to ensure the most up-to-date versions
// of the files. Thanks.
// ===================================================================


/* 
CalendarPopup.js
Author: Matt Kruse
Last modified: 3/21/02

Rewritten by IVO GELOV to use IFRAME PopUp-s
Last Modified: 15.06.2006

DESCRIPTION: This object implements a popup calendar to allow the user to
select a date, month, quarter, or year.

COMPATABILITY: Works with Netscape 4.x, 6.x, IE 5.x on Windows. Some small
positioning errors - usually with Window positioning - occur on the 
Macintosh platform.
The calendar can be modified to work for any location in the world by 
changing which weekday is displayed as the first column, changing the month
names, and changing the column headers for each day.

USAGE: 
// Create a new CalendarPopup object
// iframe_src_url = /path/to/calendar.php?calo=name_of_calendar_object ("cal" in our case)
var cal = new CalendarPopup('iframe_id','iframe_src_url'); 

// Easy method to link the popup calendar with an input box. 
cal.select(inputObject, anchorname, dateFormat);
// This is an example call to the popup calendar from a link to populate an 
// input box. Note that to use this, date.js must also be included!!
<A HREF="#" onClick="cal.select(document.forms[0].date,'anchorname','MM/dd/yyyy'); return false;">Select</A>

// Set the type of date select to be used. By default it is 'date'.
cal.setDisplayType(type);

// When a date, month, quarter, or year is clicked, a function is called and
// passed the details. You must write this function, and tell the calendar
// popup what the function name is.
// Function to be called for 'date' select receives y, m, d
cal.setReturnFunction(functionname);
// Function to be called for 'month' select receives y, m
cal.setReturnMonthFunction(functionname);
// Function to be called for 'quarter' select receives y, q
cal.setReturnQuarterFunction(functionname);
// Function to be called for 'year' select receives y
cal.setReturnYearFunction(functionname);

// Show the calendar relative to a given anchor
cal.showCalendar(anchorname);

// Hide the calendar. The calendar is set to autoHide automatically
cal.hideCalendar();

// Set the month names to be used. Default are English month names
cal.setMonthNames("January","February","March",...);

// Set the month abbreviations to be used. Default are English month abbreviations
cal.setMonthAbbreviations("Jan","Feb","Mar",...);

// Set the text to be used above each day column. The days start with 
// sunday regardless of the value of WeekStartDay
cal.setDayHeaders("S","M","T",...);

// Set the day for the first column in the calendar grid. By default this
// is Sunday (0) but it may be changed to fit the conventions of other
// countries.
cal.setWeekStartDay(1); // week is Monday - Saturday

// Set the weekdays which should be disabled in the 'date' select popup. You can
// then allow someone to only select week end dates, or Tuedays, for example
cal.setDisabledWeekDays(0,1); // To disable selecting the 1st or 2nd days of the week

// When the 'year' select is displayed, set the number of years back from the 
// current year to start listing years. Default is 2.
cal.setYearSelectStartOffset(2);

// Text for the word "Today" appearing on the calendar
cal.setTodayText("Today");

// Set the calendar offset to be different than the default. By default it
// will appear just below and to the right of the anchorname. So if you have
// a text box where the date will go and and anchor immediately after the
// text box, the calendar will display immediately under the text box.
cal.offsetX = 20;
cal.offsetY = 20;

NOTES:
1) Requires the functions in AnchorPosition.js and date.js

2) Your anchor tag MUST contain both NAME and ID attributes which are the 
   same. For example:
   <A NAME="test" ID="test"> </A>

3) There must be at least a space between <A> </A> for IE5.5 to see the 
   anchor tag correctly. Do not do <A></A> with no space.

4) When a CalendarPopup object is created, a handler for 'onmouseup' is
   attached to any event handler you may have already defined. Do NOT define
   an event handler for 'onmouseup' after you define a CalendarPopup object 
   or the autoHide will not work correctly.
   
5) The calendar display uses "graypixel.gif" which is a 1x1 gray pixel of
   color #C0C0C0. If this file is not present, the calendar display should 
   still be fine but will not show the gray lines.
   
*/ 

function getEl(idVal)
{
	try
	{
		if (document.layers) return document.layers[idVal];
	  if (document.all != null) return document.all[idVal];
	  if (document.getElementById != null)  return document.getElementById(idVal);
	}
	catch(e) {}
	return null;
}

// CONSTRUCTOR for the CalendarPopup Object
function CalendarPopup(i_tag,cal_url) 
{
  this.framer = getEl(i_tag); // IFRAME object
  if(!this.framer)
  {
	  //alert('Can not find IFRAME '+i_tag);
	  return;
	}
	this.framer.style.width = "160px";
	this.framer.style.height = "140px";
	window._frmname = this.framer.id;
	this.cal = cal_url;
	this.offsetX = -120;
	this.offsetY = 20;
	// Calendar-specific properties
	this.monthNames = new Array('January','February','March','April','May','June','July','August','September','October','November','December');
	this.monthAbbreviations = new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	this.dayHeaders = new Array("S","M","T","W","T","F","S");
	this.weekStartDay = 0;
	this.isShowYearNavigation = false;
	this.displayType = "date";
	this.disabledWeekDays = new Object();
	this.yearSelectStartOffset = 2;
	this.currentDate = null;
	this.todayText="Today";
	window.CalendarPopup_targetInput = null;
	window.CalendarPopup_dateFormat = "dd.mm.yyyy";
	// Method mappings
 	this.returnFunction = "CalendarPopup_tmpReturnFunction";
 	this.returnMonthFunction = "CalendarPopup_tmpReturnMonthFunction";
 	this.returnQuarterFunction = "CalendarPopup_tmpReturnQuarterFunction";
 	this.returnYearFunction = "CalendarPopup_tmpReturnYearFunction";
  this.setReturnFunction = CalendarPopup_setReturnFunction;
	this.setReturnMonthFunction = CalendarPopup_setReturnMonthFunction;
	this.setReturnQuarterFunction = CalendarPopup_setReturnQuarterFunction;
	this.setReturnYearFunction = CalendarPopup_setReturnYearFunction;
	this.setMonthNames = CalendarPopup_setMonthNames;
	this.setMonthAbbreviations = CalendarPopup_setMonthAbbreviations;
	this.setDayHeaders = CalendarPopup_setDayHeaders;
	this.setWeekStartDay = CalendarPopup_setWeekStartDay;
	this.setDisplayType = CalendarPopup_setDisplayType;
	this.setDisabledWeekDays = CalendarPopup_setDisabledWeekDays;
	this.setYearSelectStartOffset = CalendarPopup_setYearSelectStartOffset;
	this.setTodayText = CalendarPopup_setTodayText;
	this.showYearNavigation = CalendarPopup_showYearNavigation;
	this.showCalendar = CalendarPopup_showCalendar;
	this.hideCalendar = CalendarPopup_hideCalendar;
	this.select = CalendarPopup_select;
	// Return the object
	attachListener();
	return this;
}

// Pass an event and return whether or not it was the IFRAME that was clicked
function isClicked(e,ifrm) 
{
	if (document.layers) 
	{
		var clickX = e.pageX;
		var clickY = e.pageY;
		var t = ifrm;
		if ((clickX > t.left) && (clickX < t.left+t.clip.width) && (clickY > t.top) && (clickY < t.top+t.clip.height)) return true;
			else return false;
	}
	else if (window.event) // (document.all) 
	{ 
	  // Need to hard-code this to trap IE for error-handling
		var t = window.event.srcElement;
		while (t.parentElement != null) 
		{
			if (t == ifrm) return true;
			t = t.parentElement;
		}
		return false;
	}
	else
	{
		var t = e.target; // e.originalTarget;
		while (t.parentNode != null) 
		{
			if (t == ifrm) return true;
			t = t.parentNode;
		}
		return false;
	}
	return false;
}

function CalendarPopup_hideCalendar() 
{
	if(arguments.length>0)
	{
		v = getEl(arguments[0]);
		if(v) v.style.display = 'none';
	}
	else this.framer.style.display = 'none';
}

// if mouse is clicked outside IFRAME - close this IFRAME
function hideIfNotClicked(e,t)
{
	if (!isClicked(e,t) && t.id == window._frmname)
	{
		t.src = '';
		t.style.display = 'none';
	}
}

function oldEvent(e)
{
	window.popOldEvent();
	newEvent(e);
}

// This global function checks all IFRAME objects onmouseup to see if they should be hidden
function newEvent(e)
{
	ifrm = document.body.getElementsByTagName('iframe');
	if(ifrm.length) 
		for(i=0; i < ifrm.length; i++)
			if(ifrm[i]) hideIfNotClicked(e,ifrm[i]);
}

// Run this immediately to attach the event listener
function attachListener() 
{
	if (document.layers) document.captureEvents(Event.MOUSEUP);
	window.popOldEvent = document.onmouseup;
	if (window.popOldEvent != null) document.onmouseup = oldEvent;
  	else document.onmouseup = newEvent;
}

// Temporary default functions to be called when items clicked, so no error is thrown
function CalendarPopup_tmpReturnFunction(y,m,d) 
{ 
	if (window.CalendarPopup_targetInput!=null) 
	{
		var d = new Date(y,m-1,d,0,0,0);
		window.CalendarPopup_targetInput.value = formatDate(d,window.CalendarPopup_dateFormat);
	}
	else alert('Use setReturnFunction() to define which function will get the clicked results!'); 
}

function CalendarPopup_tmpReturnMonthFunction(y,m) 
{ 
	alert('Use setReturnMonthFunction() to define which function will get the clicked results!\nYou clicked: year='+y+' , month='+m); 
}

function CalendarPopup_tmpReturnQuarterFunction(y,q) 
{ 
	alert('Use setReturnQuarterFunction() to define which function will get the clicked results!\nYou clicked: year='+y+' , quarter='+q); 
}

function CalendarPopup_tmpReturnYearFunction(y) 
{ 
	alert('Use setReturnYearFunction() to define which function will get the clicked results!\nYou clicked: year='+y); 
}

// Set the name of the functions to call to get the clicked item
function CalendarPopup_setReturnFunction(name)
{
  this.returnFunction = name;
}

function CalendarPopup_setReturnMonthFunction(name) 
{ 
  this.returnMonthFunction = name; 
}

function CalendarPopup_setReturnQuarterFunction(name) 
{ 
  this.returnQuarterFunction = name; 
}

function CalendarPopup_setReturnYearFunction(name) 
{ 
  this.returnYearFunction = name;
}

// Over-ride the built-in month names
function CalendarPopup_setMonthNames() 
{
	for (var i=0; i<arguments.length; i++) 
	  this.monthNames[i] = arguments[i];
}

// Over-ride the built-in month abbreviations
function CalendarPopup_setMonthAbbreviations() 
{
	for (var i=0; i<arguments.length; i++) 
	  this.monthAbbreviations[i] = arguments[i];
}

// Over-ride the built-in column headers for each day
function CalendarPopup_setDayHeaders() 
{
	for (var i=0; i<arguments.length; i++) 
	  this.dayHeaders[i] = arguments[i];
}

// Set the day of the week (0-7) that the calendar display starts on
// This is for countries other than the US whose calendar displays start on Monday(1), for example
function CalendarPopup_setWeekStartDay(day) 
{ 
  this.weekStartDay = day; 
}

// Show next/last year navigation links
function CalendarPopup_showYearNavigation() 
{ 
  this.isShowYearNavigation = true; 
}

// Which type of calendar to display
function CalendarPopup_setDisplayType(type) 
{
	if (type!="date" && type!="week-end" && type!="month" && type!="quarter" && type!="year") 
	{ 
	  alert("Invalid display type! Must be one of: date,week-end,month,quarter,year"); 
	  return false; 
	}
	this.displayType=type;
}

// How many years back to start by default for year display
function CalendarPopup_setYearSelectStartOffset(num) 
{ 
  this.yearSelectStartOffset=num; 
}

// Set which weekdays should not be clickable
function CalendarPopup_setDisabledWeekDays() 
{
	this.disabledWeekDays = new Object();
	for (var i=0; i<arguments.length; i++) 
	  this.disabledWeekDays[arguments[i]] = true;
}
	
// Set the text to use for the "Today" link
function CalendarPopup_setTodayText(text) 
{
	this.todayText = text;
}

// Populate the calendar and display it
function CalendarPopup_showCalendar(anchorname) 
{
	this.framer.src = this.cal;
	// calculate position
	var coordinates = getAnchorPosition(anchorname);
	// If the popup window will go off-screen, move it so it doesn't
	with (this.framer.style)
	{
		left = (this.offsetX + coordinates.x)+"px";
		top  = (this.offsetY + coordinates.y)+"px";
		if ((parseInt(top) + parseInt(height) - parseInt(document.body.scrollTop)) > parseInt(document.body.clientHeight)) top = (parseInt(document.body.scrollTop) + parseInt(document.body.clientHeight) - parseInt(height) - 16)+"px";
		if ((parseInt(left) + parseInt(width) - parseInt(document.body.scrollLeft)) > parseInt(document.body.clientWidth)) left = (parseInt(document.body.scrollLeft) + parseInt(document.body.clientWidth) - parseInt(width) - 16)+"px";
		if (parseInt(left) < 1) left = "1px";
		if (parseInt(top) < 1) top = "1px";
		display = 'block';
	}
}

// Simple method to interface popup calendar with a text-entry box
function CalendarPopup_select(inputobj, linkname, format) 
{
	if (!window.getDateFromFormat) 
	{
		alert("calendar.select: To use this method you must also include 'date.js' for date formatting");
		return;
	}
	if (this.displayType!="date" && this.displayType!="week-end") 
	{
		alert("calendar.select: This function can only be used with displayType 'date' or 'week-end'");
	  return;
	}
	if (inputobj.type!="text" && inputobj.type!="hidden" && inputobj.type!="textarea") 
	{ 
		alert("calendar.select: Input object passed is not a valid form input object"); 
		window.CalendarPopup_targetInput=null;
		return;
	}
	window.CalendarPopup_targetInput = inputobj;
	if (inputobj.value!="") 
	{
		var time = getDateFromFormat(inputobj.value,format);
		if (time==0) this.currentDate=null;
  		else this.currentDate=new Date(time);
	}
	else this.currentDate=null;
	window.CalendarPopup_dateFormat = format;
	this.showCalendar(linkname);
}
