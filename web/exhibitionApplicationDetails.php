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
function ciniki_artgallery_web_exhibitionApplicationDetails($ciniki, $settings, $tnid) {

    $strsql = "SELECT detail_value "
        . "FROM ciniki_artgallery_settings "
        . "WHERE ciniki_artgallery_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND detail_key = 'exhibition-application-details' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artgallery', 'application');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['application']) ) {
        $application_details = $rc['application']['detail_value'];
    } else {
        return array('stat'=>'ok', 'application'=>array('details'=>'', 'files'=>array()));
    }

    $strsql = "SELECT id, name, extension, permalink, description "
        . "FROM ciniki_artgallery_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND type = 2 "
        . "AND (webflags&0x01) = 0 "
        . "ORDER BY name "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'files', 'fname'=>'name', 'name'=>'file',
            'fields'=>array('id', 'name', 'extension', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        return array('stat'=>'ok', 'application'=>array('details'=>$application_details, 'files'=>$rc['files']));
    }

    return array('stat'=>'ok', 'application'=>array('details'=>$application_details, 'files'=>array()));
}
?>
