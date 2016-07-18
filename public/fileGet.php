<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to file belongs to.
// file_id:             The ID of the file to get.
//
// Returns
// -------
//
function ciniki_artgallery_fileGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.fileGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_artgallery_files.id, "
        . "ciniki_artgallery_files.type, "
        . "ciniki_artgallery_files.name, "
        . "ciniki_artgallery_files.permalink, "
        . "ciniki_artgallery_files.webflags, "
        . "IF(ciniki_artgallery_files.webflags&0x01=1,'Hidden','Visible') AS webvisible, "
        . "IFNULL(DATE_FORMAT(publish_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS publish_date, "
        . "ciniki_artgallery_files.description "
        . "FROM ciniki_artgallery_files "
        . "WHERE ciniki_artgallery_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_artgallery_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1099', 'msg'=>'Unable to find file'));
    }
    
    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
