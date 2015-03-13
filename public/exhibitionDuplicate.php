<?php
//
// Description
// ===========
// This method will add a new exhibition to the exhibitions table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the exhibition to.
//
// name:				The name of the exhibition.
// description:			(optional) The extended description of the exhibition, can be much longer than the name.
// webflags:			(optional) How the exhibition is shared with the public and customers.  
//						The default is the exhibition is public.
//
//						0x01 - Hidden, unavailable on the website
//
// start_date:			The start date for the exhibition.
// end_date:			The end date for the exhibition.
// primary_image_id:	The ID of the primary image for the exhibition.
// location:			The location of exhibition. This was designed to be the exhibit room in the gallery.
// short_description:	The short description of the exhibition, for use in lists.
// long_description:	The long description of the exhibition, for use in the details page.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_artgallery_exhibitionDuplicate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'old_exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Web Flags'), 
		'start_date'=>array('required'=>'yes', 'blank'=>'no', 'default'=>'', 'type'=>'date', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'End Date'),
//		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Image'),
		'location'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Location'),
		'location_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Location'),
//        'short_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Short Description'), 
//        'long_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Long Description'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
		'webcollections'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Web Collections'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

//	$name = $args['name'];
//	$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
	$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink "
		. "FROM ciniki_artgallery_exhibitions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'exhibition');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2267', 'msg'=>'You already have a exhibition with this name, please choose another name'));
	}

	//
	// Get the old exhibition details
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'exhibitionLoad');
    $rc = ciniki_artgallery_exhibitionLoad($ciniki, $args['business_id'], $args['old_exhibition_id'], 
		array('images'=>'yes', 'links'=>'yes')); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$exhibition = $rc['exhibition'];

	$args['primary_image_id'] = $exhibition['primary_image_id'];
	$args['short_description'] = $exhibition['short_description'];
	$args['long_description'] = $exhibition['long_description'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artgallery');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the exhibition
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
		return $rc;
	}
	$exhibition_id = $rc['id'];

	//
	// Update the categories
	//
	if( isset($args['categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.artgallery', 'exhibition_tag', $args['business_id'],
			'ciniki_artgallery_exhibition_tags', 'ciniki_artgallery_history',
			'exhibition_id', $exhibition_id, 10, $args['categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
			return $rc;
		}
	}

	//
	// If exhibition was added ok, Check if any web collections to add
	//
	if( isset($args['webcollections'])
		&& isset($ciniki['business']['modules']['ciniki.web']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionUpdate');
		$rc = ciniki_web_hooks_webCollectionUpdate($ciniki, $args['business_id'],
			array('object'=>'ciniki.artgallery.exhibition', 'object_id'=>$exhibition_id, 
				'collection_ids'=>$args['webcollections']));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
			return $rc;
		}
	}

	//
	// Add any links from the old exhibition
	//
	if( isset($exhibition['links']) ) {
		foreach($exhibition['links'] as $link) {
			$link = $link['link'];
			$link['exhibition_id'] = $exhibition_id;
			$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_link', $link, 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
				return $rc;
			}
		}
	}

	//
	// Add any images from the old exhibition
	//
	if( isset($exhibition['images']) ) {
		foreach($exhibition['images'] as $img) {
			$img = $img['image'];
			$img['exhibition_id'] = $exhibition_id;
			$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_image', $img, 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
				return $rc;
			}
		}
	}

	//
	// Add any items from the old exhibition
	//
	if( isset($ciniki['business']['modules']['ciniki.artgallery']['flags'])
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x02) == 0x02
		) {
		$strsql = "SELECT id, "
			. "customer_id, "
			. "code, "
			. "name, "
			. "medium, "
			. "size, "
			. "item_condition, "
			. "price, "
			. "fee_percent, "
			. "sell_date, "
			. "sell_price, "
			. "business_fee, "
			. "seller_amount, "
			. "notes "
			. "FROM ciniki_artgallery_exhibition_items "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['old_exhibition_id']) . "' "
			. "AND sell_date = '0000-00-00 00:00:00' " // Only copy unsold items
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'item');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
			return $rc;
		}
		if( isset($rc['rows']) ) {
			foreach($rc['rows'] as $item) {
				$item['exhibition_id'] = $exhibition_id;
				$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_item', $item, 0x04);
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
					return $rc;
				}
			}
		}
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artgallery');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'artgallery');

	return array('stat'=>'ok', 'id'=>$exhibition_id);
}
?>
