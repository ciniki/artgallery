<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_artgallery_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.artgallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.42', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page-exhibitions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $options = array();
    //
    // FIXME: Add settings
    //
/*    $options[] = array(
        'label'=>'Image',
        'setting'=>'page-artgalleryexhibitions-image',
        'type'=>'image',
        'value'=>(isset($settings['page-artgalleryexhibitions-image'])?$settings['page-artgalleryexhibitions-image']:'yes'),
    );
    $options[] = array(
        'label'=>'Caption',
        'setting'=>'page-artgalleryexhibitions-image-caption',
        'type'=>'text',
        'value'=>(isset($settings['page-artgalleryexhibitions-image-caption'])?$settings['page-artgalleryexhibitions-image-caption']:''),
    );
    $options[] = array(
        'label'=>'Introduction',
        'setting'=>'page-artgalleryexhibitions-content',
        'type'=>'textarea',
        'value'=>(isset($settings['page-artgalleryexhibitions-content'])?$settings['page-artgalleryexhibitions-content']:''),
    ); */


    $pages['ciniki.artgallery'] = array('name'=>'Exhibitions', 'options'=>$options);
    
/*            '_image':{'label':'Image', 'type':'imageform', 'fields':{
                'page-artgalleryexhibitions-image':{'label':'', 'type':'image_id', 'controls':'all', 'hidelabel':'yes', 'history':'no'},
                }},
            '_image_caption':{'label':'', 'fields':{
                'page-artgalleryexhibitions-image-caption':{'label':'Caption', 'type':'text'},
                }},
            '_content':{'label':'Content', 'fields':{
                'page-artgalleryexhibitions-content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
                }}, */
//    $pages['ciniki.artgallery.currentupcoming'] = array('name'=>'Exhibitions - Current & Upcoming', 'options'=>$options);
//    $pages['ciniki.artgallery.current'] = array('name'=>'Exhibitions - Current', 'options'=>$options);
//    $pages['ciniki.artgallery.upcoming'] = array('name'=>'Exhibitions - Upcoming', 'options'=>$options);
//    $pages['ciniki.artgallery.past'] = array('name'=>'Exhibitions - Past', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
