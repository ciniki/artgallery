<?php
//
// Description
// ===========
// This method will add a new file to the files table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the file to.
// type:                The type of file.
//
//                      1 - Membership Application
//                      2 - Exhibitor Application
//
// name:                The name of the file.
// description:         (optional) The extended description of the file, can be much longer than the name.
// webflags:            (optional) How the file is shared with the public and customers.  
//                      The default is the file is public.
//
//                      0x01 - Hidden, unavailable on the website
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_artgallery_fileAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('1', '2'), 'name'=>'Type'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'validlist'=>array('1'), 'name'=>'Status'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Web Flags'), 
        'publish_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Publish Date'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    $name = $args['name'];
    $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.fileAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, permalink FROM ciniki_artgallery_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.26', 'msg'=>'You already have a file with this name, please choose another name'));
    }

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.27', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Make sure a file was submitted
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.28', 'msg'=>'No file specified.'));
    }

    $args['org_filename'] = $_FILES['uploadfile']['name'];
    $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

    //
    // Check the extension is a PDF, currently only accept PDF files
    //
    if( $args['extension'] != 'pdf' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.29', 'msg'=>'The file must be a PDF file.'));
    }

    $args['binary_content'] = file_get_contents($_FILES['uploadfile']['tmp_name']);

    //
    // Add the file
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.artgallery.file', $args, 0x07);
}
?>
