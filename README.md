CLIInput
========

Its an easy way to insert text in our CLI projects for Linux servers. 
We can move backward and forward, implement completion, insert text
inside a line and some more things making text input more useful.

	1 - Text from STDIN
	2 - Installation
	3 - Using it
	  3.1 - Callbacks
	  3.2 - Other options
	4 - Key reference
	5 - To-do
	6 - Version
	7 - Updated information

1 - Text from STDIN
====================
We can make interactive CLI programs in PHP, where we ask the user
for some information, this way:

<?php
	$text = fread(STDIN, 100);
?>

But, the problem is when the user wants to go backward in the console, it
will be detected as a new key and the character combination for the left arrow
will be written on the screen (maybe we can edit the text). But we are used
to BASH/DASH or similar stuff, and maybe we want some more options.


2 - Installation
=================
To start using CLIInput in your projects, just include or require the
file CLIInput.class.php:

<?php
	require_once('CLIInput.class.php');
?>

	You must copy this file to your project directory, and it can be located 
wherever you want (some lib directory).

3 - Using it
============
The simple way to start using CLIInput is installing it, creating a 
CLIInput object and then calling the read() method, this way:

<?php
	require_once('CLIInput.class.php');
	$cli = new CLIInput();
	$text = $cli->read();
?>

But, of course you can customize a bit this call, it accepts some arguments:
  * max length of the text, default to 30
  * callbacks, really useful, explained later. (3.1)
  * other options, to customize a little more (3.2)

We can call the read method like this:

<?php
        require_once('CLIInput.class.php');
        $cli = new CLIInput();
        $text = $cli->read(100);
?>

To make the texts longer. But be aware it is not compatible yet with multiline
inputs, so we will get a weird result if the input string it too long and it
requires some lines.

3.1 - Callbacks
========================
CLIInput is able to call external functions or methods when an event 
occurs, for example a key press. We may want this interact with our scripts, 
to achieve better results, for example, implement completion to our input, when
the user presses up or down keys (See key reference: 4)

	To use callbacks is really simple, just enter an array as the second 
argument of read(), where it will have as keys the keyboard's key identifier,
and as value, the name of the function we want to call, or an array containing
(object, method), as call_user_func_array() needs. 

The callbacks function will have 3 arguments:
	* current string: Is the string the user have written until this moment.
	* state : Is an array with the state variables:
		- 'pos' : Cursor position
		- 'insert' : Insert state, true if the user can insert text, false
			     if the text will be replaced. As insert key do.
	* options : Special options for the callback (See key reference: 4)

Also the callbacks response must be an array containig the following:
	* string : New string to write
	* pos : Move cursor to new position
	* end : To force exit and return current text
	* insert : The new insert state.

You don't have to include all these keys into the returned array.

For example:

<?php
        require_once('CLIInput.class.php');

	function completion_up($string, $state, $options)
	{
		return array('string' => strtoupper($string),
			     'pos' => 0);
	}

        $cli = new CLIInput();
        $text = $cli->read(100, array('up' => 'completion_up'));
?>

or we can finish text input without a result (for skipping text fields):

<?php
        require_once('CLIInput.class.php');

        function escape_key($string, $state, $options)
        {
                return array('string' => '',
                             'end' => true);
        }

        $cli = new CLIInput();
        $text = $cli->read(100, array('escape' => 'escape_key'));

?>

3.2 - Other options
============================
At this moment, other options are related to highlights. This class, can
write some events where the text appears, like when we press the Insert key, the
Escape key or we are deleting in an empty string. This argument is an array than 
can contain these keys:
	* 'messageHighlight' : Enable highlighting. Defaults to true.
	* 'escapeHighlight' : Enable highlighting Escape key. Defaults to true.
	* 'insertHighlight' : Enable highlighting Insert key. Defaults to true.
        * 'emptyHighlight' : Enable empty string highlighting. Defaults to true.
        * 'highlightStyle' : What to write when highlighing event. The default 
			     string is " [ \033[1m%s\033[0m ] " wich shows the
			     event name in bold between brackets.
        * 'highlightTime' : Microseconds the message will be on screen before 
			    deleting it. Defaults to 1000000us = 1 second.
        * 'escapeMessage' : Message to show when Escape event is triggered.
			    Defaults to "ESCAPE".
        * 'insertMessage' : Message to show when Insert event is triggered.
			    Defaults to "INSERT".
        * 'emptyMessage' : Message to show when Empty event is triggered.
			   Defaults to "EMPTY",

If we want to skip callbacks array and give options to the read event, just give
NULL value to callbacks argument.

4 - Key reference
==================
These are the keys used in this class. All names are lowercase. Some are
self explanatory and doesn't have additional options (third arguments in callbacks),
so I won't give more details:

	* tab
	* backspace : options array ('oldPos' => (old cursor position))
	* enter : doesn't trigger an event, just make end=true
	* insert 
	* delete : options array ('oldPos' => (old cursor position),
				  'deletedChar' => (deleted character))
	* up
	* down
	* pgup
	* pgdown
	* left : options array ('oldPos' => (old cursor position))
	* right : options array ('oldPos' => (old cursor position))
	* start : options array ('oldPos' => (old cursor position))
	* end : options array ('oldPos' => (old cursor position))
	* escape
	* character : Any written character. 
		      Options array ('character' => (character written))

5 - To-do
==========
There are always things to do with projects. But I have a little list:

	* Enable use of Ctrl+arrows to locate words
	* Enable use of Ctrl+W to delete words
	* Give callbacks the chance to avoid key action. For example, if we
	  are writing a number, avoid letters
	* Multiline inputs
	* Single key inputs for option menus, or S/N inputs

	* Please feel free to suggest anything you consider useful sending me
	  an e-mail, or leaving a message in the blog.

6 - Version
============
This is the first revision of the document, (20130821)

	This document belongs to CLIInput version 0.3

7 - Updated information
========================
I will try to keep github updated, but I feel more comfortable publishing
all changes in my projects blog: http://gaspar.totaki.com/ or explaining some
parts of the code in http://totaki.com/poesiabinaria (in Spanish), so, you may 
find the latest updated information there.

	You can also make bug reports, comments and feature requests in github, 
sending me an e-mail to gaspy [at] totaki [dot] com or writting comments in the blogs.

Thank you, I hope this is useful for you
