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
// tnid:     The ID of the tenant to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_artgallery_hooks_calendarsWebOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.artgallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artgallery.43', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Include Exhibitions',
        'setting'=>'ciniki-artgallery-calendar-include',
        'type'=>'toggle',
        'value'=>(isset($settings['ciniki-artgallery-calendar-include'])?$settings['ciniki-artgallery-calendar-include']:'yes'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Exhibitions Title Prefix',
        'setting'=>'ciniki-artgallery-exhibition-prefix',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-artgallery-exhibition-prefix'])?$settings['ciniki-artgallery-exhibition-prefix']:''),
        );
    $options[] = array(
        'label'=>'Exhibitions Legend Name',
        'setting'=>'ciniki-artgallery-legend-title',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-artgallery-legend-title'])?$settings['ciniki-artgallery-legend-title']:''),
        );
    $options[] = array(
        'label'=>'Exhibitions Background Colour',
        'setting'=>'ciniki-artgallery-colour-background', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-artgallery-colour-background'])?$settings['ciniki-artgallery-colour-background']:'no'),
        );
    $options[] = array(
        'label'=>'Exhibitions Border Colour',
        'setting'=>'ciniki-artgallery-colour-border', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-artgallery-colour-border'])?$settings['ciniki-artgallery-colour-border']:'no'),
        );
    $options[] = array(
        'label'=>'Exhibitions Font Colour',
        'setting'=>'ciniki-artgallery-colour-font', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-artgallery-colour-font'])?$settings['ciniki-artgallery-colour-font']:'no'),
        );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
