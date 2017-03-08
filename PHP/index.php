<?php 
header('Content-type: text/html; charset=utf-8');
session_start();
// set to the user defined error handler
$old_error_handler = set_error_handler('myErrorHandler',E_ALL & ~E_NOTICE | E_STRICT);

$conn = mysql_connect('db.your_domain.com','skype','skype',false,MYSQL_CLIENT_INTERACTIVE) or die ('Cannot connect to MySQL!');
$db = mysql_select_db('skype',$conn);
mysql_query('SET NAMES utf8');

function myErrorHandler ($errno, $errstr, $errfile, $errline, $vars)
{
  // Only handle the errors specified by the error_reporting directive or function
  // Ensure that we should be displaying and/or logging errors
  //if ( ! ($errno & error_reporting ()) || ! (ini_get ('display_errors') || ini_get ('log_errors'))) return;
  if(($errno & (E_NOTICE | E_STRICT)) OR error_reporting()==0) return;

  // define an assoc array of error string
  // in reality the only entries we should
  // consider are 2,8,256,512 and 1024
  $errortype = array (
    1   =>  'Error',
    2   =>  'Warning',
    4   =>  'Parsing Error',
    8   =>  'Notice',
    16  =>  'Core Error',
    32  =>  'Core Warning',
    64  =>  'Compile Error',
    128 =>  'Compile Warning',
    256 =>  'User Error',
    512 =>  'User Warning',
    1024=>  'User Notice',
    2048=>  'Strict Mode',
    4096=>  'Recoverable Error'
    );
  $s = "</table></table></table><br>\n<b>".$errortype[$errno]."</b><br>\n$errstr<br><br>\n\n# $errline, $errfile";
  $s2 = "\n".$errortype[$errno]."\n$errstr\n\n# $errline, $errfile";
	$MAXSTRLEN = 64;
	$s .= '<pre>'; 
	$a = debug_backtrace();
	//array_shift($a);
	$traceArr = array_reverse($a);
	$tabs = 1;
	if(count($traceArr)) foreach($traceArr as $arr)
	{
		if($arr['function']=='myErrorHandler') continue;
		$Line = (isset($arr['line'])? $arr['line'] : "unknown");
		$File = (isset($arr['file'])? str_replace($_GLOBALS['tmpdir'],'',$arr['file']) : "unknown");
		$s.= "\n<br>";
		$s2.= "\n";
		for ($i=0; $i < $tabs; $i++) 
		{
		  $s .= '#';
		  $s2.= '#';
		}
		$s.= ' <b>'.$Line.'</b>, <font color=blue>'.$File."</font>\n<br>";
		$s2.= ' '.$Line.', '.$File."\n";
		for ($i=0; $i < $tabs; $i++) 
		{
		  $s .= ' ';
		  $s2.= ' ';
		}
		$tabs ++;
		$s .= ' ';
		$s2.= ' ';
		if (isset($arr['class'])) 
		{
		  $s .= $arr['class'].'.';
		  $s2.= $arr['class'].'.';
		}
		$args = array();
		if(!empty($arr['args'])) foreach($arr['args'] as $v)
		{
			if (is_null($v)) $args[] = 'NULL';
			elseif (is_array($v)) $args[] = 'Array['.sizeof($v).']'.(sizeof($v)<=5 ? serialize($v) : '');
			elseif (is_object($v)) $args[] = 'Object:'.get_class($v);
			elseif (is_bool($v)) $args[] = $v ? 'true' : 'false';
			else
			{ 
				$v = (string) @$v;
				//$str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
				$str = htmlspecialchars($v);
				//if (strlen($v) > $MAXSTRLEN) $str .= '...';
				$args[] = "\"".$str."\"";
			}
		}
		if(isset($arr['function'])) 
		{
		  $s .= $arr['function'].'('.implode(', ',$args).')';
		  $s2.= $arr['function'].'('.implode(', ',$args).')';
		}
		else 
		{
		  $s .= '[PHP Kernel] ('.implode(', ',$args).')';
		  $s2.= '[PHP Kernel] ('.implode(', ',$args).')';
		}
	}
	$m = mysql_errno();
	if($m) 
	{
	  $s.= chr(13).'<br> MySQL error: '.$m;
	  $s2.= chr(13).' MySQL error: '.$m;
	}
	$s.= '</pre>';
	if($m==2006) return true; // server gone away
	echo $s;
  die;
} 

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Skype History</title>
<meta http-equiv="Content-type" content="text/html;charset=utf-8">
<link rel="stylesheet" type="text/css" href="normal.css">
<SCRIPT LANGUAGE="JavaScript" type="text/javascript" SRC="AnchorPosition.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" type="text/javascript" SRC="date.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" type="text/javascript" SRC="calendar.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" type="text/javascript" SRC="dragiframe.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">bringSelectedIframeToTop(true);</SCRIPT>
</head>
<body><font face="Tahoma">
<IFRAME NAME="opiten" ID="opiten" STYLE="width:150px;height:150px;position:absolute;top:200;left:200;border:0px;display:none" SRC="calendar.php" frameborder="0"></IFRAME>
<form name="report" method="post" action="index.php">
	<table id="sub" align="center" class="log">
		<tr><th colspan="2" align="center">Skype History</th></tr>
		<tr>
			<td><table>
				<tr>
					<td><b>&nbsp;Message From:</b></td>
					<td>&nbsp;</td>
					<td><b>&nbsp;Message To:</b></td>
				</tr>
				<tr>
					<td><select name="chat_from[]" class="combo" size="10" multiple>
<?php
	$query = 'SELECT ID,SKYPE_NAME FROM USER_LAST ORDER BY SKYPE_NAME';
 	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
	while($row = mysql_fetch_array($result,MYSQL_NUM))
	  if($row[1]{0}!='+')
		echo '<option value="'.$row[0].'"'.(is_array($_POST['chat_from']) ? (in_array($row[0],$_POST['chat_from']) ? ' selected' : '') : '').'>'.$row[1].'</option>'.chr(13).chr(10);
?>
					</select></td>
					<td>&nbsp;</td>
					<td><select name="chat_to[]" class="combo" size="10" multiple>
<?php
	$query = 'SELECT ID,SKYPE_NAME FROM USER_LAST ORDER BY SKYPE_NAME';
 	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
	while($row = mysql_fetch_array($result,MYSQL_NUM))
	  if($row[1]{0}!='+')
  	{
  		echo '<option value="'.$row[0].'"'.(is_array($_POST['chat_to']) ? (in_array($row[0],$_POST['chat_to']) ? ' selected' : '') : '').'>'.$row[1].'</option>'.chr(13).chr(10);
  		$users[$row[0]] = $row[1];
  	}
?>
					</select></td>
				</tr>
			</table></td>
			<td><table>
				<tr>
					<td align="right"><font class="regular">From date:</font></td>
					<td><input type="text" size="10" name="beg_date" maxlength="10" value='<?php echo (int)$_POST['beg_date'] ? $_POST['beg_date'] : ''; ?>'></td>
					<td><A onClick="cal1.select(document.report.beg_date,'anchor1x1','dd.MM.yyyy'); return false;" NAME="anchor1x1" ID="anchor1x1">
						<IMG SRC="DownCh.gif" BORDER="0" ALIGN="ABSMIDDLE"></A>
					</td>
				</tr>
				<tr>
					<td align="right"><font class="regular">Until date:</font></td>
					<td><input type="text" size="10" name="end_date" maxlength="10" value='<?php echo (int)$_POST['end_date'] ? $_POST['end_date'] : ''; ?>'></td>
					<td><A onClick="cal1.select(document.report.end_date,'anchor1y1','dd.MM.yyyy'); return false;" NAME="anchor1y1" ID="anchor1y1">
							<IMG SRC="DownCh.gif" BORDER="0" ALIGN="ABSMIDDLE"></A>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td>&nbsp;</td>
					<td align="center"><input type="submit" name="cmdShow" class="button" value="SHOW" onClick="javascript: return Validate(this.form);"></td>
				</tr>
			</table></td>
		</tr>
	</table>
</form>
	<br>
<?php if(isset($_POST['cmdShow']))
{
	$query = 'SELECT STAMP,BODY,F.SKYPE_NAME FROM_USER,T.SKYPE_NAME TO_USER FROM MSG_LOG LEFT JOIN USER_LAST F ON MSG_FROM=F.ID LEFT JOIN USER_LAST T ON MSG_TO=T.ID';
	if(is_array($_POST['chat_from']) AND !is_array($_POST['chat_to'])) $qq[] = 'F.ID IN ('.implode(',',$_POST['chat_from']).')';
	if(is_array($_POST['chat_to']) AND !is_array($_POST['chat_from'])) $qq[] = 'T.ID IN ('.implode(',',$_POST['chat_to']).')';
	if(is_array($_POST['chat_from']) AND is_array($_POST['chat_to'])) 
	{
		if($_POST['chat_from'] == $_POST['chat_to']) $qq[] = '(F.ID IN ('.implode(',',$_POST['chat_from']).') OR T.ID IN ('.implode(',',$_POST['chat_to']).'))';
		else
		$qq[] = '((F.ID IN ('.implode(',',$_POST['chat_from']).') AND T.ID IN ('.implode(',',$_POST['chat_to']).')) 
		OR (T.ID IN ('.implode(',',$_POST['chat_from']).') AND F.ID IN ('.implode(',',$_POST['chat_to']).')))';
	}
	if($_POST['beg_date']!='') $qq[] = 'STAMP>="'.GDate($_POST['beg_date']).' 00:00:00"';
	if($_POST['end_date']!='') $qq[] = 'STAMP<="'.GDate($_POST['end_date']).' 23:59:59"';
	if(is_array($qq)) $query.=' WHERE '.implode(' AND ',$qq);
	$query.= ' ORDER BY STAMP';
 	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
	if (!mysql_num_rows($result)) return;
	echo '<TABLE BORDER="0" CELLSPACING="2" CELLPADDING="1" BGCOLOR="#DDAA99" align="center" style="font-size:10pt">
	  <TR BGCOLOR="#00E099" style="font-weight:bold" ALIGN="CENTER">
	  <TD>Date</TD>
	  <TD>Time</TD>
	  <TD>FROM</TD>
	  <TD>TO</TD>
	  <TD>Message</TD>
	  </TR>'.chr(13).chr(10);
	$i=1;
  $doc = new DOMDocument();
  libxml_use_internal_errors(true);
	while ($row = mysql_fetch_array($result,MYSQL_NUM))
	{
		echo '<TR BGCOLOR="#';
		if($i % 2) echo "B9DAE1";
			else echo "DFE5C3";
		echo '"><TD NOWRAP ALIGN="CENTER">&nbsp;'.ADate($row[0],'-').'&nbsp;</TD>
		  <TD ALIGN="CENTER">&nbsp;'.substr($row[0],11).'&nbsp;</TD>
		  <TD>&nbsp;'.$row[2].'&nbsp;</TD>
		  <TD>&nbsp;'.$row[3].'&nbsp;</TD>';
		if(substr($row[1],0,5)=='<file')
		{
		  $doc->loadHTML($row[1]);
		  $files = $doc->getElementsByTagName('file');
		  unset($msg);
		  foreach($files as $item)
		    $msg.= 'filename = "'.$item->nodeValue.'" ('.$item->getAttribute('size').' bytes)<br>';
		  $msg = substr($msg,0,-4);
		}
		else $msg = str_replace(chr(13),'<br>',$row[1]);
		echo '<TD>&nbsp;'.$msg.'&nbsp;</TD></TR>'.chr(13).chr(10);
		$i++;
	}
	echo '</TABLE>';

} ?>
</font>
<script language="Javascript" type="text/javascript">
		var cal1 = new CalendarPopup('opiten','calendar.php?calo=cal1'); 
		cal1.setWeekStartDay(1);		

function ValidateDate(dat)
{
var arr;
var tmp;

	tmp=dat.value;
	while(tmp.indexOf(" ")>=0) tmp = tmp.replace(" ","");
	while(tmp.indexOf(".")>=0) tmp = tmp.replace(".","-");
	while(tmp.indexOf(":")>=0) tmp = tmp.replace(":","-");
	while(tmp.indexOf("/")>=0) tmp = tmp.replace("/","-");
	if (tmp.indexOf("-")>=0)
	{
		arr = tmp.split("-");
		if (arr.length!=3) return false;
		if ((arr[0]<1)||(arr[0]>31)) return false;
		if ((arr[1]<1)||(arr[1]>12)) return false;
		if (arr[2]<100) arr[2]="20"+arr[2];
		if ((arr[2]<1995)||(arr[2]>2020)) return false;
		if(arr[0].length==1) dat.value="0"+arr[0]; else dat.value=arr[0];
		if(arr[1].length==1) dat.value=dat.value+"-0"+arr[1]; else dat.value=dat.value+"-"+arr[1];
		dat.value=dat.value+"-"+arr[2];
		return true;
	}
	else return false;
}
 
function Compare2Date(dat1,dat2)
{
var arr1;
var arr2;
var val1;
var val2;

	arr1 = dat1.split("-");
	if (arr1.length!=3) return false;
	val1 = new Date(arr1[2],arr1[1]-1,arr1[0]);

	arr2 = dat2.split("-");
	if (arr2.length!=3) return false;
	val2 = new Date(arr2[2],arr2[1]-1,arr2[0]);

	if (val1 < val2) return -1;
	if (val1 == val2) return 0;
	if (val1 > val2) return 1;
}

function Validate(top)
{
	// check if chat_from is selected
	var yes_from = false;
	var elf = top.elements['chat_from[]'];
	for(var i=0;i < elf.options.length; i++)
	{
		if(elf.options[i].selected)
		{
			yes_from = true;
			break;
		}
	}
	// check if chat_to is selected
	var yes_to = false;
	var elt = top.elements['chat_to[]'];
	for(var i=0;i < elt.options.length; i++)
	{
		if(elt.options[i].selected)
		{
			yes_to = true;
			break;
		}
	}
	if(!yes_from && !yes_to)
	{
		alert('You have to specify at least one sender or at least one recipient for messages');
		return false;
	}
	if(top.beg_date.value!="")
	{
		if (!ValidateDate(top.beg_date))
		{
			alert("Invalid start date!");
			return false;
		}
	}
	if(top.end_date.value!="")
	{
		if (!ValidateDate(top.end_date))
		{
			alert("Invalid final date!");
			return false;
		}
	}
	if(Compare2Date(top.beg_date.value,top.end_date.value)==1)
	{
		alert("Start date can not be later than final date!");
		return false;
	}
	return true;
}
</script>
</body>
</html>