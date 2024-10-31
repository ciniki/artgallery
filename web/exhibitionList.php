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
function ciniki_artgallery_web_exhibitionList($ciniki, $settings, $tnid, $args, $format='') {

    $strsql_count = "SELECT COUNT(ciniki_artgallery_exhibitions.id) ";
    $strsql = "SELECT ciniki_artgallery_exhibitions.id, "
        . "ciniki_artgallery_exhibitions.name, "
        . "";
    // Check where to pull location information
    $location_sql = '';
    if( ($ciniki['tenant']['modules']['ciniki.artgallery']['flags']&0x01) > 0 ) {
        $strsql .= "ciniki_artgallery_locations.name AS location, ";
        $location_sql = "LEFT JOIN ciniki_artgallery_locations ON (" 
            . "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
            . "AND ciniki_artgallery_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    } else {
        $strsql .= "ciniki_artgallery_exhibitions.location, ";
    }
    $strsql .= "DATE_FORMAT(start_date, '%M') AS start_month, "
        . "DATE_FORMAT(start_date, '%D') AS start_day, "
        . "DATE_FORMAT(start_date, '%Y') AS start_year, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%M')) AS end_month, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%D')) AS end_day, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%Y')) AS end_year, "
        . "DATE_FORMAT(start_date, '%b %e, %Y') AS start_date, "
        . "DATE_FORMAT(end_date, '%b %e, %Y') AS end_date, "
//      . "IF(DATEDIFF(start_date, NOW())>0,'yes','no') AS upcoming, "
        . "ciniki_artgallery_exhibitions.permalink, "
        . "ciniki_artgallery_exhibitions.short_description, "
        . "ciniki_artgallery_exhibitions.long_description, "
        . "ciniki_artgallery_exhibitions.primary_image_id, "
        . "COUNT(ciniki_artgallery_exhibition_images.id) AS num_images "
        . "";
    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql .= "FROM ciniki_artgallery_exhibition_tags "
            . "LEFT JOIN ciniki_artgallery_exhibitions ON ("
                . "ciniki_artgallery_exhibition_tags.exhibition_id = ciniki_artgallery_exhibitions.id "
                . "AND ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
                . ") "
            . $location_sql
            . "LEFT JOIN ciniki_artgallery_exhibition_images ON ("
                . "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_images.exhibition_id "
                . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
                . "AND ciniki_artgallery_exhibition_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_artgallery_exhibition_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_artgallery_exhibition_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
            . "";
        $strsql_count .= "FROM ciniki_artgallery_exhibition_tags "
            . "LEFT JOIN ciniki_artgallery_exhibitions ON ("
                . "ciniki_artgallery_exhibition_tags.exhibition_id = ciniki_artgallery_exhibitions.id "
                . "AND ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
                . ") "
            . "WHERE ciniki_artgallery_exhibition_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_artgallery_exhibition_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_artgallery_exhibition_tags.tag_type = '10' "
            . "";
    } else {
        $strsql .= "FROM ciniki_artgallery_exhibitions "
            . $location_sql
            . "LEFT JOIN ciniki_artgallery_exhibition_images ON ("
                . "ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_images.exhibition_id "
                . "AND ciniki_artgallery_exhibition_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Check the exhibition is visible on the website
            . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
            . "";
        $strsql_count .= "FROM ciniki_artgallery_exhibitions "
            . "WHERE ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Check the exhibition is visible on the website
            . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
            . "";
    }
    if( isset($args['type']) && $args['type'] == 'past' ) {
        $strsql .= "AND ((ciniki_artgallery_exhibitions.end_date > ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.end_date < DATE(NOW())) "
                . "OR (ciniki_artgallery_exhibitions.end_date < ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.start_date <= DATE(NOW())) "
                . ") "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "ORDER BY ciniki_artgallery_exhibitions.start_date DESC, name "
            . "";
        $strsql_count .= "AND ((ciniki_artgallery_exhibitions.end_date > ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.end_date < DATE(NOW())) "
                . "OR (ciniki_artgallery_exhibitions.end_date < ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.start_date <= DATE(NOW())) "
                . ") "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "";
    } elseif( isset($args['type']) && $args['type'] == 'current' ) {
        $strsql .= "AND (ciniki_artgallery_exhibitions.end_date >= DATE(NOW()) AND ciniki_artgallery_exhibitions.start_date <= DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "ORDER BY ciniki_artgallery_exhibitions.start_date ASC, name "
            . "";
        $strsql_count .= "AND (ciniki_artgallery_exhibitions.end_date >= DATE(NOW()) AND ciniki_artgallery_exhibitions.start_date <= DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "";
    } elseif( isset($args['type']) && $args['type'] == 'upcoming' ) {
        $strsql .= "AND (ciniki_artgallery_exhibitions.start_date > DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "ORDER BY ciniki_artgallery_exhibitions.start_date ASC, name "
            . "";
        $strsql_count .= "AND (ciniki_artgallery_exhibitions.start_date > DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "";
    } else {
        $strsql .= "AND (ciniki_artgallery_exhibitions.end_date >= DATE(NOW()) OR ciniki_artgallery_exhibitions.start_date >= DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "ORDER BY ciniki_artgallery_exhibitions.start_date ASC, name "
            . "";
        $strsql .= "AND (ciniki_artgallery_exhibitions.end_date >= DATE(NOW()) OR ciniki_artgallery_exhibitions.start_date >= DATE(NOW())) "
            . "GROUP BY ciniki_artgallery_exhibitions.id "
            . "";
    }
    if( isset($args['offset']) && $args['offset'] > 0
        && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['offset']) . ', ' . intval($args['limit']);
    } elseif( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && is_int($args['limit']) ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }

    //
    // Get the total number of exhibitions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql_count, 'ciniki.artgallery', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'customer.gibsoncentre.25', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    $item_count = isset($rc['num']) ? $rc['num'] : 0;

    //
    // Get the exhibition list
    //
    if( isset($args['format']) && $args['format'] == 'tradingcards' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'exhibitions', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'location', 'image_id'=>'primary_image_id', 
                    'start_date', 'start_month', 'start_day', 'start_year', 
                    'end_date', 'end_month', 'end_day', 'end_year', 
                    'permalink', 'synopsis'=>'short_description', 'long_description', 'num_images',
                    )),
            ));
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
                'fields'=>array('id', 'name', 'location', 'image_id'=>'primary_image_id', 
                    'start_date', 'start_month', 'start_day', 'start_year', 
                    'end_date', 'end_month', 'end_day', 'end_year', 
                    'permalink', 'description'=>'short_description', 'long_description', 'num_images')),
            ));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['exhibitions']) ) {
        return array('stat'=>'ok', 'exhibitions'=>array(), 'num_exhibitions'=>0);
    }
    
    return array('stat'=>'ok', 'exhibitions'=>$rc['exhibitions'], 'num_exhibitions'=>$item_count);
}
?>
