<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_exhibitionLinkDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'exhibition_link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionLinkDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing link information
    //
    $strsql = "SELECT uuid FROM ciniki_artgallery_exhibition_links "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_link_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.19', 'msg'=>'Exhibition link does not exist'));
    }
    $item = $rc['item'];

    //
    // Delete the image
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_link', $args['exhibition_link_id'], $item['uuid'], 0x07);
}
?>
