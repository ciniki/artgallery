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
		'location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
		'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
		'locations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Locations'),
		'sellers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sellers'),
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	
	$rsp = array('stat'=>'ok');

	//
	// If years is specified, then get the distinct years
	//
	$years = array();
	if( isset($args['years']) && $args['years'] == 'yes' 
		&& (!isset($args['customer_id']) || $args['customer_id'] == 0 || $args['customer_id'] == '') 
		) {
		if( isset($args['category']) && $args['category'] == '--' ) {
			$strsql = "SELECT DISTINCT YEAR(start_date) AS year, ciniki_artgallery_exhibition_tags.tag_name "
				. "FROM ciniki_artgallery_exhibitions "
				. "LEFT JOIN ciniki_artgallery_exhibition_tags ON ("
					. "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_tags.exhibition_id "
					. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
					. "AND ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. ") "
				. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ISNULL(ciniki_artgallery_exhibition_tags.tag_name) "
				. "";
		} elseif( isset($args['category']) && $args['category'] != '' ) {
			$strsql = "SELECT DISTINCT YEAR(start_date) AS year "
				. "FROM ciniki_artgallery_exhibition_tags, ciniki_artgallery_exhibitions "
				. "WHERE ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_artgallery_exhibition_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
				. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
				. "AND ciniki_artgallery_exhibition_tags.exhibition_id = ciniki_artgallery_exhibitions.id "
				. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "";
		} else {
			$strsql = "SELECT DISTINCT YEAR(start_date) AS year "
				. "FROM ciniki_artgallery_exhibitions "
				. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "";
		}
		if( isset($args['location_id']) && $args['location_id'] != '' && $args['location_id'] > 0 ) {
			$strsql .= "AND ciniki_artgallery_exhibitions.location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
				. "";
		}
		$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC "
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
	// Categories
	//
	if( isset($args['categories']) && $args['categories'] == 'yes'
		&& ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0
		) {
		$strsql = "SELECT ciniki_artgallery_exhibition_tags.tag_name AS name, "
			. "ciniki_artgallery_exhibition_tags.permalink, "
			. "COUNT(ciniki_artgallery_exhibition_tags.exhibition_id) AS num_exhibitions "
			. "FROM ciniki_artgallery_exhibition_tags "
			. "WHERE ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
			. "GROUP BY ciniki_artgallery_exhibition_tags.permalink "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'categories', 'fname'=>'permalink', 'name'=>'tag',
				'fields'=>array('permalink', 'name', 'num_exhibitions')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['categories']) ) {
			$rsp['categories'] = $rc['categories'];
		}
		//
		// Check for uncategorized exhibitions
		//
		$strsql = "SELECT ciniki_artgallery_exhibition_tags.tag_name, "
			. "ciniki_artgallery_exhibition_tags.permalink, "
			. "COUNT(ciniki_artgallery_exhibitions.id) AS num_exhibitions "
			. "FROM ciniki_artgallery_exhibitions "
			. "LEFT JOIN ciniki_artgallery_exhibition_tags ON ("
				. "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_tags.exhibition_id "
				. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
				. "AND ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ISNULL(tag_name) "
//			. "GROUP BY ciniki_artgallery_exhibitions.id "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'uncategorized');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['uncategorized']) && $rc['uncategorized']['num_exhibitions'] > 0 ) {
			$rsp['categories'][] = array('tag'=>array('permalink'=>'--', 
				'name'=>'Uncategorized', 
				'num_exhibitions'=>$rc['uncategorized']['num_exhibitions'],
				));
		}
	}

	//
	// Locations
	//
	if( isset($args['locations']) && $args['locations'] == 'yes'
		&& ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x01) > 0
		) {
		$strsql = "SELECT ciniki_artgallery_locations.id, "
			. "ciniki_artgallery_locations.name, "
			. "COUNT(ciniki_artgallery_exhibitions.id) AS num_exhibitions "
			. "FROM ciniki_artgallery_locations "
			. "LEFT JOIN ciniki_artgallery_exhibitions ON ("
				. "ciniki_artgallery_locations.id = ciniki_artgallery_exhibitions.location_id "
				. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "GROUP BY ciniki_artgallery_locations.id "
			. "ORDER BY ciniki_artgallery_locations.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
				'fields'=>array('id', 'name', 'num_exhibitions')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['locations']) ) {
			$rsp['locations'] = $rc['locations'];
		}
	}

	//
	// If a specific location is requested
	//
	if( isset($args['location_id']) && $args['location_id'] != '' && $args['location_id'] > 0 ) {
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
			. "WHERE ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
			return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'2365', 'msg'=>'Unable to find location.'));
		}
		$rsp['location'] = $rc['locations'][0]['location'];
	}


	//
	// If sellers list is requested
	//
	if( isset($args['sellers']) && $args['sellers'] == 'yes' 
		&& ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x02) > 0
		) {
		$strsql = "SELECT ciniki_artgallery_exhibition_items.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS display_name, "
			. "IFNULL(ciniki_customers.first, '') AS first, "
			. "IFNULL(ciniki_customers.last, '') AS last, "
			. "COUNT(DISTINCT ciniki_artgallery_exhibition_items.exhibition_id) AS num_exhibitions "
			. "FROM ciniki_artgallery_exhibition_items "
			. "LEFT JOIN ciniki_customers ON ("
				. "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_items.exhibition_id > 0 "
			. "GROUP BY ciniki_artgallery_exhibition_items.customer_id "
			. "ORDER BY display_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
			array('container'=>'sellers', 'fname'=>'customer_id', 'name'=>'customer',
				'fields'=>array('id'=>'customer_id', 'display_name', 'first', 'last', 'num_exhibitions')),
			));
		if( isset($rc['sellers']) ) {
			$rsp['sellers'] = $rc['sellers'];
		} 
	}

	//
	// Load the list of exhibitions for an artgallery
	//
	$strsql = "SELECT ciniki_artgallery_exhibitions.id, "
		. "ciniki_artgallery_exhibitions.name, ";
	if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0 ) {
		$strsql .= "IFNULL(ciniki_artgallery_locations.name, '') AS location, ";
	} else {
		$strsql .= "ciniki_artgallery_exhibitions.location, ";
	}
	if( isset($args['category']) && $args['category'] == '--' ) {
		$strsql .= "ciniki_artgallery_exhibitions.short_description, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
			. "ciniki_artgallery_exhibitions.permalink, "
			. "ciniki_artgallery_exhibition_tags.tag_name "
			. "FROM ciniki_artgallery_exhibitions "
			. "LEFT JOIN ciniki_artgallery_exhibition_tags ON ("
				. "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_tags.exhibition_id "
				. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
				. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0 ) {
			$strsql .= "LEFT JOIN ciniki_artgallery_locations ON (" 
				. "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
				. "AND ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		}
		$strsql .= "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ISNULL(ciniki_artgallery_exhibition_tags.tag_name) "
			. "";
		if( isset($args['year']) && $args['year'] > 0 ) {
			$strsql .= "AND YEAR(start_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
				. "";
		}
		$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name"
			. "";
	} elseif( isset($args['category']) && $args['category'] != '' ) {
		$strsql .= "ciniki_artgallery_exhibitions.short_description, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
			. "ciniki_artgallery_exhibitions.permalink "
			. "FROM ciniki_artgallery_exhibition_tags "
			. "LEFT JOIN ciniki_artgallery_exhibitions ON ("
				. "ciniki_artgallery_exhibition_tags.exhibition_id = ciniki_artgallery_exhibitions.id "
				. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0 ) {
			$strsql .= "LEFT JOIN ciniki_artgallery_locations ON (" 
				. "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
				. "AND ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		}
		$strsql .= "WHERE ciniki_artgallery_exhibition_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
			. "";
		if( isset($args['year']) && $args['year'] > 0 ) {
			$strsql .= "AND YEAR(start_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
				. "";
		}
		$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name"
			. "";
	} elseif( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql .= "ciniki_artgallery_exhibitions.short_description, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
			. "ciniki_artgallery_exhibitions.permalink "
			. "FROM ciniki_artgallery_exhibition_items "
			. "LEFT JOIN ciniki_artgallery_exhibitions ON ("
				. "ciniki_artgallery_exhibition_items.exhibition_id = ciniki_artgallery_exhibitions.id "
				. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0 ) {
			$strsql .= "LEFT JOIN ciniki_artgallery_locations ON (" 
				. "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
				. "AND ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		}
		$strsql .= "WHERE ciniki_artgallery_exhibition_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_artgallery_exhibition_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
//			. "AND ciniki_artgallery_exhibition_items.exhibition_id = ciniki_artgallery_exhibitions.id "
//			. "AND ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
//		if( isset($args['year']) && $args['year'] > 0 ) {
//			$strsql .= "AND YEAR(start_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
//				. "";
//		}
		$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name"
			. "";
	} else {
		$strsql .= "ciniki_artgallery_exhibitions.short_description, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
			. "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
			. "ciniki_artgallery_exhibitions.permalink "
			. "FROM ciniki_artgallery_exhibitions ";
		if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x01) > 0 ) {
			$strsql .= "LEFT JOIN ciniki_artgallery_locations ON (" 
				. "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
				. "AND ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") ";
		}
		$strsql .= "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		if( isset($args['year']) && $args['year'] > 0 ) {
			$strsql .= "AND YEAR(start_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
				. "";
		}
		if( isset($args['location_id']) && $args['location_id'] != '' && $args['location_id'] > 0 ) {
			$strsql .= "AND location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
				. "";
		}
		$strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name"
			. "";
	}
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
		array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
			'fields'=>array('id', 'name', 'permalink', 'location', 'short_description', 
				'start_date', 'end_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['exhibitions']) ) {
		$rsp['exhibitions'] = $rc['exhibitions'];
	} else {
		$rsp['exhibitions'] = array();
	}

	//
	// Build response array
	//
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
