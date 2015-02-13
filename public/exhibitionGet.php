<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the exhibition to.
// exhibition_id:		The ID of the exhibition to get.
//
// Returns
// -------
//
function ciniki_artgallery_exhibitionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
		'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Collections'),
		'sellers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sellers'),
		'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load the business intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_artgallery_exhibitions.id, "
		. "ciniki_artgallery_exhibitions.name, "
		. "ciniki_artgallery_exhibitions.permalink, "
		. "ciniki_artgallery_exhibitions.webflags, "
		. "IF((ciniki_artgallery_exhibitions.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible, "
		. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
		. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
		. "ciniki_artgallery_exhibitions.primary_image_id, "
		. "ciniki_artgallery_exhibitions.location, "
		. "ciniki_artgallery_exhibitions.location_code, "
		. "ciniki_artgallery_exhibitions.short_description, "
		. "ciniki_artgallery_exhibitions.long_description "
		. "FROM ciniki_artgallery_exhibitions "
		. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_artgallery_exhibitions.id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
			'fields'=>array('id', 'name', 'permalink', 'webflags', 'web_visible', 'start_date', 'end_date', 'primary_image_id', 
				'location', 'location_code', 'short_description', 'long_description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['exhibitions']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1118', 'msg'=>'Unable to find exhibition'));
	}
	$exhibition = $rc['exhibitions'][0]['exhibition'];

	//
	// Load images for exhibition if requested
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$strsql = "SELECT id, name, permalink, webflags, image_id, description "
			. "FROM ciniki_artgallery_exhibition_images "
			. "WHERE ciniki_artgallery_exhibition_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_images.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'images', 'fname'=>'id', 'name'=>'image',
				'fields'=>array('id', 'name', 'permalink', 'webflags', 'image_id', 'description')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['images']) ) {
			$exhibition['images'] = $rc['images'];
			//
			// Include the image thumbnails in the returned data
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
			foreach($exhibition['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$exhibition['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		} else {
			$exhibition['images'] = array();
		}
	}

	//
	// Load links for exhibition if requested
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$strsql = "SELECT id, name, url "
			. "FROM ciniki_artgallery_exhibition_links "
			. "WHERE ciniki_artgallery_exhibition_links.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_links.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'links', 'fname'=>'id', 'name'=>'link',
				'fields'=>array('id', 'name', 'url')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['links']) ) {
			$exhibition['links'] = $rc['links'];
		} else {
			$exhibition['links'] = array();
		}
	}

	//
	// Get the list of web collections, and which ones this exhibition is attached to
	//
	if( isset($args['webcollections']) && $args['webcollections'] == 'yes'
		&& isset($ciniki['business']['modules']['ciniki.web']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
		$rc = ciniki_web_hooks_webCollectionList($ciniki, $args['business_id'],
			array('object'=>'ciniki.artgallery.exhibition', 'object_id'=>$args['exhibition_id']));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['collections']) ) {
			$exhibition['_webcollections'] = $rc['collections'];
			$exhibition['webcollections'] = $rc['selected'];
			$exhibition['webcollections_text'] = $rc['selected_text'];
		}
	}

	//
	// Get the list of sellers
	//
	if( isset($args['sellers']) && $args['sellers'] == 'yes' 
		&& isset($ciniki['business']['modules']['ciniki.artgallery']['flags']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x02) == 0x02
		) {
		$strsql = "SELECT ciniki_artgallery_exhibition_items.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS display_name, "
			. "COUNT(ciniki_artgallery_exhibition_items.id) AS num_items, "
			. "SUM(ciniki_artgallery_exhibition_items.price) AS total_price, "
			. "SUM(ciniki_artgallery_exhibition_items.business_fee) AS total_business_fee, "
			. "SUM(ciniki_artgallery_exhibition_items.seller_amount) AS total_seller_amount "
			. "FROM ciniki_artgallery_exhibition_items "
			. "LEFT JOIN ciniki_customers ON ("
				. "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_artgallery_exhibition_items.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
			. "AND ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "GROUP BY ciniki_artgallery_exhibition_items.customer_id "
			. "ORDER BY ciniki_customers.display_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'sellers', 'fname'=>'customer_id', 'name'=>'seller',
				'fields'=>array('id'=>'customer_id', 'display_name', 'num_items',
					'total_price', 'total_business_fee', 'total_seller_amount')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['sellers']) ) {
			$exhibition['sellers'] = array();
		} else {
			$exhibition['sellers'] = $rc['sellers'];
			foreach($exhibition['sellers'] as $sid => $seller) {
				$exhibition['sellers'][$sid]['seller']['total_price'] = numfmt_format_currency($intl_currency_fmt, 
					$seller['seller']['total_price'], $intl_currency);
				$exhibition['sellers'][$sid]['seller']['total_business_fee'] = numfmt_format_currency($intl_currency_fmt, 
					$seller['seller']['total_business_fee'], $intl_currency);
				$exhibition['sellers'][$sid]['seller']['total_seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
					$seller['seller']['total_seller_amount'], $intl_currency);
			}
		}
	}
	
	return array('stat'=>'ok', 'exhibition'=>$exhibition);
}
?>
