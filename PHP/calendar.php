<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Calendar</title>
<meta http-equiv="content-type" Content="text/html;charset=windows-1251">
<SCRIPT LANGUAGE="JavaScript" SRC="dragiframe.js"></SCRIPT>
<STYLE>
TD.cal 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
}

TD.calmonth 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
	text-align: right;
}

TD.caltoday 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
	font-weight: normal; 
	text-align: right; 
	background-color: #FFCC66;
}

A.textlink:link, A.textlink:visited 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
	text-decoration: none; 
	color: blue; 
}

.disabledtextlink 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
	font-weight: normal; 
	color: #808080; 
}

A.cal, A.calthismonth 
{ 
	font-family: Tahoma;	
	font-size: 8pt;	
	font-weight: normal; 
	text-decoration: none; 
	color: black; 
}

A.calothermonth 
{ 
	font-family: Tahoma; 
	font-size: 8pt; 
	font-weight: normal; 
	text-decoration: none;	
	color: #808080; 
}

.calnotclickable 
{	
	font-family: Tahoma;	
	font-size: 8pt;	
	font-weight: normal; 
	color: #808080; 
}
</STYLE>
<SCRIPT LANGUAGE="JavaScript">
function getEl(idVal)
{
  if (document.getElementById != null) return document.getElementById(idVal);
  if (document.all != null) return document.all[idVal];
	if (document.layers) return document.layers[idVal];
	return null;
}

function frm_resize()
{
	var tbl = getEl('calend');
	if(tbl)
	{
		var oH = tbl.clip ? tbl.clip.height : tbl.offsetHeight;
		var oW = tbl.clip ? tbl.clip.width : tbl.offsetWidth;
		window.resizeTo( oW, oH );
	  var myW = 0, myH = 0;
	  if( typeof( window.innerWidth ) == 'number' ) 
	  {
	    //Non-IE
	    myW = window.innerWidth;
	    myH = window.innerHeight;
	  } 
	  else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) 
	  {
	    //IE 6+ in 'standards compliant mode'
	    myW = document.documentElement.clientWidth;
	    myH = document.documentElement.clientHeight;
	  } 
	  else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) 
	  {
	    //IE 4 compatible
	    myW = document.body.clientWidth;
	    myH = document.body.clientHeight;
	  }
		//Opera 6- adds on 16 pixels for the non-existent scrollbar
		//if( window.opera && !document.childNodes ) myW += 16;
		//window.resizeTo(6+ oW + ( oW - myW ),6+ oH + ( oH - myH ) );
		window.resizeTo(oW + ( oW - myW ),oH + ( oH - myH ));
	}
}
</script>
</head>
<body topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" MARGINWIDTH="0" MARGINHEIGHT="0" onLoad="getCalendar(); addHandle(getEl('pole'), window); frm_resize();" style="overflow:visible; border:0px; background-color: transparent">
<TABLE id="calend" name="calend" WIDTH="154" CELLSPACING="0" CELLPADDING="0" BGCOLOR="white" style="font-family:Tahoma; font-size:8pt; border:2px solid #663333">
	<TR><TD ALIGN="CENTER" id="pole" name="pole"></TD></TR>
</TABLE>
<SCRIPT LANGUAGE="JavaScript">
function getCalendar()
{
	tbl = getEl('pole');
	<? if($_GET['calo']!='') { ?>
	cal_obj = window.parent.<?=$_GET['calo'];?>;
	if(!cal_obj)
	{
		alert('"<?=$_GET['calo'];?>" not found');
		return;
	}

	var now = new Date();
	var windowref = "window.parent.";
	var refresh = 'javascript: getCalendar';
	var result = "";

	// Code for DATE display (default)
	// -------------------------------
	if (cal_obj.displayType=="date" || cal_obj.displayType=="week-end") 
	{
		if (!cal_obj.currentDate) cal_obj.currentDate = now;
		if (arguments.length > 1) var month = arguments[1];
			else var month = cal_obj.currentDate.getMonth()+1;
		if (arguments.length > 2) var year = arguments[2];
			else var year = cal_obj.currentDate.getFullYear();
		var daysinmonth= new Array(0,31,28,31,30,31,30,31,31,30,31,30,31);
		if (((year%4 == 0) && (year%100 != 0)) || (year%400 == 0)) daysinmonth[2] = 29;
		var current_month = new Date(year,month-1,1);
		var display_year = year;
		var display_month = month;
		var display_date = 1;
		var weekday = current_month.getDay();
		var offset = 0;
		if (weekday >= cal_obj.weekStartDay) offset = weekday - cal_obj.weekStartDay;
  		else offset = 7-cal_obj.weekStartDay+weekday;
		if (offset > 0) 
		{
			display_month--;
			if (display_month < 1)
			{
			  display_month = 12; 
			  display_year--;
			}
			display_date = daysinmonth[display_month]-offset+1;
		}
		var next_month = month+1;
		var next_month_year = year;
		if (next_month > 12) 
		{
		  next_month=1; 
		  next_month_year++;
		}
		var last_month = month-1;
		var last_month_year = year;
		if (last_month < 1) 
		{
		  last_month=12; 
		  last_month_year--;
		}
		var date_class;
		result += '<TABLE WIDTH=154 BORDER=0 BORDERWIDTH=0 CELLSPACING=0 CELLPADDING=2 style="font-family:Tahoma; font-size:8pt">\n';
		result += '<TR BGCOLOR="#C0C0C0">\n';
		var refresh = 'javascript: getCalendar';
		if (cal_obj.isShowYearNavigation) 
		{
			var td = '<TD BGCOLOR="#C0C0C0" CLASS="cal" ALIGN=CENTER VALIGN=MIDDLE ';
			result += td + ' WIDTH=10><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+last_month+','+last_month_year+');">&lt;</A></B></TD>';
			result += td + ' WIDTH=58>'+cal_obj.monthNames[month-1]+'</TD>';
			result += td + ' WIDTH=10><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+next_month+','+next_month_year+');">&gt;</A></B></TD>';
			result += td + ' WIDTH=10>&#160;</TD>';
			result += td + ' WIDTH=10><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+month+','+(year-1)+');">&lt;</A></B></TD>';
			result += td + ' WIDTH=36>'+year+'</TD>';
			result += td + ' WIDTH=10><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+month+','+(year+1)+');">&gt;</A></B></TD>';
		}
		else 
		{
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=22 ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+last_month+','+last_month_year+');">&lt;&lt;</A></B></TD>\n';
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=100 ALIGN=CENTER><B>'+cal_obj.monthNames[month-1]+' '+year+'</B></TD>\n';
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=22 ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+next_month+','+next_month_year+');">&gt;&gt;</A></B></TD>\n';
		}
		result += '</TR></TABLE>\n';
		result += '<TABLE WIDTH=120 BORDER=0 CELLSPACING=0 CELLPADDING=0 ALIGN=CENTER style="font-family:Tahoma; font-size:8pt">\n';
		result += '<TR>\n';
		var td = '	<TD CLASS="cal" ALIGN=RIGHT WIDTH=14%>';
		for (var j=0; j<7; j++) 
		  result += td+cal_obj.dayHeaders[(cal_obj.weekStartDay+j)%7]+'</TD>\n';
		result += '</TR>\n';
		result += '<TR><TD COLSPAN=7 BGCOLOR=#663333 ALIGN=CENTER><IMG SRC="<?php echo WEBDIR; ?>/images/graypixel.gif" WIDTH=120 HEIGHT=1></TD></TR>\n';
		for (var row=1; row<=6; row++) 
		{
			result += '<TR>\n';
			for (var col=1; col<=7; col++) 
			{
				if (display_month == month) date_class = "calthismonth";
  				else date_class = "calothermonth";
				if ((display_month == cal_obj.currentDate.getMonth()+1) && (display_date==cal_obj.currentDate.getDate()) && (display_year==cal_obj.currentDate.getFullYear())) td_class="caltoday";
				  else td_class="calmonth";
				if (cal_obj.disabledWeekDays[col-1]) 
				{
					date_class="calnotclickable";
					result += '	<TD CLASS="'+td_class+'"><SPAN CLASS="'+date_class+'">'+display_date+'</SPAN></TD>\n';
				}
				else 
				{
					var selected_date = display_date;
					var selected_month = display_month;
					var selected_year = display_year;
					if (cal_obj.displayType=="week-end") 
					{
						var d = new Date(selected_year,selected_month-1,selected_date,0,0,0,0);
						d.setDate(d.getDate() + (7-col));
						selected_year = d.getYear();
						if (selected_year < 1000) selected_year += 1900;
						selected_month = d.getMonth()+1;
						selected_date = d.getDate();
					}
					result += '	<TD CLASS="'+td_class+'"><A HREF="javascript:;" onClick="javascript: '+windowref+cal_obj.returnFunction+'('+selected_year+','+selected_month+','+selected_date+'); cal_obj.hideCalendar();" CLASS="'+date_class+'">'+display_date+'</A></TD>\n';
				}
				display_date++;
				if (display_date > daysinmonth[display_month]) 
				{
					display_date=1;
					display_month++;
				}
				if (display_month > 12) 
				{
					display_month=1;
					display_year++;
				}
			}
			result += '</TR>';
		}
		var current_weekday = now.getDay();
		result += '<TR><TD COLSPAN=7 ALIGN=CENTER BGCOLOR=#663333><IMG SRC="<?php echo WEBDIR; ?>/images/graypixel.gif" WIDTH=120 HEIGHT=1></TD></TR>\n';
		result += '<TR>\n';
		result += '	<TD COLSPAN=7 ALIGN=CENTER HEIGHT=20>\n';
		if (cal_obj.disabledWeekDays[current_weekday+1]) result += '  <SPAN CLASS="disabledtextlink">'+cal_obj.todayText+'</SPAN>\n';
		else result += '   <A CLASS="textlink" HREF="javascript:;" onClick="javascript: '+windowref+cal_obj.returnFunction+'('+now.getFullYear()+','+(now.getMonth()+1)+','+now.getDate()+'); cal_obj.hideCalendar();">'+cal_obj.todayText+'</A>\n';
		result += '  <BR>\n';
		result += '	</TD></TR></TABLE></CENTER></TD></TR></TABLE>\n';
	}

	// Code common for MONTH, QUARTER, YEAR
	// ------------------------------------
	if (cal_obj.displayType=="month" || cal_obj.displayType=="quarter" || cal_obj.displayType=="year") 
	{
		if (arguments.length > 1) var year = arguments[1];
		else 
		{ 
			if (cal_obj.displayType=="year") var year = now.getFullYear()-cal_obj.yearSelectStartOffset;
  			else var year = now.getFullYear();
		}
		if (cal_obj.displayType!="year" && cal_obj.isShowYearNavigation) 
		{
			result += '<TABLE WIDTH=144 BORDER=0 BORDERWIDTH=0 CELLSPACING=0 CELLPADDING=0 style="font-family:Tahoma; font-size:8pt">\n';
			result += '<TR BGCOLOR="#C0C0C0">\n';
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=22 ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+(year-1)+');">&lt;&lt;</A></B></TD>\n';
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=100 ALIGN=CENTER>'+year+'</TD>\n';
			result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=22 ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+(year+1)+');">&gt;&gt;</A></B></TD>\n';
			result += '</TR></TABLE>\n';
		}
	}
		
	// Code for MONTH display (default)
	// -------------------------------
	if (cal_obj.displayType=="month") 
	{
		// If POPUP, write entire HTML document
		result += '<TABLE WIDTH=120 BORDER=0 CELLSPACING=0 CELLPADDING=0 ALIGN=CENTER style="font-family:Tahoma; font-size:8pt">\n';
		for (var i=0; i<4; i++) 
		{
			result += '<TR>';
			for (var j=0; j<3; j++) 
			{
				var monthindex = ((i*3)+j);
				result += '<TD WIDTH=33% ALIGN=CENTER><A CLASS="textlink" HREF="javascript:;" onClick="javascript: '+windowref+cal_obj.returnMonthFunction+'('+year+','+(monthindex+1)+'); cal_obj.hideCalendar();" CLASS="'+date_class+'">'+cal_obj.monthAbbreviations[monthindex]+'</A></TD>';
			}
			result += '</TR>';
		}
		result += '</TABLE></CENTER></TD></TR></TABLE>\n';
	}
	
	// Code for QUARTER display (default)
	// ----------------------------------
	if (cal_obj.displayType=="quarter") 
	{
		result += '<BR><TABLE WIDTH=120 BORDER=1 CELLSPACING=0 CELLPADDING=0 ALIGN=CENTER style="font-family:Tahoma; font-size:8pt">\n';
		for (var i=0; i<2; i++) 
		{
			result += '<TR>';
			for (var j=0; j<2; j++) 
			{
				var quarter = ((i*2)+j+1);
				result += '<TD WIDTH=50% ALIGN=CENTER><BR><A CLASS="textlink" HREF="javascript:;" onClick="javascript: '+windowref+cal_obj.returnQuarterFunction+'('+year+','+quarter+'); cal_obj.hideCalendar();" CLASS="'+date_class+'">Q'+quarter+'</A><BR><BR></TD>';
			}
			result += '</TR>';
		}
		result += '</TABLE></CENTER></TD></TR></TABLE>\n';
	}

	// Code for YEAR display (default)
	// -------------------------------
	if (cal_obj.displayType=="year") 
	{
		var yearColumnSize = 4;
		result += '<TABLE WIDTH=144 BORDER=0 BORDERWIDTH=0 CELLSPACING=0 CELLPADDING=0 style="font-family:Tahoma; font-size:8pt">\n';
		result += '<TR BGCOLOR="#C0C0C0">\n';
		result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=50% ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+(year-(yearColumnSize*2))+');">&lt;&lt;</A></B></TD>\n';
		result += '	<TD BGCOLOR="#C0C0C0" CLASS="cal" WIDTH=50% ALIGN=CENTER VALIGN=MIDDLE><B><A CLASS="cal" HREF="javascript:;" onClick="'+refresh+"('"+pole+"',"+(year+(yearColumnSize*2))+');">&gt;&gt;</A></B></TD>\n';
		result += '</TR></TABLE>\n';
		result += '<TABLE WIDTH=120 BORDER=0 CELLSPACING=1 CELLPADDING=0 ALIGN=CENTER style="font-family:Tahoma; font-size:8pt">\n';
		for (var i=0; i<yearColumnSize; i++) 
		{
			for (var j=0; j<2; j++) 
			{
				var currentyear = year+(j*yearColumnSize)+i;
				result += '<TD WIDTH=50% ALIGN=CENTER><A CLASS="textlink" HREF="javascript:;" onClick="javascript: '+windowref+cal_obj.returnYearFunction+'('+currentyear+'); cal_obj.hideCalendar();" CLASS="'+date_class+'">'+currentyear+'</A></TD>';
			}
			result += '</TR>';
		}
		result += '</TABLE></CENTER></TD></TR></TABLE>\n';
	}
	tbl.innerHTML = result;
	<? } ?>
}
</script>
</body>
</html>
