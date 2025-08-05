<?php

/**
 * @created by Zach <Gmk 177 | Gucci>
 */

/**
 * mysql database hostname
 */
define("MYSQL_HOST", "localhost");
/**
 *  mysql username to connect to the database server
 */
define("MYSQL_USERNAME", "mrdrago4_gucci");
/**
 *  mysql password the password to connect to the database server
 */
define("MYSQL_PASSWORD", "zach1121");
/**
 *  mysql database the database name in which you have your vote table setup
 */
define("MYSQL_DATABASE", "mrdrago4_vote");
/**
 *  vote url this is the url which where users will be sent to on voting
 */
define("VOTE_URL", "#");
/**
 * The number of hours between voting
 */
define("VOTE_HOURS", 24);


/**
 *  connect() this function is used to connect to the mysql database server.
 */
function connect()
{
    if (!@mysql_connect(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD))
        die("Could not connect to mysql database: " . mysql_error());
    if (!@mysql_select_db(MYSQL_DATABASE))
        die("Could not select mysql database: " . mysql_error());
    $tables = mysql_list_tables(MYSQL_DATABASE);
    while (list($temp) = mysql_fetch_array($tables)) {
        if ($temp == "votes") {
            return;
        }
    }
    query("CREATE TABLE `votes` (
        `playerName` VARCHAR( 255 ) NOT NULL ,
        `ip` VARCHAR( 255 ) NOT NULL,
        `time` BIGINT NOT NULL ,
        `recieved` INT( 1 ) NOT NULL DEFAULT '0')");
}
/**
 *  query(string query) this function is used to query the mysql database server.
 */
function query($s)
{
    $query = @mysql_query($s);
    if (!$query)
        die("Error running query('" . $s . "'): " . mysql_error());
    return $query;
}
/**
 *  anti_inject(string text) this function is used to make sure no injections can be made.
 */
function anti_inject($sql)
{
    $sql = preg_replace(sql_regcase("/(from|select|insert|delete|where|drop table|show tables|#|\*|--|\\\\)/"),
        "", $sql);
    $sql = trim($sql);
    $sql = strip_tags($sql);
    $sql = addslashes($sql);
    $sql = strtolower($sql);
    return $sql;
}
/**
 *  clean_request(int timestamp, string username) this function is used to delete any entries if they have already expired.
 */
function clean_request($time, $username)
{
    $query = query("SELECT * FROM `votes` WHERE `playerName`='" . $username . "'");
    if (mysql_num_rows($query) > 0) {
        $row = mysql_fetch_array($query);
        $timerequested = $row['time'];
        if ($time - $timerequested > VOTE_HOURS * 3600)
            query("DELETE FROM `votes` WHERE time='" . $timerequested . "'");
    }
}
/**
 *  vote_entries(string ip) this function is used return the number of rows within the table
 */
function vote_entries($ip)
{
    $query = query("SELECT * FROM `votes` WHERE ip='" . $ip . "'");
    return mysql_num_rows($query);
}


/**
 * This is the actual working of the script please do not touch anything below if you do not know what you are doing...
 */
if (isset($_POST['submit']) || isset($_GET['username']) && isset($_GET['type'])) {
    connect();
    if ($_POST['submit']) {
        if(@fsockopen($_SERVER['REMOTE_ADDR'], 80, $errno, $errstr, 1))
            die("Sorry but you have port 80 open, this is to stop voting by proxy address.");
        if(isset($_COOKIE['voted']))
            die("Sorry but it looks like you have already voted...");
        $username = anti_inject($_POST['username']);
        $ip = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        clean_request(time(), $username);
        if (vote_entries($ip) == 0) {
            setcookie ("voted", "yes", VOTE_HOURS * 3600);
            query("INSERT INTO `votes` (`playerName`, `ip`, `time`) VALUES ('" . $username .
                "', '" . $ip . "', '" . time() . "')");
            header("Location: " . VOTE_URL . "");
        } else {
            die("You have already voted once today.");
        }
    } elseif ($_GET['type'] == "checkvote") {
        $username = anti_inject($_GET['username']);
        $query = query("SELECT * FROM `votes` WHERE `playerName`='" . $username . "'");
        if (mysql_num_rows($query) == 1) {
            $results = mysql_fetch_array($query);
            if ($results['recieved'] == 0) {
                query("UPDATE `votes` SET `recieved`='1' WHERE `playerName`='" . $username . "'");
                die("user needs reward...");
            } else {
                die("user been given reward...");
            }
        } else {
          die("Vote not found... ".  $username .".");
        }
    }
}

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns:IE>

<!-- LeeStrong Runescape Website Source --!>
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=ISO-8859-1"><!-- /Added by HTTrack -->
<head>
<meta http-equiv="Expires" content="0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta name="MSSmartTagsPreventParsing" content="TRUE">
<title>BrainScape - Vote4Cash</title>
<link rel="shortcut icon" href='../img/favicon.ico' />
<link href="../css/basic-3.css" rel="stylesheet" type="text/css" media="all">

<link href="../css/kbase/kbase-2.css" rel="stylesheet" type="text/css" media="all">
<script type="text/javascript">
var link_no=0;
function open_window(url, width, height) {
 var settings="toolbar=0,scrollbars=0";
 if(width && width!=0) settings+=",width=" + width;
 if(height && height!=0) settings+=",height=" + height;
 window.open(url, "popuplink" + link_no++, settings);
}
var subcats=new Array();
function add_subcat(parent, id, name) {
 if(!subcats[parent]) subcats[parent]=new Array();
 var new_option=new Option(name, id);
 subcats[parent][subcats[parent].length]=new_option;
 return new_option;
}
function update_cats(suffix) {
 if(!suffix) suffix="";
 var top_level_select=document.getElementById("search_cat_select" + suffix);
 var subcat_select=document.getElementById("search_subcat_select" + suffix);
 if(!top_level_select || !subcat_select) return;
 if(subcat_select.options)
  for(old in subcat_select.options) subcat_select.options[1]=null;
 to_show=top_level_select.value;
 if(to_show>-1 && subcats[to_show]) {
  for(new_opt=0; new_opt<subcats[to_show].length; new_opt++) {
   subcat_select.options.add(subcats[to_show][new_opt]);
  }
 }
}
</script>
<script type="text/javascript">
new_subcat=add_subcat(775, 880, "About RuneScape");new_subcat=add_subcat(775, 892, "How do I get started?");new_subcat=add_subcat(775, 798, "Controls");new_subcat=add_subcat(775, 795, "Combat");new_subcat=add_subcat(775, 776, "Skills");new_subcat=add_subcat(775, 7, "Quests");new_subcat=add_subcat(775, 1, "Achievement Diary");new_subcat=add_subcat(775, 10, "RuneScape Minigames");new_subcat=add_subcat(775, 831, "Miscellaneous Guides");new_subcat=add_subcat(775, 881, "Area Guides");new_subcat=add_subcat(9, 815, "Advertising");new_subcat=add_subcat(9, 811, "Bank PIN");new_subcat=add_subcat(9, 855, "Bans and mutes");new_subcat=add_subcat(9, 127, "Billing");new_subcat=add_subcat(9, 139, "Bug Reporting");new_subcat=add_subcat(9, 135, "FanSite and Player Contributions");new_subcat=add_subcat(9, 259, "Forums and Your Inbox");new_subcat=add_subcat(9, 123, "Game");new_subcat=add_subcat(9, 260, "Jagex");new_subcat=add_subcat(9, 856, "Members");new_subcat=add_subcat(9, 136, "Moderators");new_subcat=add_subcat(9, 884, "Parents' Guide");new_subcat=add_subcat(9, 828, "Password Support");new_subcat=add_subcat(9, 256, "Reporting Abuse");new_subcat=add_subcat(9, 126, "Technical");new_subcat=add_subcat(827, 883, "The Stronghold of Security");
</script>
<meta name="language" content="en, de">
<meta name="description" content="Taking your first steps in RuneScape.">
<title>How do I get started?</title>
</head>
<body>

<div id="body">
<div style="text-align: center; background: none;">
<div class="titleframe e">
<h4>BrainScape - Vote4Cash</h4>
>> <a href="../index.php">Go Back</a> <<
</div>
</div>
<img class="widescroll-top" src="../img/scroll/backdrop_765_top.gif" alt="" width="765" height="50">
<div class="widescroll">
<div class="widescroll-bgimg">
<div class="widescroll-content">
<h1>Greetings:</h1>
<body>
Hi, my name is Zach (Gmk 177). Today I will be explaining on how you vote and what rewards you receive.<br><br><h4 style="display: inline;">Rewards:</h4><br>Well, let me explain what rewards you get. Once you have voted you will receive: (COMING SOON)!<br><br><h4 style="display: inline;">Rules of Voting:</h4><br>Yes, there are a few rules before you vote.<br><li>Rule #1: You may vote every 24 hours.<br><li>Rule #2: Using a proxy is prohibited.<br><li>Rule #3: Do not close this page until you've completely finished voting.<br><li>Rule #4: DONT ask for higher rewards.(Auto-Ban)</li>----------------------------------------------------------------------------------------------------------------------------<br><h1>How to Vote:</h1>Ok, lets get started.. Please type in your username ingame in the box below. Then once you've done that please click 'Click to Vote', there you will be redirected to another webpage called 'RuneLocus'. Please vote on that page. Once you have completed the following you may go ingame and you will receive (COMING SOON)!<br><br>

<form action="vote.php" method="post">
  <tr>
    <td align="right">Username: </td>
    <td><input name="username" type="text" /></td>
  </tr>
  <tr>
          <td>&nbsp;</td>
    <td align="center"><input type="submit" name="submit" value="Click to Vote" /></td>
  </tr>

</form>
</table></center><br>
</body>
<div class="spacer">
<img src="../img/kbase/scroll_spacer.gif" alt="" style="display: block; text-align: center; margin: 1em auto;">
</div>
<div class="float" style="clear: left;">
</div>
<div class="clear"></div>
</div>
</div>
</div>
<img class="widescroll-bottom" src="../img/scroll/backdrop_765_bottom.gif" alt="" width="765" height="50">

</div>
<div class="tandc">Copyright &copy; 2012 BrainScape - All Rights Reserve<br>
</div>
</div>

</body>

<!-- LeeStrong Runescape Website Source --!>
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=ISO-8859-1"><!-- /Added by HTTrack -->
</html>