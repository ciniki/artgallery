<?php
//
// Description
// ===========
// This method will remore a file from the club.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to remove the item from.
// exhibition_id:       The ID of the exhibition to remove.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_exhibitionDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the uuid of the artgallery item to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_artgallery_exhibitions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1789', 'msg'=>'Unable to find existing item'));
    }
    $exhibition_uuid = $rc['file']['uuid'];

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
    // Remove any images
    //
    $strsql = "SELECT id, uuid, image_id FROM ciniki_artgallery_exhibition_images "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'image');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $images = $rc['rows'];
        
        foreach($images as $iid => $image) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_image', 
                $image['id'], $image['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
                return $rc; 
            }
        }
    }

    //
    // Remove any links
    //
    $strsql = "SELECT id, uuid FROM ciniki_artgallery_exhibition_links "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'link');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $links = $rc['rows'];
        
        foreach($links as $iid => $link) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition_link', 
                $link['id'], $link['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
                return $rc; 
            }
        }
    }

    //
    // Remove the exhibition from any web collections
    //
    if( isset($ciniki['business']['modules']['ciniki.web']) 
        && ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionDeleteObjRef');
        $rc = ciniki_web_hooks_webCollectionDeleteObjRef($ciniki, $args['business_id'],
            array('object'=>'ciniki.artgallery.exhibition', 'object_id'=>$args['exhibition_id']));
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
            return $rc;
        }
    }

    // 
    // Remove any tags
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsDelete');
    $rc = ciniki_core_tagsDelete($ciniki, 'ciniki.artgallery', 'exhibition_tag', $args['business_id'],
        'ciniki_artgallery_exhibition_tags', 'ciniki_artgallery_history',
        'exhibition_id', $args['exhibition_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
        return $rc;
    }

    //
    // Remove the exhibition
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition', 
        $args['exhibition_id'], $exhibition_uuid, 0x04);
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
