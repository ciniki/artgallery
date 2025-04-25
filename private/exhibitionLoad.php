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
function ciniki_artgallery_exhibitionLoad($ciniki, $tnid, $exhibition_id, $args) {

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
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
    // Get the main information
    //
    $rsp = array('stat'=>'ok');
    if( isset($exhibition_id) && $exhibition_id > 0 ) {
        $strsql = "SELECT ciniki_artgallery_exhibitions.id, "
            . "ciniki_artgallery_exhibitions.name, "
            . "ciniki_artgallery_exhibitions.permalink, "
            . "ciniki_artgallery_exhibitions.webflags, "
            . "IF((ciniki_artgallery_exhibitions.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible, "
            . "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS start_date, "
            . "IFNULL(DATE_FORMAT(ciniki_artgallery_exhibitions.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS end_date, "
            . "ciniki_artgallery_exhibitions.primary_image_id, "
            . "ciniki_artgallery_exhibitions.location, "
            . "ciniki_artgallery_exhibitions.location_id, "
            . "IFNULL(ciniki_artgallery_locations.name, '') AS location_text, "
            . "ciniki_artgallery_exhibitions.short_description, "
            . "ciniki_artgallery_exhibitions.long_description "
            . "FROM ciniki_artgallery_exhibitions "
            . "LEFT JOIN ciniki_artgallery_locations ON ("
                . "ciniki_artgallery_exhibitions.location_id = ciniki_artgallery_locations.id "
                . "AND ciniki_artgallery_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_artgallery_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_artgallery_exhibitions.id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
                'fields'=>array('id', 'name', 'permalink', 'webflags', 'web_visible', 'start_date', 'end_date', 'primary_image_id', 
                    'location', 'location_id', 'location_text', 'short_description', 'long_description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['exhibitions']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.artgallery.5', 'msg'=>'Unable to find exhibition'));
        }
        $rsp['exhibition'] = $rc['exhibitions'][0]['exhibition'];
    } else {
        $rsp['exhibition'] = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'webflags'=>0,
            'start_date'=>'',
            'end_date'=>'',
            'primary_image_id'=>0,
            'location'=>'',
            'location_id'=>0,
            'short_description'=>'',
            'long_description'=>'',
            );
    }

    //
    // Get the categories and tags for the post
    //
    if( ($ciniki['tenant']['modules']['ciniki.artgallery']['flags']&0x04) > 0 
        && isset($exhibition_id) && $exhibition_id > 0 
        ) {
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_artgallery_exhibition_tags "
            . "WHERE exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $rsp['exhibition']['categories'] = $tags['tags']['lists'];
                }
            }
        }
    }
    
    //
    // Load images for exhibition if requested
    //
    if( isset($exhibition_id) && $exhibition_id > 0 && isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "IF((flags&0x01)=1,'yes','no') AS reddot, "
            . "webflags, "
            . "sequence, "
            . "image_id, "
            . "description, "
            . "url, "
            . "last_updated "
            . "FROM ciniki_artgallery_exhibition_images "
            . "WHERE ciniki_artgallery_exhibition_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_artgallery_exhibition_images.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                'fields'=>array('id', 'name', 'permalink', 'reddot', 'webflags', 'sequence', 'image_id', 'description', 'url', 'last_updated'),
                'utctots'=>array('last_updated'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $rsp['exhibition']['images'] = $rc['images'];
            //
            // Include the image thumbnails in the returned data
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
            foreach($rsp['exhibition']['images'] as $img_id => $img) {
                if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                    $rc = ciniki_images_hooks_loadThumbnail($ciniki, $tnid, array('image_id'=>$img['image']['image_id'], 
                        'maxlength'=>75, 'last_updated'=>$img['image']['last_updated'], 'reddot'=>$img['image']['reddot']));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $rsp['exhibition']['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        } else {
            $rsp['exhibition']['images'] = array();
        }
    }

    //
    // Load links for exhibition if requested
    //
    if( isset($args['links']) && $args['links'] == 'yes' ) {
        $strsql = "SELECT id, name, url "
            . "FROM ciniki_artgallery_exhibition_links "
            . "WHERE ciniki_artgallery_exhibition_links.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_artgallery_exhibition_links.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'links', 'fname'=>'id', 'name'=>'link',
                'fields'=>array('id', 'name', 'url')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['links']) ) {
            $rsp['exhibition']['links'] = $rc['links'];
        } else {
            $rsp['exhibition']['links'] = array();
        }
    }

    //
    // Load links for exhibition if requested
    //
    if( isset($args['files']) && $args['files'] == 'yes' ) {
        $strsql = "SELECT id, name, extension, permalink "
            . "FROM ciniki_artgallery_exhibition_files "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_artgallery_exhibition_files.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.exhibitions', array(
            array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                'fields'=>array('id', 'name', 'extension', 'permalink')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $rsp['exhibition']['files'] = $rc['files'];
        }
    }

    //
    // Get the list of web collections, and which ones this exhibition is attached to
    //
    if( isset($args['webcollections']) && $args['webcollections'] == 'yes'
        && isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
        $rc = ciniki_web_hooks_webCollectionList($ciniki, $tnid,
            array('object'=>'ciniki.artgallery.exhibition', 'object_id'=>$exhibition_id));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['collections']) ) {
            $rsp['exhibition']['_webcollections'] = $rc['collections'];
            $rsp['exhibition']['webcollections'] = $rc['selected'];
            $rsp['exhibition']['webcollections_text'] = $rc['selected_text'];
        }
    }

    //
    // Get the list of sellers
    //
    if( isset($args['sellers']) && $args['sellers'] == 'yes' 
        && isset($ciniki['tenant']['modules']['ciniki.artgallery']['flags']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x02) == 0x02
        ) {
        $strsql = "SELECT ciniki_artgallery_exhibition_items.customer_id, "
            . "IFNULL(ciniki_customers.display_name, '') AS display_name, "
            . "IFNULL(ciniki_customers.first, '') AS first, "
            . "IFNULL(ciniki_customers.last, '') AS last, "
            . "COUNT(ciniki_artgallery_exhibition_items.id) AS num_items, "
            . "SUM(ciniki_artgallery_exhibition_items.price) AS total_price, "
            . "SUM(ciniki_artgallery_exhibition_items.tenant_fee) AS total_tenant_fee, "
            . "SUM(ciniki_artgallery_exhibition_items.seller_amount) AS total_seller_amount "
            . "FROM ciniki_artgallery_exhibition_items "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_artgallery_exhibition_items.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_artgallery_exhibition_items.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
            . "AND ciniki_artgallery_exhibition_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "GROUP BY ciniki_artgallery_exhibition_items.customer_id "
            . "ORDER BY ciniki_customers.display_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artgallery', array(
            array('container'=>'sellers', 'fname'=>'customer_id', 'name'=>'seller',
                'fields'=>array('id'=>'customer_id', 'display_name', 'first', 'last', 'num_items',
                    'total_price', 'total_tenant_fee', 'total_seller_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['sellers']) ) {
            $rsp['exhibition']['sellers'] = array();
        } else {
            $rsp['exhibition']['sellers'] = $rc['sellers'];
            foreach($rsp['exhibition']['sellers'] as $sid => $seller) {
                $rsp['exhibition']['sellers'][$sid]['seller']['total_price'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_price'], $intl_currency);
                $rsp['exhibition']['sellers'][$sid]['seller']['total_tenant_fee'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_tenant_fee'], $intl_currency);
                $rsp['exhibition']['sellers'][$sid]['seller']['total_seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_seller_amount'], $intl_currency);
            }
        }
    }

    return $rsp;
}
?>
