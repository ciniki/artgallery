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
// name:				The name of the exhibition.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_exhibitionUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Name'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Flags'), 
		'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'),
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
		'location'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Long Description'), 
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	
//	if( isset($args['name']) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
//		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($args['name'])));
//	}

	if( isset($args['name']) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
		error_log('permalink');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
		error_log($args['permalink']);
	}

	//
	// Check the permalink doesn't already exist
	//
	if( isset($args['permalink']) ) {
		$strsql = "SELECT id, name, permalink FROM ciniki_artgallery_exhibitions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'artgallery');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1130', 'msg'=>'You already have an exhibition with this name, please choose another name.'));
		}
	}

	//
	// Update the exhibition
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition', $args['exhibition_id'], $args, 0x07);
}
?>
