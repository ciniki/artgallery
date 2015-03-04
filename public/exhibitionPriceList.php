<?php
//
// Description
// ===========
// This method will return all the information about an exhibition.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the seller is attached to.
// exhibition_id:		The ID of the exhibition to get the details for.
// 
// Returns
// -------
//
function ciniki_artgallery_exhibitionPriceList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'),
		'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Seller'),
		'output'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Format'),
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
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionPriceList'); 
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2235', 'msg'=>'Exhibition does not exist'));
	}
	$exhibition_name = $rc['exhibition']['name'];

	//
	// Get the list of exhibition items
	//
	$strsql = "SELECT ciniki_artgallery_exhibition_items.id, "
		. "ciniki_artgallery_exhibition_items.code, "
		. "IFNULL(ciniki_customers.display_name, '') AS display_name, "
		. "ciniki_artgallery_exhibition_items.name, "
		. "ciniki_artgallery_exhibition_items.medium, "
		. "ciniki_artgallery_exhibition_items.size, "
		. "ciniki_artgallery_exhibition_items.item_condition, "
		. "ciniki_artgallery_exhibition_items.price, "
		. "ciniki_artgallery_exhibition_items.fee_percent, "
		. "DATE_FORMAT(ciniki_artgallery_exhibition_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
		. "sell_price, business_fee, seller_amount, "
		. "ciniki_artgallery_exhibition_items.notes "
		. "FROM ciniki_artgallery_exhibition_items "
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_artgallery_exhibition_items.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
		. "";
	if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
		$strsql .= "AND ciniki_artgallery_exhibition_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	}
	$strsql .= "ORDER BY code, name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'items', 'fname'=>'id',
			'fields'=>array('id', 'display_name', 'code', 'name', 'medium', 'size', 'item_condition', 
				'price', 'fee_percent', 'sell_date', 'sell_price', 'business_fee', 'seller_amount', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		$items = array();	
	} else {
		$items = $rc['items'];
	}

	$today = new DateTime('now', new DateTimeZone($intl_timezone));

	ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'templates', 'pricelist');
	$rc = ciniki_artgallery_templates_pricelist($ciniki, $args['business_id'], array(
		'title'=>$exhibition_name . ' - Price List',
		'author'=>$business_details['name'],
		'footer'=>$today->format('M d, Y'),
		'items'=>$items,
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$pdf = $rc['pdf'];

	//
	// Output the pdf
	//
	$filename = $exhibition_name . ' - Price List - ' . $today->format('M d, Y');
	$filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
	$pdf->Output($filename, 'D');

	return array('stat'=>'exit');
}
?>
