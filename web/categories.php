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
function ciniki_artgallery_web_categories($ciniki, $settings, $business_id, $args) {

	//
	// Get the list of category names
	//
	$rsp = array('stat'=>'ok');
	$strsql = "SELECT DISTINCT ciniki_artgallery_exhibition_tags.tag_name AS name, "
		. "ciniki_artgallery_exhibition_tags.permalink "
		. "FROM ciniki_artgallery_exhibition_tags, ciniki_artgallery_exhibitions "
		. "WHERE ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
		. "AND ciniki_artgallery_exhibition_tags.exhibition_id = ciniki_artgallery_exhibitions.id "
		. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
		. "ORDER BY tag_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'categories', 'fname'=>'permalink',
			'fields'=>array('permalink', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['categories']) ) {
		$rsp['categories'] = $rc['categories'];
	}

	return $rsp;
}
?>
