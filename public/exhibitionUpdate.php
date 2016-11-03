<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the exhibition to.
// name:                The name of the exhibition.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artgallery_exhibitionUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Name'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Flags'), 
        'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'location'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Long Description'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Web Collections'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    
    //
    // Load the business intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Get the old information
    //
    $strsql = "SELECT name, permalink, location_id, DATE_FORMAT(start_date, '%b-%Y') as start_date "
        . "FROM ciniki_artgallery_exhibitions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'exhibition');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['exhibition']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.24', 'msg'=>'That exhibition does not exist'));
    }
    $exhibition = $rc['exhibition'];

    //
    // Check if anything has changed in the permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    if( (!isset($args['permalink']) || $args['permalink'] == '')
        && (isset($args['name']) || isset($args['start_date']) || isset($args['location_id']))
        ) {
        //
        // Start the permalink with the exhibition name
        //
        if( isset($args['name']) ) {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $exhibition['name']);
        }
        //
        // Attached the location permalink to the exhibition permalink
        //
        if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x01) == 1 ) {
            $strsql = "SELECT id, name, permalink "
                . "FROM ciniki_artgallery_locations "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, (isset($args['location_id'])?$args['location_id']:$exhibition['location_id'])) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'location');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['location']['permalink']) && $rc['location']['permalink'] != '' ) {
                $args['permalink'] .= '-' . $rc['location']['permalink'];
            }
        }

        //
        // Check if there is a start date
        //
        if( isset($args['start_date']) ) {
            $dt = new DateTime($args['start_date'], new DateTimeZone($intl_timezone));
            $args['permalink'] .= '-' . $dt->format('M-Y');
        } else {
            $args['permalink'] .= '-' . $exhibition['start_date'];
        }

        //
        // If nothings changed, remove from the args
        //
        if( $args['permalink'] == $exhibition['permalink'] ) {
            unset($args['permalink']);
        }
    }

    //
    // Check the permalink doesn't already exist
    //
    if( isset($args['permalink']) ) {
        $strsql = "SELECT id, name, permalink FROM ciniki_artgallery_exhibitions "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'artgallery');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.25', 'msg'=>'You already have an exhibition with this name, please choose another name.'));
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
    // Update the exhibition
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc =  ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.artgallery.exhibition', $args['exhibition_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.artgallery', 'exhibition_tag', $args['business_id'],
            'ciniki_artgallery_exhibition_tags', 'ciniki_artgallery_history',
            'exhibition_id', $args['exhibition_id'], 10, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
            return $rc;
        }
    }

    //
    // If exhibition was added ok, Check if any web collections to add
    //
    if( isset($args['webcollections'])
        && isset($ciniki['business']['modules']['ciniki.web']) 
        && ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionUpdate');
        $rc = ciniki_web_hooks_webCollectionUpdate($ciniki, $args['business_id'],
            array('object'=>'ciniki.artgallery.exhibition', 'object_id'=>$args['exhibition_id'], 
                'collection_ids'=>$args['webcollections']));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artgallery');
            return $rc;
        }
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
