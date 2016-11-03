<?php
//
// Description
// ===========
// This method returns the pdf of the seller summaries, or
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the seller is attached to.
// 
// Returns
// -------
//
function ciniki_artgallery_exhibitionSellerSummary($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Seller'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionSellerSummary'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Get the exhibition name
    //
    $strsql = "SELECT name "
        . "FROM ciniki_artgallery_exhibitions "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'exhibition');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['exhibition']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.23', 'msg'=>'Market does not exist'));
    }
    $exhibition_name = $rc['exhibition']['name'];

    //
    // Get the list of sellers and their items
    //
    $strsql = "SELECT ciniki_artgallery_exhibition_items.id, "  
        . "ciniki_artgallery_exhibition_items.customer_id, "
        . "IFNULL(ciniki_customers.display_name, '') AS display_name, "
        . "ciniki_artgallery_exhibition_items.id AS item_id, "
        . "ciniki_artgallery_exhibition_items.code, "
        . "ciniki_artgallery_exhibition_items.name, "
        . "ciniki_artgallery_exhibition_items.flags, "
        . "ciniki_artgallery_exhibition_items.medium, "
        . "ciniki_artgallery_exhibition_items.size, "
        . "ciniki_artgallery_exhibition_items.item_condition, "
        . "ciniki_artgallery_exhibition_items.price, "
        . "ciniki_artgallery_exhibition_items.fee_percent, "
        . "DATE_FORMAT(ciniki_artgallery_exhibition_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
        . "ciniki_artgallery_exhibition_items.sell_price, "
        . "ciniki_artgallery_exhibition_items.business_fee, "
        . "ciniki_artgallery_exhibition_items.seller_amount "
        . "FROM ciniki_artgallery_exhibition_items "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_artgallery_exhibition_items.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
        . "AND ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $strsql .= "AND ciniki_artgallery_exhibition_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    }
    $strsql .= "ORDER BY display_name, code, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'sellers', 'fname'=>'customer_id',
            'fields'=>array('id'=>'customer_id', 'display_name')),
        array('container'=>'items', 'fname'=>'item_id',
            'fields'=>array('id'=>'item_id', 'code', 'name', 'flags', 'medium', 'size', 'item_condition', 
                'price', 'fee_percent', 'sell_date', 'sell_price', 'business_fee', 'seller_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sellers']) ) {
        $sellers = array(); 
    } else {
        $sellers = $rc['sellers'];
    }

    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $title = $exhibition_name;
    }

    $today = new DateTime('now', new DateTimeZone($intl_timezone));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'templates', 'sellersummary');
    $rc = ciniki_artgallery_templates_sellersummary($ciniki, $args['business_id'], array(
        'title'=>$exhibition_name,
        'author'=>$business_details['name'],
        'footer'=>$today->format('M d, Y'),
        'sellers'=>$sellers,
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $pdf = $rc['pdf'];

    //
    // Output the pdf
    //
    $filename = $exhibition_name . ' - Summary - ' . $today->format('M d, Y');
    $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    $pdf->Output($filename, 'D');

    return array('stat'=>'exit');
}
?>
