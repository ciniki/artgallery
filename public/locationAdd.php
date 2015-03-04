<?php
//
// Description
// ===========
// This method adds a new location to the business for use in exhibitions.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the location to.
//
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_artgallery_locationAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Permalink'), 
		'address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address Line 1'),
		'address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address Line 2'),
		'city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'City'),
		'province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Province'),
		'postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Postal'),
		'latitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Latitude'),
		'longitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Longitude'),
		'url'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'URL'),
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.locationAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
	}

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_artgallery_locations "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'location');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2264', 'msg'=>'You already have a location with this name, please choose another name.'));
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
	// Add the location
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.artgallery.location', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$location_id = $rc['id'];

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

	return array('stat'=>'ok', 'id'=>$location_id);
}
?>
