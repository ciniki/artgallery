<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artgallery_web_exhibitionDetails($ciniki, $settings, $business_id, $permalink) {

    $strsql = "SELECT ciniki_artgallery_exhibitions.id, "
        . "ciniki_artgallery_exhibitions.name, "
        . "ciniki_artgallery_exhibitions.location, "
        . "ciniki_artgallery_exhibitions.location_id, "
        . "ciniki_artgallery_exhibitions.permalink, "
        . "DATE_FORMAT(start_date, '%b %e, %Y') AS start_date, "
        . "DATE_FORMAT(end_date, '%b %e, %Y') AS end_date, "
        . "DATE_FORMAT(start_date, '%M') AS start_month, "
        . "DATE_FORMAT(start_date, '%D') AS start_day, "
        . "DATE_FORMAT(start_date, '%Y') AS start_year, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%M')) AS end_month, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%D')) AS end_day, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%Y')) AS end_year, "
        . "ciniki_artgallery_exhibitions.short_description, "
        . "ciniki_artgallery_exhibitions.long_description, "
        . "ciniki_artgallery_exhibitions.primary_image_id, "
        . "ciniki_artgallery_exhibition_images.image_id, "
        . "ciniki_artgallery_exhibition_images.name AS image_name, "
        . "ciniki_artgallery_exhibition_images.permalink AS image_permalink, "
        . "IF((ciniki_artgallery_exhibition_images.flags&0x01)=1, 'yes', 'no') AS image_sold, "
        . "ciniki_artgallery_exhibition_images.description AS image_description, "
        . "ciniki_artgallery_exhibition_images.url AS image_url, "
        . "UNIX_TIMESTAMP(ciniki_artgallery_exhibition_images.last_updated) AS image_last_updated "
        . "FROM ciniki_artgallery_exhibitions "
        . "LEFT JOIN ciniki_artgallery_exhibition_images ON ("
            . "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_images.exhibition_id "
            . "AND (ciniki_artgallery_exhibition_images.webflags&0x01) = 0 "
            . ") "
        . "WHERE ciniki_artgallery_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_artgallery_exhibitions.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        // Check the exhibition is visible on the website
        . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'exhibitions', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'location', 'location_id', 'permalink', 'image_id'=>'primary_image_id', 
                'start_month', 'start_day', 'start_year', 'end_month', 'end_day', 'end_year',
                'description'=>'long_description')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'short_description', 'sold'=>'image_sold', 'url'=>'image_url',
                'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['exhibitions']) || count($rc['exhibitions']) < 1 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1119', 'msg'=>'Unable to find exhibition'));
    }
    $exhibition = array_pop($rc['exhibitions']);

    //
    // Get the location for the exhibition
    //
    if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x01) > 0 
        && $exhibition['location_id'] > 0 
        ) {
        $strsql = "SELECT ciniki_artgallery_locations.name, "   
            . "ciniki_artgallery_locations.address1, "  
            . "ciniki_artgallery_locations.address2, "  
            . "ciniki_artgallery_locations.city, "  
            . "ciniki_artgallery_locations.province, "  
            . "ciniki_artgallery_locations.postal, "    
            . "ciniki_artgallery_locations.latitude, "  
            . "ciniki_artgallery_locations.longitude, " 
            . "ciniki_artgallery_locations.url "    
            . "FROM ciniki_artgallery_locations "
            . "WHERE ciniki_artgallery_locations.id = '" . ciniki_core_dbQuote($ciniki, $exhibition['location_id']) . "' "
            . "AND ciniki_artgallery_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'location');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['location']) ) {
            $exhibition['location_details'] = $rc['location'];
            $exhibition['location'] = $rc['location']['name'];
            $location = $rc['location'];

            $joined_address = $location['address1'] . "<br/>";
            if( isset($location['address2']) && $location['address2'] != '' ) {
                $joined_address .= $location['address2'] . "<br/>";
            }
            $city = '';
            $comma = '';
            if( isset($location['city']) && $location['city'] != '' ) {
                $city = $location['city'];
                $comma = ', ';
            }
            if( isset($location['province']) && $location['province'] != '' ) {
                $city .= $comma . $location['province'];
                $comma = ', ';
            }
            if( isset($location['postal']) && $location['postal'] != '' ) {
                $city .= $comma . ' ' . $location['postal'];
                $comma = ', ';
            }
            if( $city != '' ) {
                $joined_address .= $city . "<br/>";
            }
            $exhibition['location_address'] = $joined_address;
        }
    }

    //
    // Get any links for the exhibition
    //
    $strsql = "SELECT id, name, url "
        . "FROM ciniki_artgallery_exhibition_links "
        . "WHERE ciniki_artgallery_exhibition_links.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_artgallery_exhibition_links.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'links', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $exhibition['links'] = $rc['links'];
    }

    return array('stat'=>'ok', 'exhibition'=>$exhibition);
}
?>
