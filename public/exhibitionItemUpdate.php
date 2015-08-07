<?php
//
// Description
// ===========
// This method will update an exhibition in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the exhibition is attached to.
// name:			(optional) The new name of the exhibition.
// url:				(optional) The new URL for the exhibition website.
// description:		(optional) The new description for the exhibition.
// start_date:		(optional) The new date the exhibition starts.  
// end_date:		(optional) The new date the exhibition ends, if it's longer than one day.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_exhibitionItemUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'), 
		'code'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Code'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
		'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
		'medium'=>array('required'=>'no', 'blank'=>'yes', 'trimblanks'=>'yes', 'name'=>'Medium'), 
		'size'=>array('required'=>'no', 'blank'=>'yes', 'trimblanks'=>'yes', 'name'=>'Size'), 
		'item_condition'=>array('required'=>'no', 'blank'=>'yes', 'trimblanks'=>'yes', 'name'=>'Condition'), 
		'price'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Price'), 
		'fee_percent'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Fee Percent'), 
		'sell_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Sell Date'), 
		'sell_price'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Sell Price'), 
		'business_fee'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Business Fee'), 
		'seller_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Seller Amount'), 
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionItemUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
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
	// Update the exhibition in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_item', $args['item_id'], $args, 0x04);
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
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'artgallery');

	return array('stat'=>'ok');
}
?>
