<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artgallery_objects($ciniki) {
	
	$objects = array();
	$objects['exhibition'] = array(
		'name'=>'Exhibition',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_exhibitions',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'location'=>array(),
			'location_id'=>array('default'=>'0'),
			'short_description'=>array(),
			'long_description'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['exhibition_image'] = array(
		'name'=>'Exhibition Image',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_exhibition_images',
		'fields'=>array(
			'exhibition_id'=>array('ref'=>'ciniki.artgallery.exhibition'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'sequence'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['exhibition_link'] = array(
		'name'=>'Exhibition Link',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_exhibition_links',
		'fields'=>array(
			'exhibition_id'=>array('ref'=>'ciniki.artgallery.exhibition'),
			'name'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['exhibition_item'] = array(
		'name'=>'Exhibition Item',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_exhibition_items',
		'fields'=>array(
			'exhibition_id'=>array('ref'=>'ciniki.artgallery.exhibition'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'code'=>array(),
			'name'=>array(),
			'medium'=>array(),
			'size'=>array(),
			'item_condition'=>array(),
			'price'=>array(),
			'fee_percent'=>array(),
			'sell_date'=>array(),
			'sell_price'=>array(),
			'business_fee'=>array(),
			'seller_amount'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['exhibition_tag'] = array(
		'name'=>'Exhibition Tag',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_exhibition_tags',
		'fields'=>array(
			'exhibition_id'=>array('ref'=>'ciniki.artgallery.exhibition'),
			'tag_type'=>array(),
			'tag_name'=>array(),
			'permalink'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_files',
		'fields'=>array(
			'type'=>array(),
			'extension'=>array(),
			'status'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['location'] = array(
		'name'=>'Location',
		'sync'=>'yes',
		'table'=>'ciniki_artgallery_locations',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'address1'=>array(),
			'address2'=>array(),
			'city'=>array(),
			'province'=>array(),
			'postal'=>array(),
			'latitude'=>array(),
			'longitude'=>array(),
			'url'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_artgallery_history',
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Art Gallery Settings',
		'table'=>'ciniki_artgallery_settings',
		'history_table'=>'ciniki_artgallery_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
