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
function ciniki_artgallery_web_webCollectionList($ciniki, $settings, $tnid, $args) {

    $strsql = "SELECT ciniki_artgallery_exhibitions.id, "
        . "ciniki_artgallery_exhibitions.name, "
        . "ciniki_artgallery_exhibitions.location, "
        . "DATE_FORMAT(start_date, '%M') AS start_month, "
        . "DATE_FORMAT(start_date, '%D') AS start_day, "
        . "DATE_FORMAT(start_date, '%Y') AS start_year, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%M')) AS end_month, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%D')) AS end_day, "
        . "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%Y')) AS end_year, "
        . "DATE_FORMAT(start_date, '%b %e, %Y') AS start_date, "
        . "DATE_FORMAT(end_date, '%b %e, %Y') AS end_date, "
        . "ciniki_artgallery_exhibitions.permalink, "
        . "ciniki_artgallery_exhibitions.short_description, "
        . "ciniki_artgallery_exhibitions.long_description, "
        . "ciniki_artgallery_exhibitions.primary_image_id, "
        . "COUNT(ciniki_artgallery_exhibition_images.id) AS num_images "
        . "FROM ciniki_web_collection_objrefs "
        . "INNER JOIN ciniki_artgallery_exhibitions ON ("
            . "ciniki_web_collection_objrefs.object_id = ciniki_artgallery_exhibitions.id "
            . "AND ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
            . "";
        if( isset($args['type']) && $args['type'] == 'past' ) {
            $strsql .= "AND ((ciniki_artgallery_exhibitions.end_date > ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.end_date < DATE(NOW())) "
                    . "OR (ciniki_artgallery_exhibitions.end_date <= ciniki_artgallery_exhibitions.start_date AND ciniki_artgallery_exhibitions.start_date < DATE(NOW())) "
                    . ") ";
        } else {
            $strsql .= "AND (ciniki_artgallery_exhibitions.end_date >= DATE(NOW()) OR ciniki_artgallery_exhibitions.start_date >= DATE(NOW())) ";
        }
    $strsql .= ") "
        . "LEFT JOIN ciniki_artgallery_exhibition_images ON (ciniki_artgallery_exhibitions.id = ciniki_artgallery_exhibition_images.exhibition_id "
            . "AND ciniki_artgallery_exhibition_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
        . "AND ciniki_web_collection_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_web_collection_objrefs.object = 'ciniki.artgallery.exhibition' "
        . "";
    if( isset($args['type']) && $args['type'] == 'past' ) {
        $strsql .= "GROUP BY ciniki_artgallery_exhibitions.id ";
        $strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date DESC ";
    } else {
        $strsql .= "GROUP BY ciniki_artgallery_exhibitions.id ";
        $strsql .= "ORDER BY ciniki_artgallery_exhibitions.start_date ASC ";
    }
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && is_int($args['limit']) ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
            'fields'=>array('id', 'name', 'location', 'image_id'=>'primary_image_id', 
                'start_date', 'start_month', 'start_day', 'start_year', 
                'end_date', 'end_month', 'end_day', 'end_year', 
                'permalink', 'description'=>'short_description', 'long_description', 'num_images')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['exhibitions']) ) {
        return array('stat'=>'ok', 'exhibitions'=>array());
    }
    return array('stat'=>'ok', 'exhibitions'=>$rc['exhibitions']);
}
?>
