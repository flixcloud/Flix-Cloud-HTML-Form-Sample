<?php
/*
Copyright (c) 2009 On Technologies/On2 Flix Cloud
 
Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:
 
The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, SECURITY AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

This is just a sample, and a very quick & dirty one at that. Very
little error checking, sanitizing, etc. DO NOT put this script on a
public web server unless you know what you're doing.
*/

require('../includes/config.php');


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<title>On2 Flix Cloud Demo Player</title>
<head>
      
      <link href="../css/style.css" media="screen" rel="stylesheet" type="text/css" />

</head>
<body id="bare" class="sessions sessions-new">
    <div id="wrapper" style="width:450px">
        <div id="header">
            <h1 id="logo"><a href="http://www.flixcloud.com"><span>FlixCloud</span></a></h1>
        </div>
        <div id="main">
            <div id="video"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</div>

        <? 
        if (isset($_POST['videoField'])){
            $video = $_POST['videoField'];
            }
            else {$video = $demo_config['default_playback_video'];}
        ?>

        <script type="text/javascript" src="mediaplayer/swfobject.js"></script>
        <script type="text/javascript">
            var s1 = new SWFObject("mediaplayer/player.swf","ply","400","224","9","#FFFFFF");
            s1.addParam("allowfullscreen","true");
            s1.addParam("allowscriptaccess","always");
            s1.addParam("autostart","true");
            s1.addParam("flashvars","file=<?=$demo_config['cloudfront_url']?>/<?=$video?>&autostart=true");
            s1.write("video");
        </script>
            <div style="padding:15px 0">
                <h2>Files in CloudFront</h2>
                <p>Now Playing: <strong><?=$video?></strong></p>
                <p>Play a video:</p>
                <form method="post" action="<? $_SERVER['PHP_SELF'] ?>">

                            <?php
                            if (!class_exists('S3'))require_once('../includes/S3.php');
                            //AWS access info
                            if (!defined('awsAccessKey')) define('awsAccessKey', $demo_config['aws_access_key']);
                            if (!defined('awsSecretKey')) define('awsSecretKey', $demo_config['aws_secret_key']);

                            //instantiate the class
                            $s3 = new S3(awsAccessKey, awsSecretKey);

                            // Get the contents of our bucket  
                            $bucket_contents = $s3->getBucket($demo_config['output_bucket']);  

                            foreach ($bucket_contents as $file){  

                                $fname = $file['name'];  
                                $furl = $demo_config['cloudfront_url'].$fname;  

                                //output a link to the file  
                                    echo '<span class="radio_buttons"><input type="radio" name="videoField" value="'.$fname.'" />'.$fname.'</span><br />';  
                                    }  
                            ?>
                        <p class="clear" style="margin-top: 10px;">
                            <button class="button" name="play" type="submit">Play</button>
                        </p>
                        <p><a href="/">Return to job creator</a></p>
                    </ul>
                </form>
            </div>
        </div>
    </div>
</body>
</html>




