<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get exhibitions for.
// type:			The type of participants to get.  Refer to participantAdd for 
//					more information on types.
//
// Returns
// -------
//
function ciniki_artgallery_exhibitionList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'year'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Year'),
		'years'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Years'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'private', 'checkAccess');
    $rc = ciniki_artgallery_checkAccess($ciniki, $args['business_id'], 'ciniki.artgallery.exhibitionList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	
	//
	// If years is specified, then get the distinct years
	//
	if( isset($args['years']) && $args['years'] == 'yes' ) {
		$strsql = "SELECT YEAR(start_date) AS year "
			. "FROM ciniki_artgallery_exhibitions "
			. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY start_date DESC "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'year');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['rows']) ) {
			$years = array();
			foreach($rc['rows'] as $y) {
				if( !isset($args['year']) || $args['year'] == 0 || $args['year'] == '' ) {
					$args['year'] = $y['year'];
				}
				array_unshift($years, $y['year']);
			}
		} else {
			$years = array();
		}
	}

	//
	// Load the list of members for an artgallery
	//
	$strsql = "SELECT ciniki_artgallery_exhibitions.id, "
		. "ciniki_artgallery_exhibitions.name, "
		. "ciniki_artgallery_exhibitions.location, "
		. "ciniki_artgallery_exhibitions.short_description, "
		. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
		. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
		. "ciniki_artgallery_exhibitions.permalink "
		. "FROM ciniki_artgallery_exhibitions "
		. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['year']) && $args['year'] > 0 ) {
		$strsql .= "AND YEAR(start_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
			. "";
	}
	$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name"
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
			'fields'=>array('id', 'name', 'permalink', 'location', 'short_description', 
				'start_date', 'end_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['exhibitions']) ) {
		return array('stat'=>'ok', 'exhibitions'=>array());
	}
	$exhibitions = $rc['exhibitions'];

	//
	// Build response array
	//
	$rsp = array('stat'=>'ok', 'exhibitions'=>$rc['exhibitions']);
	if( isset($args['years']) && $args['years'] == 'yes' ) {
		$rsp['years'] = implode(',', $years);
	} 
	if( isset($args['year']) && $args['year'] != '' ) {
		// Return the year selected, might be auto selected from above
		$rsp['year'] = $args['year'];
	}

	return $rsp;
}
?>
