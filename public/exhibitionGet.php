<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the exhibition to.
// exhibition_id:       The ID of the exhibition to get.
//
// Returns
// -------
//
function ciniki_artgallery_exhibitionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'sellers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sellers'),
        'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'),
        'locations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Locations'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Collections'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.exhibitionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the exhibition
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'exhibitionLoad');
    $rc = ciniki_artgallery_exhibitionLoad($ciniki, $args['tnid'], $args['exhibition_id'], $args); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $rsp = array('stat'=>'ok', 'exhibition'=>$rc['exhibition']);

    //
    // Check if all tags should be returned
    //
    if( ($ciniki['tenant']['modules']['ciniki.artgallery']['flags']&0x04) > 0
        && isset($args['categories']) && $args['categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.artgallery', $args['tnid'], 
            'ciniki_artgallery_exhibition_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.9', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    //
    // Check if all locations should be returned
    //
    if( ($ciniki['tenant']['modules']['ciniki.artgallery']['flags']&0x01) > 0
        && isset($args['locations']) && $args['locations'] == 'yes' 
        ) {
        $strsql = "SELECT ciniki_artgallery_locations.id, "
            . "ciniki_artgallery_locations.name, "
            . "ciniki_artgallery_locations.city "
            . "FROM ciniki_artgallery_locations "
            . "WHERE ciniki_artgallery_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_artgallery_locations.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
                'fields'=>array('id', 'name', 'city')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['locations']) ) {
            $rsp['locations'] = $rc['locations'];
        }
    }

    return $rsp;
}
?>
