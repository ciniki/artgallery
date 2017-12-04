<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get files for.
//
// Returns
// -------
//
function ciniki_artgallery_fileList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('1','2'), 'name'=>'Type'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.fileList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    
    //
    // Load the list of members for an artgallery
    //
    $strsql = "SELECT ciniki_artgallery_files.id, "
        . "ciniki_artgallery_files.type, "
        . "ciniki_artgallery_files.type AS type_id, "
        . "ciniki_artgallery_files.name, "
        . "ciniki_artgallery_files.description, "
        . "ciniki_artgallery_files.permalink "
        . "FROM ciniki_artgallery_files "
        . "WHERE ciniki_artgallery_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['type']) && $args['type'] != '' ) {
        $strsql .= "AND type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    $strsql .= "ORDER BY type, publish_date DESC, name";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    if( isset($args['type']) && $args['type'] != '' ) {
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                'fields'=>array('id', 'name', 'permalink', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['files']) ) {
            return array('stat'=>'ok', 'files'=>array());
        }
        return array('stat'=>'ok', 'files'=>$rc['files']);
    } 

    //
    // Return the output sorted by types
    //
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'types', 'fname'=>'type', 'name'=>'type',
            'fields'=>array('id'=>'type_id', 'name'=>'type'),
            'maps'=>array(
                'type'=>array(
                    '1'=>'Membership Applications',
                    '2'=>'Newsletters',
                    ),
            )),
        array('container'=>'files', 'fname'=>'id', 'name'=>'file',
            'fields'=>array('id', 'name', 'permalink', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'ok', 'types'=>array());
    }
    return array('stat'=>'ok', 'types'=>$rc['types']);
}
?>
