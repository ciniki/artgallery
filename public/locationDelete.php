<?php
//
// Description
// ===========
// This method will remove a location.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to remove the item from.
// location_id:     The ID of the location to remove.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_locationDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'location_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Location'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.locationDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the uuid of the artgallery item to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_artgallery_locations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'location');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['location']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.36', 'msg'=>'Unable to find existing item'));
    }
    $location_uuid = $rc['location']['uuid'];

    //
    // Check for any existing exhibitions at this location
    //
    $strsql = "SELECT 'exhibitions', COUNT(id) "
        . "FROM ciniki_artgallery_exhibitions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.artgallery', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']['exhibitions']) && $rc['num']['exhibitions'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.37', 'msg'=>'There are still exhibitions at this location, they need to be removed or their locations changed before you can remove this location.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artgallery');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the location
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.artgallery.location', 
        $args['location_id'], $location_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'artgallery');

    return array('stat'=>'ok');
}
?>
