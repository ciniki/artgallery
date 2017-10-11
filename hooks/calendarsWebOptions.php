<?php
//
// Description
// -----------
// This function will return the calendar options for the this module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_artgallery_hooks_calendarsWebOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.artgallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.43', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Exhibitions Title Prefix',
        'setting'=>'ciniki-artgallery-exhibition-prefix',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-artgallery-exhibition-prefix'])?$settings['ciniki-artgallery-exhibition-prefix']:''),
    );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
