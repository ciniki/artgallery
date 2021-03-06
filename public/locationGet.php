<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the location to.
// location_id:     The ID of the location to get.
//
// Returns
// -------
//
function ciniki_artgallery_locationGet($ciniki) {
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.locationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $rsp = array('stat'=>'ok');
    if( isset($args['location_id']) && $args['location_id'] > 0 ) {
        $strsql = "SELECT ciniki_artgallery_locations.id, "
            . "ciniki_artgallery_locations.name, "
            . "ciniki_artgallery_locations.permalink, "
            . "ciniki_artgallery_locations.address1, "
            . "ciniki_artgallery_locations.address2, "
            . "ciniki_artgallery_locations.city, "
            . "ciniki_artgallery_locations.province, "
            . "ciniki_artgallery_locations.postal, "
            . "ciniki_artgallery_locations.latitude, "
            . "ciniki_artgallery_locations.longitude, "
            . "ciniki_artgallery_locations.url, "
            . "ciniki_artgallery_locations.notes "
            . "FROM ciniki_artgallery_locations "
            . "WHERE ciniki_artgallery_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_artgallery_locations.id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
                'fields'=>array('id', 'name', 'permalink', 'address1', 'address2', 'city', 'province', 'postal', 
                    'latitude', 'longitude', 'url', 'notes')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['locations']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.artgallery.38', 'msg'=>'Unable to find location.'));
        }
        $rsp['location'] = $rc['locations'][0]['location'];
    } else {
        $rsp['location'] = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'address1'=>'',
            'address2'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'latitude'=>'',
            'longitude'=>'',
            'url'=>'',
            'notes'=>'',
            );
    }

    return $rsp;
}
?>
