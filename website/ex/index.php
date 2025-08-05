<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns:IE>

<!-- LeeStrong Runescape Website Source --!>
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=ISO-8859-1"><!-- /Added by HTTrack -->
<head>
<meta http-equiv="Expires" content="0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta name="MSSmartTagsPreventParsing" content="TRUE">
<title>BrainScape - BrainScape Base</title>
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
</head>
<body>

<div id="body">
<div style="text-align: center; background: none;">
<div class="titleframe e">
<h4>BrainScape - BrainScape Base</h4>
>> <a href="http://BrainScape.net/index.php">Go Back</a> <<
</div>
</div>
<img class="widescroll-top" src="../img/scroll/backdrop_765_top.gif" alt="" width="765" height="50">
<div class="widescroll">
<div class="widescroll-bgimg">
<div class="widescroll-content">

<h1 class="homepage">Welcome to the BrainScape Base</h1>
Hello & Welcome to BrainScape Base, here you will find anything you wanna know about BrainScape. You will find a list of category below, you may view/click on one to review your Q&A. Thanks for playing BrainScape!
<div style="position: relative;height: 25em; margin-bottom: 0.5em;">
<div class="navi-box" style="left: 30px;">
<ul class="bigger" style="margin: 0; list-style: none; font-size: 12pt;padding-left: 0;">
<br>
<li><a href="geting-started.php"><img src="../img/kbase/newicons/small_gettingstarted.gif" width="25" height="25" alt="[icon]"></a><a href="geting-started.php">How To Get Started</a></li>
<li><a href="rules.php"><img src="../img/kbase/newicons/small_rules.gif" width="25" height="25" alt="[icon]"></a><a href="rules.php">Rules of BrainScape</a></li>
<li><a href="your-safety.php"><img src="../img/kbase/newicons/small_safety.gif" width="25" height="25" alt="[icon]"></a><a href="your-safety.php">Safety &amp; Security</a></li>
<li><a href="customer-support.php"><img src="../img/kbase/newicons/small_customersupport.gif" width="25" height="25" alt="[icon]"></a><a href="customer-support.php">Customer Support</a></li>
<li><a href="give-us-feedback.php"><img src="../img/kbase/newicons/small_comments.gif" width="25" height="25" alt="[icon]"></a><a href="give-us-feedback.php">Give Us Feed Back :)</a></li>
</ul>
</div>
</div>
<br>
<div class="tandc">Copyright &copy; 2012 BrainScape - All Rights Reserve
</div>
</div>
</div>


</body>

<!-- LeeStrong Runescape Website Source --!>
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=ISO-8859-1"><!-- /Added by HTTrack -->
</html>