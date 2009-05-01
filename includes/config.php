<?php

/* 

Fill in the values to the right of the arrows with your own info

The demo only supports files written in & out of Amazon s3

    flixcloud_api_key - Available in the Settings tab of your FC Dashboard
    aws_access_key - Available in your AWS account info
    aws_secret_key - Available in your AWS account info
    ca_bundle_path -  Usually /usr/share/curl/curl-ca-bundle.crt but see your cURL docs
    input_bucket - Just the bucket name, i.e., myinbucket',
    output_bucket - Just the bucket name, i.e., myoutbucket',
    default_playback_video - Not required but if you want the Player to start with a video, enter the video name, for example video.flv
    cloudfront_url - http://your_distro_name.cloudfront.net
    watermark_file - just the filename, i.e., watermark.png. Only required if your recipe uses a watermark

*/

$demo_config = array(
    'flixcloud_api_key'         => '',
    'aws_access_key'            => '',
    'aws_secret_key'            => '',
    'ca_bundle_path'            => '',
    'input_bucket'              => '',
    'output_bucket'             => '',
    'default_playback_video'    => '',
    'cloudfront_url'            => '',
    'watermark_file'            => ''
);

?>