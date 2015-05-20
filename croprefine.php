<?php
/*
Plugin Name: CropRefine
Plugin URI: http://wordpress.org/plugins/croprefine/
Description: Giving you greater control over how each of your media item sizes are cropped.
Version: 0.9.5
Author: era404
Author URI: http://www.era404.com
License: GPLv2 or later.
Copyright 2015 ERA404 Creative Group, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/***********************************************************************************
*     Globals
***********************************************************************************/
define('CROPREF_URL', admin_url() . 'admin.php?page=croprefine');

/***********************************************************************************
*     Setup Admin Menus
***********************************************************************************/
add_action( 'admin_init', 'croprefine_admin_init' );
add_action( 'admin_menu', 'croprefine_admin_menu' );
 
function croprefine_admin_init() {
	/* Register our stylesheet. */
	wp_register_style( 'croprefine-styles', plugins_url('croprefine.css', __FILE__) );
	wp_register_style( 'croprefine-cropper-styles', plugins_url('cropper/cropper.css', __FILE__) );
	/* and javascripts */
	wp_enqueue_script( 'croprefine-script', plugins_url('croprefine.js', __FILE__), array('jquery'), 1.0 ); 	// jQuery will be included automatically
	wp_enqueue_script( 'croprefine-cropper-script', plugins_url('cropper/cropper.js', __FILE__), array('jquery'), 1.0 ); 	// jQuery will be included automatically
	wp_localize_script('croprefine-script', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); 	// setting ajaxurl
}
add_action( 'wp_ajax_getimage', 'croprefine_getimage' ); 	//for loading image to refine
add_action( 'wp_ajax_cropimage', 'croprefine_cropimage' ); 	//for refining crop
 
function croprefine_admin_menu() {
	/* Register our plugin page */
	$page = add_menu_page(	'CropRefine', 
							'CropRefine', 
							'manage_options', 
							'croprefine', 
							'croprefine_plugin_options', 
							plugins_url('croprefine/admin_icon.png') );

	/* Using registered $page handle to hook stylesheet loading */
	add_action( 'admin_print_styles-' . $page, 'croprefine_admin_styles' );
	add_action( 'admin_print_scripts-'. $page, 'croprefine_admin_scripts' );
}
 
function croprefine_admin_styles() {	
	wp_enqueue_style( 'croprefine-styles' ); 
	wp_enqueue_style( 'croprefine-cropper-styles' ); 
}
function croprefine_admin_scripts() {	
	wp_enqueue_script( 'croprefine-script' );
	wp_enqueue_script( 'croprefine-cropper-script' ); }
 
function croprefine_plugin_options() {
	
	//uploads
	if(!empty($_FILES)) $results = croprefine_replaceimage();
	
	//successful uploads
	if(isset($_GET['done'])){
		$results = array("err"=>0,"msg"=>"");
		$r = array(	1=>" crop replaced. Uploaded image dimensions matched.<br />Clear your browser's cache and click the image name to see a preview.",
			   		2=>" crop replaced. Image uploaded and resized to match dimensions.<br />Clear your browser's cache and click the image name to see a preview.");
		foreach($r as $k=>$v) if(array_key_exists($k,$_GET['done'])) $results["msg"] .= "{$_GET['done'][$k]} {$r[$k]}<br />";
	}
	
	/* Output our admin page */
	echo "<h1>CropRefine (Beta)</h1>";
	
	if(isset($_GET['item']) && $image = get_post( $_GET['item'] )) {
		//form url 
		$formurl = admin_url()."admin.php?page=croprefine&item={$_GET['item']}";
		
		//javascript to fetch image from uploads directory
		echo "<script type='text/javascript'> var mediaitem = {$image->ID}; </script>";
		
		//path to wp-admin styles
		$styles = admin_url( 'load-styles.php?c=0&amp;dir=ltr&amp;load=media-views');
		
		//build modals
		echo "	
		<div id='modal-cropper' class='media-modal wp-core-ui' style='display:none;'>
				<a href='#' class='media-modal-close modal-cropper-hide'><span class='media-modal-icon'><span class='screen-reader-text'>Close media panel</span></span></a>
				<div class='media-modal-content'><div class='edit-attachment-frame mode-select hide-menu hide-router'>
			<div class='media-frame-title'><h1>Re-Crop / Upload Image</h1></div>
			<div class='media-frame-content'><div tabindex='0' role='checkbox' aria-label='Embedded Image' aria-checked='false' data-id='10' class='attachment-details save-ready'>
			<div class='attachment-media-view landscape'>
				<div class='thumbnail thumbnail-image'>
					
					<div style='width: 500px; height: 500px;'>
						<div class='container' id='cropperimage'>
			  				<img />
					 	</div>
					 </div>
				</div>
			</div>
			<div class='attachment-info'>
				<span class='settings-save-status'>
					<span class='spinner'></span>
					<span class='saved'>Saved.</span>
				</span>
				<div class='details'>
					<form method='post' enctype='multipart/form-data' action='{$formurl}'>
				<table id='available-sizes' class='wp-list-table widefat fixed' style='display: none;'>
					<thead><tr><th>Name</th><th>Size</th><th class='actions'>Actions</th></tr></thead>
					<tbody id='sizes'>
					</tbody>
				</table>
			  </form>
			<div class='compat-meta'>
		
			</div>
		</div>

		
		<div class='actions'>
			<a href='#' class='button button-large modal-cropper-hide' id='cancel'>Cancel</a>
			<a href='#' class='button button-primary button-large' id='savecrop'>Save Crop</a>							
		</div>
		
		<div class='results'>".(isset($results)?
								($results['err']<0?"<strong>Error: </strong>":"<strong>Success: </strong>").$results['msg']:
								"")."
		</div>
	
	</div>
	</div></div>
	</div></div>
		</div>
		<div class='media-modal-backdrop' style='display:none;'></div>
		<div id='popover' class='popover' data-ui='popover-panel'>
			<div id='popover-preview'></div>
			<p><small>150 x 150 (native: 190 x 190)</small></p>
		</div>
		<style type='text/css'>
			.edit-attachment-frame .attachment-media-view, 
			.edit-attachment-frame .attachment-info { width: 50% !important; }
		</style>
		<link media='all' type='text/css' href='{$styles}' rel='stylesheet'>";	
	}
		$medialink = admin_url()."upload.php";
		$pluginurl = plugins_url( 'screenshot-1.png', __FILE__ );
		echo "<div class='instructions'>Browse to your <div class='dashicons-before dashicons-admin-media'> 
				<strong><a href='{$medialink}' title='Go to My Media Library'>Media</a></strong> 
				and select &quot;Refine&quot; by the image you'd like to Crop &amp; Refine.
				<p><a href='{$medialink}' title='Go to My Media Library'><img src='{$pluginurl}' /></a></p>
			  </div>";
		echo <<<PAYPAL
	<div class="donate" style='display: none;'>
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="FPL96ZDKPHR72">
	<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" align="left" class="donate">
		If <b>CropRefine</b> has made your life easier, and you wish to say thank you, a Secure PayPal link has been provided to the left. 
		See more <a href='http://profiles.wordpress.org/era404/' title='WordPress plugins by ERA404' target='_blank'>WordPress plugins by ERA404</a> or visit us online:
		<a href='http://www.era404.com' title='ERA404 Creative Group, Inc.' target='_blank'>www.era404.com</a>. Thanks for using CropRefine.
	</div>
PAYPAL;
	$image_sizes = get_image_sizes();
	echo "<pre>"; print_r($image_sizes);
}

/**************************************************************************************************
*	Add a Button to the Media Library
**************************************************************************************************/
	add_filter('media_row_actions', 'croprefine_media_edit_link', 10, 2);

function croprefine_media_edit_link($actions, $post) {
	//get media link
	$media_link = get_admin_url() . "admin.php?page=croprefine&item=".$post->ID;
    // adding the Action to the Quick Edit row
    $actions['CropRefine'] = "<a href='{$media_link}'>Refine</a>";
    return $actions;    
}

/**************************************************************************************************
*	Ajax Functions
**************************************************************************************************/
function croprefine_getimage() {
	global $wpdb;
	header('Content-type: application/json');
	$uploads = wp_upload_dir();
	
	$image = get_post( $_POST['id'] );
	$imageurl = $image->guid;
	$imagepath = str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
	//echo "\nIMAGE URL: $imageurl\nIMAGE PATH: $imagepath\nIMAGE NAME: {$image->post_name}\n";
	$path = dirname($imagepath)."/";
	$url  = dirname($imageurl) ."/";

	switch($image->post_mime_type){
		case "image/jpeg":
		case "image/jpg":
			$ext = ".jpg"; break;
		case "image/png":
			$ext = ".png"; break;
		case "image/gif":
			$ext = ".gif"; break;
	}
	//open this uploads directory and find matches
	$imagename = str_replace($ext,"",basename($imagepath));
	$regex = '/^' . $imagename . "\-([\d]+)x([\d]+)" . $ext . '$/';
	$imgs = array();
	if ($handle = opendir($path))
	{	while (false !== ($file = readdir($handle)))
		{	preg_match($regex, $file, $match);
			if(!empty($match)&& count($match)==3) $imgs[$file] = $match;
		}
		closedir($handle); ksort($imgs);
	}
	if(!empty($imgs)) $imgs = determineSizes($imgs);

	die(json_encode(array(	"sizes"=> array_values($imgs),
							"path" => $path,
							"url"  => $url,
							"image"=> $imageurl)));
}
//https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
function get_sizes() {
        global $_wp_additional_image_sizes; $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();
        // Create the full array with sizes
        foreach( $get_intermediate_image_sizes as $_size ) {
                if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
                		$w = get_option( $_size . '_size_w' );
                		$h = get_option( $_size . '_size_h' );
                		$c = (bool) get_option( $_size . '_crop' );
						
                } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
                		$w = $_wp_additional_image_sizes[ $_size ]['width'];
                		$h = $_wp_additional_image_sizes[ $_size ]['height'];
                		$c = $_wp_additional_image_sizes[ $_size ]['crop'];
                }
                if($c){ $sizes[$w][$h]=$_size; }
                else { 
                	$sizes['w'][$w] = $_size;
                	$sizes['h'][$h] = $_size;
                }
        }
        if(!empty($sizes)) return $sizes;
        return(array());
}
function determineSizes($imgs){
	$sizes = get_sizes(); //print_r($sizes);
	foreach($imgs as $k=>$img){
		$w = $img[1]; $h = $img[2];
		if(isset($sizes[$w][$h])) { $imgs[$k][3] = $sizes[$w][$h]; }
		else { 	$longest = ($w >= $h ? 'w' : 'h');
			$imgs[$k][3] = (isset($sizes[$longest][$$longest]) ? $sizes[$longest][$$longest] : "unknown");
		}
	}
	
	return $imgs;
	
}

/**************************************************************************************************
*	Crop Image
**************************************************************************************************/
function croprefine_cropimage(){
	header('Content-type: application/json');
	$uploads = wp_upload_dir();
	
	$image = get_post( $_POST['id'] );
	$imageurl = $image->guid;
	$imagepath = str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
	$item = $_POST['cropitem'];
	$crop = $_POST['cropdata'];
	
	//some validation
	if(!file_exists($imagepath)) 									returnerr("File Not Found.");
	if(!isset($item['w']) || !is_numeric($item['w']))				returnerr("Original Width is not valid.");
	if(!isset($item['h']) || !is_numeric($item['h'])) 				returnerr("Original Height is not valid.");
	if(!isset($crop['x']) || !is_numeric($crop['x'])) 				returnerr("Crop X value is not valid.");
	if(!isset($crop['y']) || !is_numeric($crop['y'])) 				returnerr("Crop Y value is not valid.");
	if(!isset($crop['width']) || !is_numeric($crop['width'])) 		returnerr("Crop Width value is not valid.");
	if(!isset($crop['height']) || !is_numeric($crop['height'])) 	returnerr("Crop Height value is not valid.");
	
	//image properties
	$imagetype = getimagesize($imagepath); 
	switch($imagetype['mime']){
		case "image/jpeg":
		case "image/jpg":
			$source = imagecreatefromjpeg($imagepath);
			$ext = ".jpg"; break;
		case "image/png":
			$source = imagecreatefrompng($imagepath);
			$ext = ".png"; break;
		case "image/gif":
			$source = imagecreatefromgif($imagepath);
			$ext = ".gif"; break;
	}
	$sizedpath = str_replace($ext,"-{$item['w']}x{$item['h']}{$ext}",$imagepath);
	$backuppath = str_replace($ext,date(".YmdHi",time()).$ext,$sizedpath);

	//recrop
	$recrop = imagecreatetruecolor( (int) $item['w'], (int) $item['h'] );
	imagecopyresampled(	$recrop, $source, 											//resource $dst_image , resource $src_image 
						0, 0,  														//int $dst_x , int $dst_y ,
						(int) round($crop['x']), (int) round($crop['y']), 			//int $src_x , int $src_y 
						(int) $item['w'], (int) $item['h'], 						//int $dst_w , int $dst_h 
						(int) round($crop['width']), (int) round($crop['height']));//int $src_w , int $src_h
	//backup
	copy($sizedpath,$backuppath);
	// Output
	imagejpeg($recrop, $sizedpath, 100);
	imagedestroy($recrop); imagedestroy($source);
	$item['err'] = (int) 0;
	die( json_encode( $item ));
	
}

function returnerr($err,$die=true){
	$err = array("err"=>-1,"msg"=>$err);
	if($die) die(json_encode($err));
	return $err;
}


//if(!function_exists("myprint_r")){	function myprint_r($in) { echo "<pre>"; print_r($in); echo "</pre>"; return; }}

/**************************************************************************************************
*	Replace Image
**************************************************************************************************/
function croprefine_replaceimage(){
	$newimage = $_FILES['newimage'];

	$uploads = wp_upload_dir();
	
	$image = get_post( $_POST['cropitem']['id'] );
	if(empty($image)) returnerr("File Not Found");
	$imageurl = $image->guid;
	$imagepath = str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
	$item = $_POST['cropitem'];
	$crop = $item['w']."x".$item["h"];
	
	//create temporary image
	$path = dirname($imagepath)."/";
	switch($newimage['type']){
		case "image/jpeg":
		case "image/jpg":
			$ext = ".jpg"; break;
		case "image/png":
			$ext = ".png"; break;
		case "image/gif":
			$ext = ".gif"; break;
	}
	$temppath = "{$path}temp{$ext}";

	//some validation
	if(!file_exists($imagepath)) 									return(returnerr("File Not Found.",false));
	if(!isset($item['w']) || !is_numeric($item['w']))				return(returnerr("Original Width is not valid.",false));
	if(!isset($item['h']) || !is_numeric($item['h'])) 				return(returnerr("Original Height is not valid.",false));
	if(!move_uploaded_file($newimage['tmp_name'],$temppath))		return(returnerr("Couldn't perform the upload.",false));
	
	//image properties
	$imagetype = getimagesize($temppath);
	if($imagetype[0] == $item['w'] &&
	   $imagetype[1] == $item['h']) {
	   		$sizedpath = str_replace($ext,"-{$item['w']}x{$item['h']}{$ext}",$imagepath);
			$backuppath = str_replace($ext,date(".YmdHi",time()).$ext,$sizedpath);
	   		copy($sizedpath,$backuppath);
	   		copy($temppath,$sizedpath);
	   		unlink($temppath);
	   
	   		$pluginurl = admin_url()."admin.php?page=croprefine&item={$_POST['cropitem']['id']}&done[1]={$crop}";
	   		//echo "<script type='text/javascript'>window.location='{$pluginurl}';</script>";
	   		return;
	   }
	   
	//image needs to be resized
	switch($imagetype['mime']){
		case "image/jpeg":
		case "image/jpg":
			$source = imagecreatefromjpeg($temppath);
			$ext = ".jpg"; break;
		case "image/png":
			$source = imagecreatefrompng($temppath);
			$ext = ".png"; break;
		case "image/gif":
			$source = imagecreatefromgif($temppath);
			$ext = ".gif"; break;
	}
	$sizedpath = str_replace($ext,"-{$item['w']}x{$item['h']}{$ext}",$imagepath);
	$backuppath = str_replace($ext,date(".YmdHi",time()).$ext,$sizedpath);

	//resize
	$resize = imagecreatetruecolor( (int) $item['w'], (int) $item['h'] );
	imagecopyresampled(	$resize, $source, 						//resource $dst_image , resource $src_image 
						0, 0,  									//int $dst_x , int $dst_y ,
						0, 0, 									//int $src_x , int $src_y 
						(int) $item['w'], (int) $item['h'], 	//int $dst_w , int $dst_h 
						(int) $imagetype[0], $imagetype[1]);	//int $src_w , int $src_h
	//backup
	copy($sizedpath, $backuppath);
	// Output
	imagejpeg($resize, $sizedpath, 100);
	imagedestroy($resize); imagedestroy($source); unlink($temppath);
	
	//store success to be displayed later
   	$pluginurl = admin_url()."admin.php?page=croprefine&item={$_POST['cropitem']['id']}&done[2]={$crop}";
   	echo "<script type='text/javascript'>window.location='{$pluginurl}';</script>";
   	return;
}
?>