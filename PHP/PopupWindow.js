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
PopupWindow.js
Author: Matt Kruse
Last modified: 3/21/02

Rewritten by IVO GELOV to use IE 5.5+ PopUp windows
Last Modified: 05.09.2004

DESCRIPTION: This object allows you to easily and quickly popup a window
in a certain place. The window can either be a DIV or a separate browser
window.

COMPATABILITY: Works with Netscape 4.x, 6.x, IE 5.x on Windows. Some small
positioning errors - usually with Window positioning - occur on the 
Macintosh platform. Due to bugs in Netscape 4.x, populating the popup 
window with <STYLE> tags may cause errors.

USAGE:
// Create an object for a WINDOW popup
var win = new PopupWindow(); 

// Create an object for a DIV window using the DIV named 'mydiv'
var win = new PopupWindow('mydiv'); 

// Set the window to automatically hide itself when the user clicks 
// anywhere else on the page except the popup
win.autoHide(); 

// Show the window relative to the anchor name passed in
win.showPopup(anchorname);

// Hide the popup
win.hidePopup();

// Set the size of the popup window (only applies to WINDOW popups
win.setSize(width,height);

// Populate the contents of the popup window that will be shown. If you 
// change the contents while it is displayed, you will need to refresh()
win.populate(string);

// set the URL of the window, rather than populating its contents
// manually
win.setUrl("http://www.site.com/");

// Refresh the contents of the popup
win.refresh();

// Specify how many pixels to the right of the anchor the popup will appear
win.offsetX = 50;

// Specify how many pixels below the anchor the popup will appear
win.offsetY = 100;

NOTES:
1) Requires the functions in AnchorPosition.js

2) Your anchor tag MUST contain both NAME and ID attributes which are the 
   same. For example:
   <A NAME="test" ID="test"> </A>

3) There must be at least a space between <A> </A> for IE5.5 to see the 
   anchor tag correctly. Do not do <A></A> with no space.

4) When a PopupWindow object is created, a handler for 'onmouseup' is
   attached to any event handler you may have already defined. Do NOT define
   an event handler for 'onmouseup' after you define a PopupWindow object or
   the autoHide() will not work correctly.
*/ 

function getEl(idVal) 
{
  if (document.getElementById != null) return document.getElementById(idVal);
  if (document.all != null) return document.all[idVal];
	if (document.layers) return document.layers[idVal];
	return null;
}

// Set the position of the popup window based on the anchor
function PopupWindow_getXYPosition(anchorname) 
{
	var coordinates;
	if (this.type == "WINDOW") coordinates = getAnchorWindowPosition(anchorname);
  	else coordinates = getAnchorPosition(anchorname);
	this.x = coordinates.x;
	this.y = coordinates.y;
}

// Set width/height of DIV/popup window
function PopupWindow_setSize(width,height) 
{
	this.width = width;
	this.height = height;
}

// Fill the window with contents
function PopupWindow_populate(contents) 
{
	this.contents = contents;
	this.populated = false;
}

// Set the URL to go to
function PopupWindow_setUrl(url) 
{
	this.url = url;
}

// Set the window popup properties
function PopupWindow_setWindowProperties(props) 
{
	this.windowProperties = props;
}

// Refresh the displayed contents of the popup
function PopupWindow_refresh() 
{
	if (this.divName != null) 
	{
		// refresh the DIV object
		if (document.getElementById) document.getElementById(this.divName).innerHTML = this.contents;
		else if (window.document.all) document.all[this.divName].innerHTML = this.contents;
		else if (window.document.layers)
		{
			var d = document.layers[this.divName]; 
			d.document.open();
			d.document.writeln(this.contents);
			d.document.close();
		}
	}
	else 
	{
		if (this.popupWindow != null && !this.popupWindow.closed) 
		{
			if (this.url!="") this.popupWindow.location.href=this.url;
			else 
			{
				this.popupWindow.document.open();
				this.popupWindow.document.writeln(this.contents);
				this.popupWindow.document.close();
			}
			this.popupWindow.focus();
		}
	}
}

// Position and show the popup, relative to an anchor object
function PopupWindow_showPopup(anchorname) 
{
	this.getXYPosition(anchorname);
	this.x += this.offsetX;
	this.y += this.offsetY;
	if (!this.populated && (this.contents != "")) 
	{
		this.populated = true;
		this.refresh();
	}
	if (this.divName != null) 
	{
		var tbl = getEl('drag_pop');
		var obj = getEl(this.divName);
		var oH = 0;
		var oW = 0;
		if(tbl)
		{
			oH = (tbl.clip ? tbl.clip.height : tbl.offsetHeight) + (document.all ? parseInt(obj.style.borderTopWidth) + parseInt(obj.style.borderBottomWidth) : 0);
			oW = (tbl.clip ? tbl.clip.width : tbl.offsetWidth) + (document.all ? parseInt(obj.style.borderLeftWidth) + parseInt(obj.style.borderRightWidth) : 0);
		}
		// If the popup window will go off-screen, move it so it doesn't
		if ((this.y + oH - document.body.scrollTop) > document.body.clientHeight) this.y = document.body.clientHeight - oH - 16 + document.body.scrollTop;
		if ((this.x + oW - document.body.scrollLeft) > document.body.clientWidth) this.x = document.body.clientWidth - oW - 16 + document.body.scrollLeft;
		if(this.x < 1 || isNaN(this.x)) this.x = 1;
		if(this.y < 1 || isNaN(this.y)) this.y = 1;
		// Show the DIV object
		with(obj.style)
		{
			left = this.x;
			top = this.y;
			width = oW;
			height = oH;
			visibility = "visible";
		}
	}
	else 
	{
		if (this.popupWindow == null || this.popupWindow.closed) 
		{
			// If the popup window will go off-screen, move it so it doesn't
			if (this.x<0) this.x=0;
			if (this.y<0) this.y=0;
			if (screen && screen.availHeight) 
			{
				if ((this.y + this.height) > screen.availHeight) this.y = screen.availHeight - this.height;
			}
			if (screen && screen.availWidth) 
			{
				if ((this.x + this.width) > screen.availWidth) this.x = screen.availWidth - this.width;
			}
			// using IE PopUp
      this.popupWindow = window.createPopup();
     	this.popupWindow.show(this.x,this.y,this.width,this.height);
     	// using normal Window
		//	var avoidAboutBlank = window.opera || ( document.layers && !navigator.mimeTypes['*'] ) || navigator.vendor == 'KDE' || ( document.childNodes && !document.all && !navigator.taintEnabled );
		//	this.popupWindow = window.open(avoidAboutBlank?"":"about:blank","window_"+anchorname,this.windowProperties+",width="+this.width+",height="+this.height+",screenX="+this.x+",left="+this.x+",screenY="+this.y+",top="+this.y+"");
		}
		this.refresh();
	}
}

// Hide the popup
function PopupWindow_hidePopup() 
{
	if (this.divName != null) 
	{
		if (window.document.layers) document.layers[this.divName].visibility = "hidden";
		else if (document.getElementById) document.getElementById(this.divName).style.visibility = "hidden";
		else if (window.document.all) document.all[this.divName].style.visibility = "hidden";
	}
	else 
	{
		if (this.popupWindow && !this.popupWindow.closed) 
		{
			this.popupWindow.hide(); // this.popupWindow.close();
			this.popupWindow = null;
		}
	}
	window.ddEnabled = false;
}

// Pass an event and return whether or not it was the popup DIV that was clicked
function PopupWindow_isClicked(e) 
{
	if (this.divName != null) 
	{
		if (document.layers) 
		{
			var clickX = e.pageX;
			var clickY = e.pageY;
			var t = document.layers[this.divName];
			if ((clickX > t.left) && (clickX < t.left+t.clip.width) && (clickY > t.top) && (clickY < t.top+t.clip.height)) return true;
  			else  return false;
		}
		else if (document.all) 
		{ 
		  // Need to hard-code this to trap IE for error-handling
			var t = window.event.srcElement;
			while (t.parentElement != null) 
			{
				if (t.id==this.divName) return true;
				t = t.parentElement;
			}
			return false;
		}
		else if (document.getElementById && e) 
		{
			var t = e.originalTarget;
			while (t.parentNode != null) 
			{
				if (t.id==this.divName) return true;
				t = t.parentNode;
			}
			return false;
		}
		return false;
	}
	return false;
}

// Check an onMouseDown event to see if we should hide
function PopupWindow_hideIfNotClicked(e) 
{
	if (this.autoHideEnabled && !this.isClicked(e)) this.hidePopup();
}

// Call this to make the DIV disable automatically when mouse is clicked outside it
function PopupWindow_autoHide() 
{
	this.autoHideEnabled = true;
}

// This global function checks all PopupWindow objects onmouseup to see if they should be hidden
function PopupWindow_hidePopupWindows(e) 
{
	window.ddEnabled = false;
	for (var i=0; i<popupWindowObjects.length; i++) 
		if (popupWindowObjects[i] != null) popupWindowObjects[i].hideIfNotClicked(e);
}

function oldMouseUp(e)
{
	window.popupOldMouseUp(e);
	PopupWindow_hidePopupWindows(e);
}

function oldMouseDown(e)
{
	window.popupOldMouseDown(e);
	PopupWindow_drags(e);
}

// Run this immediately to attach the event listener
function PopupWindow_attachListener() 
{
	if (document.layers) document.captureEvents(Event.MOUSEUP);
	window.popupOldMouseUp = document.onmouseup;
	if (window.popupOldMouseUp != null) document.onmouseup = oldMouseUp;
  	else document.onmouseup = PopupWindow_hidePopupWindows;
	window.popupOldMouseDown = document.onmousedown;
	if (window.popupOldMouseDown != null) document.onmousedown = oldMouseDown;
  	else document.onmousedown = PopupWindow_drags;
}

function PopupWindow_move(evt)
{
	if(!window.ddEnabled) return;
	evt = (evt) ? evt : ((window.event) ? window.event : "");
	if(window.drag_divname != null)
	{
		if (document.getElementById) var obj = document.getElementById(window.drag_divname);
		else if (document.all) var obj = document.all[window.drag_divname];
		else if (document.layers) var obj = document.layers[window.drag_divname];
		else return;
		obj.style.left = window.nowX + evt.clientX - window.drag_x;
	  obj.style.top = window.nowY + evt.clientY - window.drag_y;
	  return false;
  }
}

function PopupWindow_drags(evt)
{
	evt = (evt) ? evt : ((window.event) ? window.event : "");
	var isIE = document.all;
	var topDog = isIE ? "BODY" : "HTML";
  hotDog = isIE ? evt.srcElement : evt.target;
  if(hotDog.tagName)
  {
	  while (hotDog.tagName!=topDog || (hotDog.tagName==topDog && hotDog.id && hotDog.id!=window.drag_divname))
	    hotDog = isIE ? hotDog.parentElement : hotDog.parentNode;
	  if (hotDog.id==window.drag_divname)
	  {
			window.drag_x = evt.clientX;
			window.drag_y = evt.clientY;
	    window.nowX = parseInt(hotDog.style.left);
	    window.nowY = parseInt(hotDog.style.top);
			window.ddEnabled = true;
			document.onmousemove = PopupWindow_move;
			return false;
	  }
	}
}

// CONSTRUCTOR for the PopupWindow object
// Pass it a DIV name to use a DHTML popup, otherwise will default to window popup
function PopupWindow() 
{
	window.ddEnabled = false;
	window.drag_x = 0;
	window.drag_y = 0;
	window.nowX = 0;
	window.nowY = 0;
	if (!window.popupWindowIndex) window.popupWindowIndex = 0;
	if (!window.popupWindowObjects) window.popupWindowObjects = new Array();
	if (!window.listenerAttached) 
	{
		window.listenerAttached = true;
		PopupWindow_attachListener();
	}
	this.index = popupWindowIndex++;
	popupWindowObjects[this.index] = this;
	this.divName = null;
	this.popupWindow = null;
	this.width=0;
	this.height=0;
	this.populated = false;
	this.visible = false;
	this.autoHideEnabled = false;
	
	this.contents = "";
	this.url="";
	this.windowProperties="toolbar=no,location=no,status=no,menubar=no,scrollbars=auto,resizable,alwaysRaised,dependent,titlebar=no";
	if (arguments.length>0) 
	{
		this.type="DIV";
		this.divName = arguments[0];
		window.drag_divname = arguments[0];
	}
	else this.type="WINDOW";
	this.use_gebi = false;
	this.use_css = false;
	this.use_layers = false;
	if (document.getElementById) this.use_gebi = true;
	else if (document.all) this.use_css = true;
	else if (document.layers) this.use_layers = true;
	else this.type = "WINDOW";
	this.offsetX = 0;
	this.offsetY = 0;
	// Method mappings
	this.getXYPosition = PopupWindow_getXYPosition;
	this.populate = PopupWindow_populate;
	this.setUrl = PopupWindow_setUrl;
	this.setWindowProperties = PopupWindow_setWindowProperties;
	this.refresh = PopupWindow_refresh;
	this.showPopup = PopupWindow_showPopup;
	this.hidePopup = PopupWindow_hidePopup;
	this.setSize = PopupWindow_setSize;
	this.isClicked = PopupWindow_isClicked;
	this.autoHide = PopupWindow_autoHide;
	this.hideIfNotClicked = PopupWindow_hideIfNotClicked;
}
