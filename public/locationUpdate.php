<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the location to.
// name:				The name of the location.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_locationUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'location_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'), 
		'address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 1'),
		'address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 2'),
		'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'),
		'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'),
		'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal'),
		'latitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latitude'),
		'longitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Longitude'),
		'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'),
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.locationUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	
//	if( isset($args['name']) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
//		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($args['name'])));
//	}

	if( isset($args['name']) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
	}

	//
	// Check the permalink doesn't already exist
	//
	if( isset($args['permalink']) ) {
		$strsql = "SELECT id, name, permalink FROM ciniki_artgallery_locations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'artgallery');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1130', 'msg'=>'You already have an location with this name, please choose another name.'));
		}
	}

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
	// Update the location
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc =  ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.artgallery.location', $args['location_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artgallery');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'artgallery');

	return array('stat'=>'ok');
}
?>
