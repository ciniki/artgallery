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
function ciniki_artgallery_web_calendarsWebItems($ciniki, $settings, $tnid, $args) {

    if( !isset($args['ltz_start']) || !is_a($args['ltz_start'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.exhibitions.29', 'msg'=>'Invalid start date'));
    }
    if( !isset($args['ltz_end']) || !is_a($args['ltz_end'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.exhibitions.30', 'msg'=>'Invalid end date'));
    }

    $sdt = $args['ltz_start'];
    $edt = $args['ltz_end'];

    if( isset($ciniki['tenant']['module_pages']['ciniki.artgallery']['base_url']) ) {
        $base_url = $ciniki['tenant']['module_pages']['ciniki.artgallery']['base_url'];
    } else {
        $base_url = '/exhibitions';
    }

    //
    // Check if this modules items are to be included in the calendar
    //
    if( isset($settings['ciniki-artgallery-calendar-include']) && $settings['ciniki-artgallery-calendar-include'] == 'no' ) {
        return array('stat'=>'ok');
    }

    //
    // Check if colours specified
    //
    $style = '';
    if( isset($settings['ciniki-artgallery-colour-background']) && $settings['ciniki-artgallery-colour-background'] != '' ) {
        $style .= ($style != '' ? ' ':'') . 'background: ' . $settings['ciniki-artgallery-colour-background'] . ';';
    }
    if( isset($settings['ciniki-artgallery-colour-border']) && $settings['ciniki-artgallery-colour-border'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' border: 1px solid ' . $settings['ciniki-artgallery-colour-border'] . ';';
    }
    if( isset($settings['ciniki-artgallery-colour-font']) && $settings['ciniki-artgallery-colour-font'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' color: ' . $settings['ciniki-artgallery-colour-font'] . ';';
    }

    //
    // Setup the legend
    //
    if( isset($settings['ciniki-artgallery-legend-title']) && $settings['ciniki-artgallery-legend-title'] != '' ) {
        $legend = array(
            array('title'=>$settings['ciniki-artgallery-legend-title'], 'style'=>$style)
            );
    } else {
        $legend = array();
    }

    //
    // FIXME: Add select for tags to get other colours on web
    //

    //
    // Get the list of exhibitions that start, end or start before and end after the dates specified
    //
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
        . "DATE_FORMAT(start_date, '%Y-%m-%d') AS start_date, "
        . "DATE_FORMAT(end_date, '%Y-%m-%d') AS end_date, "
//      . "IF(DATEDIFF(start_date, NOW())>0,'yes','no') AS upcoming, "
        . "ciniki_artgallery_exhibitions.permalink, "
        . "ciniki_artgallery_exhibitions.short_description, "
        . "ciniki_artgallery_exhibitions.long_description, "
        . "ciniki_artgallery_exhibitions.primary_image_id "
        . "FROM ciniki_artgallery_exhibitions "
        . $location_sql
        . "WHERE ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        // Check the exhibition is visible on the website
        . "AND (ciniki_artgallery_exhibitions.webflags&0x01) = 0 "
        . "AND ("
            . "("
                . "ciniki_artgallery_exhibitions.start_date >= '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
                . "AND ciniki_artgallery_exhibitions.start_date <= '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
            . ") "
            . "OR ("
                . "ciniki_artgallery_exhibitions.end_date >= '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
                . "AND ciniki_artgallery_exhibitions.end_date <= '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
            . ") "
            . "OR ("
                . "ciniki_artgallery_exhibitions.start_date < '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
                . "AND ciniki_artgallery_exhibitions.end_date > '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
            . ") "
        . ") ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artgallery', array(
        array('container'=>'items', 'fname'=>'id', 'name'=>'exhibition',
            'fields'=>array('id', 'title'=>'name', 'location', 'image_id'=>'primary_image_id', 
                'start_date', 'end_date', 'permalink', 'description'=>'short_description', 'long_description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $prefix = '';
    if( isset($settings['ciniki-artgallery-exhibition-prefix']) ) {
        $prefix = $settings['ciniki-artgallery-exhibition-prefix'];
    }

    $items = array();
    if( isset($rc['items']) ) {
        foreach($rc['items'] as $exhibition) {
            $item = array(
                'title'=>$prefix . $exhibition['title'],
                'time_text'=>'',
                'style'=>$style,
                'url'=>$base_url . '/' . $exhibition['permalink'],
                'classes'=>array('exhibitions'),
                );
            if( $exhibition['end_date'] != '' && $exhibition['end_date'] != '0000-00-00' && $exhibition['start_date'] != $exhibition['end_date'] ) {
                //
                // Add an item to the items list for each date of the exhibition
                //
                $dt = new DateTime($exhibition['start_date'], $sdt->getTimezone());
                $c = 0;
                do {
                    if( $c > 365 ) {
                        error_log("ERR: runaway exhibition dates " . $exhibition['id']);
                        break;
                    }
                    $cur_date = $dt->format('Y-m-d');
                    if( !isset($items[$cur_date]) ) {
                        $items[$cur_date]['items'] = array();
                    }
                    $items[$cur_date]['items'][] = $item;

                    $dt->add(new DateInterval('P1D'));
                    $c++;
                } while( $cur_date != $exhibition['end_date']);
            } else {
                if( !isset($items[$exhibition['start_date']]) ) {
                    $items[$exhibition['start_date']]['items'] = array();
                }
                $items[$exhibition['start_date']]['items'][] = $item;
            }
        }
    }

    return array('stat'=>'ok', 'items'=>$items, 'legend'=>$legend);
}
?>
