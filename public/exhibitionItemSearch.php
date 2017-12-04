<?php
//
// Description
// -----------
// This method will return the list of artgallery for a tenant.  It is restricted
// to tenant owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get artgallery for.
//
// Returns
// -------
//
function ciniki_artgallery_exhibitionItemSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'),
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['tnid'], 'ciniki.artgallery.exhibitionItemSearch');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the list of exhibition items
    //
    $strsql = "SELECT ciniki_artgallery_exhibition_items.id, "
        . "ciniki_customers.display_name, "
        . "ciniki_artgallery_exhibition_items.code, "
        . "ciniki_artgallery_exhibition_items.name, "
        . "ciniki_artgallery_exhibition_items.flags, "
        . "ciniki_artgallery_exhibition_items.medium, "
        . "ciniki_artgallery_exhibition_items.size, "
        . "ciniki_artgallery_exhibition_items.item_condition, "
        . "ciniki_artgallery_exhibition_items.price, "
        . "ciniki_artgallery_exhibition_items.fee_percent, "
        . "ciniki_artgallery_exhibition_items.notes "
        . "FROM ciniki_artgallery_exhibition_items "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_artgallery_exhibition_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_artgallery_exhibition_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND (ciniki_artgallery_exhibition_items.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_artgallery_exhibition_items.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    $strsql .= "ORDER BY ciniki_customers.display_name, code, name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'results', 'fname'=>'id', 'name'=>'result',
            'fields'=>array('id', 'display_name', 'code', 'name', 'flags',
                'medium', 'size', 'item_condition', 'price', 'fee_percent', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['results']) ) {
        $results = array();
    } else {
        $results = $rc['results'];
        foreach($results as $iid => $item) {
            $results[$iid]['result']['price'] = numfmt_format_currency($intl_currency_fmt, 
                $item['result']['price'], $intl_currency);
            $results[$iid]['result']['fee_percent'] = (float)$item['result']['fee_percent'];
        }
    }
    $rsp = array('stat'=>'ok', 'results'=>$results);

    return $rsp;
}
?>
