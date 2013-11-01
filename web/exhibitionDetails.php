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
function ciniki_artgallery_web_exhibitionDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_artgallery_exhibitions.id, "
		. "ciniki_artgallery_exhibitions.name, "
		. "ciniki_artgallery_exhibitions.location, "
		. "ciniki_artgallery_exhibitions.permalink, "
		. "DATE_FORMAT(start_date, '%b %c, %Y') AS start_date, "
		. "DATE_FORMAT(end_date, '%b %c, %Y') AS end_date, "
		. "ciniki_artgallery_exhibitions.short_description, "
		. "ciniki_artgallery_exhibitions.long_description, "
		. "ciniki_artgallery_exhibitions.primary_image_id, "
		. "ciniki_artgallery_exhibition_images.image_id, "
		. "ciniki_artgallery_exhibition_images.name AS image_name, "
		. "ciniki_artgallery_exhibition_images.permalink AS image_permalink, "
		. "ciniki_artgallery_exhibition_images.description AS image_description, "
		. "ciniki_artgallery_exhibition_images.url AS image_url, "
		. "UNIX_TIMESTAMP(ciniki_artgallery_exhibition_images.last_updated) AS image_last_updated "
		. "FROM ciniki_artgallery_exhibitions "
		. "LEFT JOIN ciniki_artgallery_exhibition_images ON ("
			. "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_images.exhibition_id "
			. "AND (ciniki_artgallery_exhibition_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_artgallery_exhibitions.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		// Check the exhibition is visible on the website
		. "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'exhibitions', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'location', 'permalink', 'image_id'=>'primary_image_id', 
				'description'=>'long_description')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 'short_description', 'url'=>'image_url',
				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['exhibitions']) || count($rc['exhibitions']) < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1119', 'msg'=>'Unable to find exhibition'));
	}
	$exhibition = array_pop($rc['exhibitions']);

	//
	// Get any links for the exhibition
	//
	$strsql = "SELECT id, name, url "
		. "FROM ciniki_artgallery_exhibition_links "
		. "WHERE ciniki_artgallery_exhibition_links.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_artgallery_exhibition_links.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition['id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'links', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'url')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['links']) ) {
		$exhibition['links'] = $rc['links'];
	}

	return array('stat'=>'ok', 'exhibition'=>$exhibition);
}
?>
