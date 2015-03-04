<?php
//
// Description
// -----------
// This method will return the list of artgallery for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get artgallery for.
//
// Returns
// -------
//
function ciniki_artgallery_exhibitionItemList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'exhibition_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Exhibition'), 
		'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionItemList');
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
		. "ciniki_artgallery_exhibition_items.medium, "
		. "ciniki_artgallery_exhibition_items.size, "
		. "ciniki_artgallery_exhibition_items.item_condition, "
		. "ciniki_artgallery_exhibition_items.price, "
		. "ciniki_artgallery_exhibition_items.fee_percent, "
		. "DATE_FORMAT(ciniki_artgallery_exhibition_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
		. "ciniki_artgallery_exhibition_items.sell_price, "
		. "ciniki_artgallery_exhibition_items.business_fee, "
		. "ciniki_artgallery_exhibition_items.seller_amount, "
		. "ciniki_artgallery_exhibition_items.notes "
		. "FROM ciniki_artgallery_exhibitions "
		. "LEFT JOIN ciniki_artgallery_exhibition_items ON ("
			. "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_items.exhibition_id "
			. "AND ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
		if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
			$strsql .= "AND ciniki_artgallery_exhibition_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
		}
			$strsql .= ") "
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_artgallery_exhibitions.id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
		. "";
	$strsql .= "ORDER BY ciniki_customers.display_name, code, name "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
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
		foreach($items as $iid => $item) {
			$items[$iid]['item']['fee_percent'] = (float)$item['item']['fee_percent'];
			$items[$iid]['item']['price'] = numfmt_format_currency($intl_currency_fmt, 
				$item['item']['price'], $intl_currency);
			$items[$iid]['item']['sell_price'] = numfmt_format_currency($intl_currency_fmt, 
				$item['item']['sell_price'], $intl_currency);
			$items[$iid]['item']['business_fee'] = numfmt_format_currency($intl_currency_fmt, 
				$item['item']['business_fee'], $intl_currency);
			$items[$iid]['item']['seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
				$item['item']['seller_amount'], $intl_currency);
		}
	}
	$rsp = array('stat'=>'ok', 'items'=>$items);

	//
	// Get the customer details
	//
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
		$rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], 
			array('customer_id'=>$args['customer_id'], 'addresses'=>'yes'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['customer_details'] = $rc['details'];
	}

	return $rsp;
}
?>
