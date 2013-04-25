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
				'location', 'short_description', 'long_description')),
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
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $img['image']['image_id'], 75);
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
	
	return array('stat'=>'ok', 'exhibition'=>$exhibition);
}
?>
