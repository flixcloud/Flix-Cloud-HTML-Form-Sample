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

require('includes/config.php');

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Flix Cloud Demo Form</title>
    <meta name="author" content="On2 Technologies, Inc.">
    <link href="css/style.css" media="screen" rel="stylesheet" type="text/css" />
    <link href="css/form.css" media="screen" rel="stylesheet" type="text/css" />
</head>

    <body id="bare">
        <div id="wrapper" style="width:440px">
            <div id="header">
                <h1 id="logo"><a href="http://www.flixcloud.com"><span>FlixCloud</span></a></h1>
            </div>
            <div id="main"style="margin:auto;padding:10px">
                <h2>My Transcoding Form</h2>
                <!-- <p>Note: This demo only works with video files stored in Amazon S3.</p> -->
                <p><br /><a href="player/">Play a transcoded file from Amazon Cloudfront</a><p>
                <div id="form-div">
                    
<?php 

include('includes/formbuilder/FormBuilder.php');

//Get recipes

$header[] = 'Content-type: application/xml';
$header[] = 'Accept: application/xml';
$header[] = 'Connection: close';
$url = 'https://www.flixcloud.com/recipes?api_key='.$demo_config['flixcloud_api_key'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
curl_setopt($ch, CURLOPT_CAINFO, $demo_config['ca_bundle_path']);
curl_setopt($ch, CURLOPT_CAPATH, $demo_config['ca_bundle_path']);
$recipes = curl_exec($ch);

curl_close($ch);

if (!class_exists('S3'))require_once('includes/S3.php');
//AWS access info
if (!defined('awsAccessKey')) define('awsAccessKey', $demo_config['aws_access_key']);
if (!defined('awsSecretKey')) define('awsSecretKey', $demo_config['aws_secret_key']);

//instantiate the class
$s3 = new S3(awsAccessKey, awsSecretKey);

// Get the contents of our bucket  
$bucket_contents = $s3->getBucket($demo_config['input_bucket']);

foreach ($bucket_contents as $file){  

    $fname[] = $file['name'];  
    }  

$transcodeForm = new FormBuilder('transcodeForm','Transcode','');

$transcodeForm->addFieldSet('Let\'s transcode a file');

$transcodeForm->addField(array(
        'id' =>   'inputFile',
        'type' => 'select',
        'label' => 'Input file',
        'instructions' => '',
        'required' => true,
        'optionlabels' => $fname,
        'optionvalues' => $fname,
        'invalidvalue' => 'Please select a file'
    )
);
    
$transcodeForm->addField(array(
        'id' => 'outputFile',
        'type' => 'hidden',
        'label' => 'Output File',
        'required' => false,
        'instructions' => 'For example, s3://bucket/file-out.flv',
        'maxlength' => 60,
        'headerinjectioncheck' => 'light'
    )
);

$xml = new SimpleXMLElement($recipes);
    $optionnames[] = "Select a recipe";
    foreach ($xml->recipe as $recipe) {
        $isActive = $recipe->active;
        if ($isActive == "true") {
            $optionnames[] .= "$recipe->name";
    }
}

$transcodeForm->addField(array(
        'id' => 'watermarkFile',
        'type' => 'hidden',
        'label' => 'Watermark File',
        'required' => false,
        'instructions' => 'For example, s3://bucket/watermark.png',
        'maxlength' => 60,
        'headerinjectioncheck' => 'light'
    )
);

$transcodeForm->addField(array(
        'id' => 'recipe',
        'type' => 'select',
        'label' => 'Recipe name',
        'instructions' => '',
        'required' => true,
        'optionlabels' => $optionnames,
        'optionvalues' => $optionnames,
        'invalidvalue' => 'Please select a recipe'
        //'defaultvalue' => 'General enquiry'
    )
);

$transcodeForm->closeFieldSet();

/*if($transcodeForm->isSubmitted()){
       if (!preg_match('/^(s3):\/\/?/i',$_POST['inputFile']) || !preg_match('/^(s3):\/\/?/i',$_POST['outputFile']) || !preg_match('/^(s3):\/\/?/i',$_POST['watermarkFile'])) {
           $transcodeForm->forceErrorMessage('nons3url',"Only S3 URLs are allowed in the form fields");
       }
}*/

if(!$transcodeForm->formSuccess()){
    $transcodeForm->renderForm();
}

$transcodeForm->renderErrorMessage();

if($transcodeForm->formSuccess()){

    // INITIALIZE ALL VARS
    $inputFile = '';
    $outputFile = '';
    $watermarkFile = '';
    $recipe = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST') {  // REQUIRE POST OR DIE
    if(isset($_POST['inputFile'])) $inputFile = $_POST['inputFile'];  
    if(isset($_POST['recipe'])) $recipe = $_POST['recipe'];  
    $outputFile = date('his').".flv";
    $watermarkFile = $demo_config['watermark_file']; 

$job_body = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>
    <api-request>
        <api-key>'.$demo_config['flixcloud_api_key'].'</api-key>
        <recipe-name>$recipe</recipe-name>
        <file-locations>
            <input>
                <url>s3://'.$demo_config['input_bucket'].'/'.$inputFile.'</url>
            </input>
            <output>
                <url>s3://'.$demo_config['output_bucket'].'/'.$outputFile.'</url>
          </output>
          <watermark>
              <url>s3://'.$demo_config['input_bucket'].'/'.$watermarkFile.'</url>
          </watermark>
        </file-locations>
    </api-request>';

    $header2[] = "Content-type: application/xml";
    $header2[] = "Accept: application/xml";
    $url2 = "https://www.flixcloud.com/jobs";

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_POST, 1); // This is a POST job
    curl_setopt($ch2, CURLOPT_URL, $url2); // The request location
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $job_body); // This is the job request xml
    curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch2, CURLOPT_HEADER, 0);  // Do not return http headers
    curl_setopt($ch2, CURLOPT_HTTPHEADER, $header2); // Send the header
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);  // Return the contents of the call
    curl_setopt($ch2, CURLOPT_CAINFO, $demo_config['ca_bundle_path']);
    curl_setopt($ch2, CURLOPT_CAPATH, $demo_config['ca_bundle_path']);
    $job_request = curl_exec($ch2);

    // Create a new simplexml object
    $request_xml = new SimpleXMLElement($job_request);
    // Make sure there's something it in
    if ($request_xml->recipe === false) {
        die('Parsing failed');
    }

echo "<p>Sucessfully submitted job number <strong>".$request_xml->id."</strong>.<br />\n
    <strong>Transcoding...</strong><br />\n
    Input file <strong>".$inputFile."</strong> to output file <strong>".$outputFile."</strong>.<br />\n
    Using recipe <strong>".$recipe."</strong>";

    if($watermarkFile != ""){
        echo " and the watermark file <strong>$watermarkFile</strong>.</p>";
    } else {
        echo ".</p>";
    }
    
     if (curl_errno($ch2)) {
        $job_request = 'ERROR -> ' . curl_errno($ch2) . ': ' . curl_error($ch2);
    } else {
        $returnCode = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        switch($returnCode){
            case 404:
                $job_request = 'ERROR - 404 Not Found';break;
            case 401:
                $job_request = 'ERROR - Access denied (401). Probably a bad API key.';break;
            case 500:
                $job_request = 'ERROR - The server returned an error (500). The API may be down.';break;
            default:
                $job_request = 'Unknown error';break;
        }
    }
    curl_close($ch2);
    } 

    else die('Sorry, something went horribly, horribly wrong');

}

?>

                </div>
            </div>
        </div>
    </body>
</html>
