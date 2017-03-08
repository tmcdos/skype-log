<?php 
header('Content-type: text/html; charset=utf-8');

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

	unset($_POST);
	$post_input = file_get_contents('php://input');
	if($post_input!='') 
	{
		$js = json_decode($post_input,true);
		if($_SERVER['REMOTE_ADDR']=='10.0.0.5')
		{
//error_log(date('[d-m-Y] (H:i:s) -> ').$post_input.chr(13).chr(10),3,'./skype.log'); 
//error_log(date('[d-m-Y] (H:i:s) -> ').serialize($js).chr(13).chr(10),3,'./skype.log'); 
    }
		unset($post_input);
    if(is_array($js)) foreach($js as $json)
    {
      // list of OBJECT:
      // stamp = YYYY-MM-DD HH:MM:SS
      // last_stamp = Integer
      // body = XHTML string
      // to = skype recipient
      // from = skype sender
      // nick = skype nickname for SQLite database
      // chat_id/file_id = index into SQLite table
      $from = mysql_real_escape_string($json['from']);
      $query = 'INSERT IGNORE INTO user_last(skype_name) VALUES("'.$from.'")';
     	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
     	$query = 'SELECT id FROM user_last WHERE skype_name="'.$from.'"';
     	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
     	$from_id = mysql_result($result,0,0);
  
      $to = mysql_real_escape_string($json['to']);
      $query = 'INSERT IGNORE INTO user_last(skype_name) VALUES("'.$to.'")';
     	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
     	$query = 'SELECT id FROM user_last WHERE skype_name="'.$to.'"';
     	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
     	$to_id = mysql_result($result,0,0);

      $nick = mysql_real_escape_string($json['nick']);
     	if($from == $nick)
     	{
     	  if($json['chat_id']!=0)
     	  {
       	  $query = 'UPDATE user_last SET last_chat='.($json['last_stamp']!=0 ? $json['last_stamp'] : $json['chat_id']).' WHERE id='.$from_id;
         	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
        }
     	  if($json['file_id']!=0)
     	  {
       	  $query = 'UPDATE user_last SET last_file='.($json['last_stamp']!=0 ? $json['last_stamp'] : $json['file_id']).' WHERE id='.$from_id;
         	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
        }
     	}
     	elseif($to == $nick)
     	{
     	  if($json['chat_id']!=0)
     	  {
       	  $query = 'UPDATE user_last SET last_chat='.($json['last_stamp']!=0 ? $json['last_stamp'] : $json['chat_id']).' WHERE id='.$to_id;
         	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
        }
     	  if($json['file_id']!=0)
     	  {
       	  $query = 'UPDATE user_last SET last_file='.($json['last_stamp']!=0 ? $json['last_stamp'] : $json['file_id']).' WHERE id='.$to_id;
         	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
        }
     	}
     	if($json['chat_id']!=0) $body = html_entity_decode($json['body'],ENT_QUOTES,'UTF-8'); 
     	  else $body = $json['body'];  
     	$query = 'INSERT IGNORE INTO msg_log(msg_from,msg_to,stamp,body) VALUES('.$from_id.','.$to_id.',CONVERT_TZ("'.$json['stamp'].'","UTC","Europe/Sofia"),"'.mysql_real_escape_string($body).'")';
     	$result = mysql_query($query,$conn) or trigger_error($query.'<br>'.mysql_error($conn),E_USER_ERROR);
    }
	}

?>