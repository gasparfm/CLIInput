<?php
  /**
   *************************************************************
   * @file CLIInput.class.php
   * @brief Easy way to input text in a comfortable way in CLI with
   *        PHP
   *
   * @author Gaspar Fernández <blakeyed@totaki.com>
   *         http://gaspar.totaki.com
   *         http://totaki.com/poesiabinaria (Spanish only)
   * @version 0.3
   * @date 20 ago 2012
   *
   * Changelog:
   *  - 20130820 Bug fixing
   *  - 20130819 Adding some comments
   *  - 20121010 Adding highlights
   *  - 20120930 Adding pgUp / pgDown / escape
   *  - 20120911 Adding callbacks
   *  - 20120820 Starting project
   * 
   *
   *************************************************************/

/* Misc. utils */

/**
 * Searches for a key within an array. If found, returns its value
 * if not, returns the default value
 *
 * @param $array
 * @param $key
 * @param $defVal
 */
function ag($array, $key, $defVal=null)
{
  return (isset($array[$key]))?$array[$key]:$defVal;
}

class CLIInput
{
  private $oldState = null;

  protected $string = null;
  protected $insert = null;
  protected $end = null;
  protected $pos = 0;

  protected $options = array();

  protected $defaultOtherOptions = array('messageHighlight' => true,
					 'escapeHighlight' => true,
					 'insertHighlight' => true,
					 'emptyHighlight' => true,
					 'highlightStyle' => " [ \033[1m%s\033[0m ] ",
					 'highlightTime' => 1000000, /* microseconds */
					 'escapeMessage' => "ESCAPE",
					 'insertMessage' => "INSERT",
					 'emptyMessage' => "EMPTY",);

  /**
   * Constructor
   *
   */
  public function __construct()
  {
  }

  /**
   * Inits text input
   *
   */
  protected function inputInit()
  {
    $this->oldState = `stty -g`; /* Stores last state */
    system('stty -icanon -echo');
  }

  /**
   * Restores tty properties
   *
   */
  protected function inputRestore()
  {
    system('stty '.$this->oldState);
  }

  /**
   * Gets input string as string variable
   *
   * @param $offset
   */
  public function getString($offset)
  {
    $out='';
    $len = count ($this->string);
    for ($i=$offset; $i<$len; $i++)
      $out.=$this->string[$i];

    return $out;
  }

  /**
   * Inserts an string into the string array
   *
   * @param $str
   */
  protected function insertString($str)
  {
    $this->goToBeginning();
    $this->string=array();
    for ($i=0; $i<strlen($str); $i++)
      $this->string[]=$str[$i];

    $this->pos = count($this->string);
    echo $this->getString(0);
  }

  /**
   * Merge callback result array
   *
   * @param $res
   */
  protected function mergeResult($res)
  {
    $str = ag($res, 'string');
    if ($str!==null)
      $this->insertString($str);

    $pos = ag($res, 'pos');
    if ( ($pos) && ($pos>=0) && ($pos<=count($this->string)) )
      {
	$this->goToPos($pos);
      }
    $this->end = ag($res, 'end', false);

    $this->insert = ag($res, 'insert', $this->insert);
  }

  /**
   * Calls external funcion
   *
   * @param $callback
   * @param $local options
   */
  protected function doCallback($callback, $options=null)
  {
    $cb = ag($this->options['callbacks'], $callback);
    if ($cb)
      {
	$currentState = array('pos' => $this->pos,
			      'insert' => $this->insert,
			      );
	$result = call_user_func_array($cb, array($this->getString(0), $currentState, $options));
	$this->mergeResult($result);
      }
  }

  /**
   * Highlights something
   *
   * @param $action to highlight
   */
  protected function doHighlight($action)
  {
    $oOptions = $this->options['other'];
    if (!ag($oOptions, 'messageHighlight'))
      return;
    if (!ag($oOptions, $action.'Highlight', false))
      return;
    $style = ag($oOptions, 'highlightStyle');
    $message = ag($oOptions, $action.'Message');
    if ( (!$style) || (!$message) )
      return;

    $_message = sprintf($style, $message);
    if ($this->pos)
      echo "\033[".($this->pos)."D";
    echo "\033[s\033[K".$_message;
    usleep(ag($oOptions, 'highlightTime', 1000));
    echo "\033[u\033[K".$this->getString(0);
    $this->goToPos($this->pos);
  }
  /**
   * Go to the beginning of the line
   *
   */
  protected function goToBeginning()
  {
    $oldPos = $this->pos;
    if ($this->pos>0)
      {
	echo "\033[D";
	while (--$this->pos)
	  echo "\033[D";
      }
    //    echo "\033[s\033[1;1H".$this->pos." -- ".$oldPos."\033[u";
    $this->doCallback('begin', array('oldPos' => $oldPos));
  }

  /**
   * Go to a specific position
   *
   * @param $pos Go to specific position in the string
   */
  protected function goToPos($pos)
  {
    echo "\033[".$this->pos."D";
    echo "\033[".$pos."C";
    $this->pos=$pos;
  }

  /**
   * Tab key
   *
   */
  protected function key_tab()
  {
    $this->doCallback('tab');
  }

  /**
   * Backspace was pressed
   *
   */
  protected function key_backspace()
  {
    $oldPos = $this->pos;
    if ($this->pos>0)
      {
	echo "\033[D\033[K\033[s";
	array_splice($this->string, --$this->pos, 1);
	echo $this->getString($this->pos);
	echo "\033[u";
      }
    $this->doCallback('backspace', array('oldPos' => $oldPos));
    if (count($this->string)==0)
      $this->doHighlight('empty');
  }

  /**
   * Enter key
   *
   */
  protected function key_enter()
  {
    $this->end = true;
    echo "\n";
  }

  /**
   * Insert key
   *
   */
  protected function key_escape_insert()
  {
    $this->insert=(!$this->insert);
    echo "\033[s\033[0;0H";
    echo ($this->insert)?"ACTIVO":"NO ACTIVO";
    echo "\033[u";
    $this->doCallback('insert');
    $this->doHighlight('insert');
  }

  /**
   * Delete key
   *
   */
  protected function key_escape_delete()
  {
    $oldPos = $this->pos;
    $deletedChar = null;
    if ($this->pos<count($this->string))
      {
	echo "\033[K\033[s";
	$deletedChar = ag($this->string, $this->pos);
	array_splice($this->string, $this->pos, 1);
	echo $this->getString($this->pos);
	echo "\033[u";
      }
    $this->doCallback('delete', array('oldPos' => $oldPos, 'deletedChar' => $deletedChar));
    if (count($this->string)==0)
      $this->doHighlight('empty');
  }

  /**
   * Arrow up
   *
   */
  protected function key_escape_arrow_up()
  {
    $this->doCallback('up');
  }

  /**
   * Arrow down
   *
   */
  protected function key_escape_arrow_down()
  {
    $this->doCallback('down');
  }

  /**
   * Page Up key
   *
   */
  protected function key_escape_pgUp()
  {
    $this->doCallback('pgup');
  }

  /**
   * Page Down key
   *
   */
  protected function key_escape_pgDown()
  {
    $this->doCallback('pgdown');
  }

  /**
   * Left arrow
   *
   */
  protected function key_escape_arrow_left()
  {
    $oldPos = $this->pos;
    if ($this->pos>0)
      {
	echo "\033[D";
	--$this->pos;
      }
    $this->doCallback('left', array('oldPos' => $oldPos));
  }

  /**
   * Right arrow
   *
   */
  protected function key_escape_arrow_right()
  {
    $oldPos = $this->pos;
    if ($this->pos<count($this->string))
      {
	echo "\033[C";
	++$this->pos;
      }
    $this->doCallback('right', array('oldPos' => $oldPos));
  }

  /**
   * Arrows and related keys
   *
   * @param $r
   */
  protected function key_escape_arrows($r)
  {
    $r2=ord(ag($r, 2));
    switch ($r2)
      {
      case 68:		/* backward */
	$this->key_escape_arrow_left();
	break;
      case 67:		/* forward */
	$this->key_escape_arrow_right();
	break;
      case 65: 			/* up */
	$this->key_escape_arrow_up();
	break;
      case 66: 
	$this->key_escape_arrow_down();
	break;
      default: 
	if (ord(ag($r, 3))==126)
	  {
	    switch ($r2)
	      {
	      case 50:
		$this->key_escape_insert();
		break;
	      case 51:
		$this->key_escape_delete();
		break;
	      case 53:
		$this->key_escape_pgUp();
		break;
	      case 54:
		$this->key_escape_pgDown();
		break;
	      }
	  }
      }
  }

  /**
   * Start key
   *
   */
  protected function key_escape_start()
  {
    $oldPos = $this->pos;
    $this->goToBeginning();
    $this->doCallback('start', array('oldPos' => $oldPos));
  }

  /**
   * End key
   *
   */
  protected function key_escape_end()
  {
    $oldPos = $this->pos;
    if ($this->pos<count($this->string))
      {
	if ($this->pos==0)
	  echo "\033[C";

	$this->goToPos(count($this->string));
	/* while (++$this->pos<=count($this->string)) */
      }
    //    echo "\033[s\033[1;1H".$this->pos."\033[u";
    $this->doCallback('end', array('oldPos' => $oldPos));
  }

  /**
   * Start and End keys
   *
   * @param $r
   */
  protected function key_escape_start_end($r)
  {
    switch (ord(ag($r, 2)))
      {
      case 72:	/* Start */
	$this->key_escape_start();
	break;
      case 70:	/* End */
	$this->key_escape_end();
	break;
      }

  }

  /**
   * Brief description
   *
   * @param 
   * @param
   */
  protected function key_escape_simple()
  {
    $this->doCallback('escape');
    $this->doHighlight('escape');
  }

  /**
   * Escape key or preceded by escape
   *
   * @param $r
   */
  protected function key_escape($r)
  {
    switch (ord(ag($r, 1)))
      {
      case 91: $this->key_escape_arrows($r);
	break;
      case 79: $this->key_escape_start_end($r);
	break;
      case null: $this->key_escape_simple();
	break;
      }
  }

  /**
   * Written stuff
   *
   * @param $r Input key
   */
  protected function content_keys($r)
  {
    if (count($this->string)>=$this->options['maxLength'])
      return;

    $newchar=ag($r, 0).ag($r, 1).ag($r, 2).ag($r, 3);
    //    echo "\033[s\033[1;1H\033[K".$r."\033[u";
    if ( ($this->pos==count($this->string)) || (!$this->insert) )
      {
	$this->string[$this->pos++]=$newchar;
	echo $newchar;
      }
    else
      {
	echo "\033[s\033[2;1H\033[K".$this->pos."\033[u";
	array_splice($this->string, $this->pos++, 0, array($newchar));
	echo $newchar."\033[s\033[K".$this->getString($this->pos)."\033[u";
      }
    $this->doCallback('character', array('character' => $newchar));
  }

  /**
   * Compute key
   *
   * @param $r Received key
   */
  protected function computeKey($r)
  {
    switch (ord(ag($r, 0)))
      {
      case 127: $this->key_backspace();
	break;
      case 9: $this->key_tab();
	break;
      case 10: $this->key_enter();
	break;
      case 27: $this->key_escape($r);
	break;
      default: 
	$this->content_keys($r); /* Letters and written stuff */
      }
  }

  /**
   * Reads text from input
   *
   * @param $maxLength
   */
  public function read($maxLength=30, $callbacks=null, $otherOptions=null)
  {
    $this->inputInit();

    $this->options = array('maxLength' => $maxLength,
			   'callbacks' => $callbacks,
			   'other'     => array_merge($this->defaultOtherOptions, (is_array($otherOptions))?$otherOptions:array()));
    /* Our string */
    $this->string = array();

    /* State variables */
    $this->insert = true;

    /* Help variables */
    $this->end = false;
    $this->pos = 0;

    while (!$this->end)
      {
	/* Prepares vectors for select */
	list($read, $write, $except) = array(array(STDIN), NULL, NULL);

	$result=stream_select($read,
			      $write,
			      $except,
			      0, 2000);
	if (!$result)
	  continue;

	$r = fread(STDIN, 6);
	if (!$r)		/* It won't happen */
	  continue;

	$this->computeKey($r);
      }

    $this->inputRestore();
    return $this->getString(0);
  }
}

?>