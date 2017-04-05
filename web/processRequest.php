<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_artgallery_web_processRequest($ciniki, $settings, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.artgallery']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.80', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] != ''
        && isset($args['uri_split'][1]) && $args['uri_split'][1] == 'download'
        && isset($args['uri_split'][2]) && $args['uri_split'][2] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'fileDownload');
        $rc = ciniki_info_web_fileDownload($ciniki, $business_id, $args['uri_split'][0], '', $args['uri_split'][2]);
        if( $rc['stat'] == 'ok' ) {
            return array('stat'=>'ok', 'download'=>$rc['file']);
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.74', 'msg'=>'The file you requested does not exist.'));
    }

    //
    // Store the content created by the page
    // Make sure everything gets generated ok before returning the content
    //
    $content = '';
    $page_content = '';
    $page['title'] = 'Exhibitors';
    $ciniki['response']['head']['og']['url'] = $args['base_url']; // $ciniki['request']['domain_base_url'] . '/exhibitions';

    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>$page_title, 'url'=>$args['base_url']);
    }

    //
    // The initial limit is how many to show on the exhibitions page after current and upcoming.  
    // This allows a shorter list in the initial page, and longer lists for the archive
    //
    $page_past_initial_limit = 2;
    $page_past_limit = 10;
    if( isset($settings['page-artgalleryexhibitions-initial-number']) 
        && $settings['page-artgalleryexhibitions-initial-number'] != ''
        && is_numeric($settings['page-artgalleryexhibitions-initial-number'])
        && $settings['page-artgalleryexhibitions-initial-number'] > 0 ) {
        $page_past_initial_limit = intval($settings['page-artgalleryexhibitions-initial-number']);
    }
    if( isset($settings['page-artgalleryexhibitions-archive-number']) 
        && $settings['page-artgalleryexhibitions-archive-number'] != ''
        && is_numeric($settings['page-artgalleryexhibitions-archive-number'])
        && $settings['page-artgalleryexhibitions-archive-number'] > 0 ) {
        $page_past_limit = intval($settings['page-artgalleryexhibitions-archive-number']);
    }
    if( isset($ciniki['request']['args']['page']) && $ciniki['request']['args']['page'] != '' && is_numeric($ciniki['request']['args']['page']) ) {
        $page_past_cur = $ciniki['request']['args']['page'];
    } else {
        $page_past_cur = 1;
    }

    //
    // FIXME: Check if anything has changed, and if not load from cache
    //

    //
    // Check if we are to display the application
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'exhibitionapplication' 
        && isset($settings['page-artgalleryexhibitions-application-details']) && $settings['page-artgalleryexhibitions-application-details'] == 'yes'
        ) {
        $page['breadcrumbs'][] = array('url'=>$args['base_url'] . '/exhibitions', 'name'=>'Application');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'pageDetails');
//      $rc = ciniki_artgallery_web_exhibitionApplicationDetails($ciniki, $settings, $business_id);
        $rc = ciniki_info_web_pageDetails($ciniki, $settings, $business_id, array('content_type'=>10));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.44', 'msg'=>"I'm sorry, but we can't find any information about the requestion application.", 'err'=>$rc['err']));;
        }
//        $info = $rc['content'];
        $page['blocks'][] = array('type'=>'content', 'content'=>$rc['content']['content']);
        if( isset($rc['content']['files']) && count($rc['content']['files']) > 0 ) {
            $page['blocks'][] = array('type'=>'files', 'base_url'=>$args['base_url'] . '/exhibitionapplication/download', 'files'=>$rc['content']['files']);
        }
/*        $page_content = "<pre>" . print_r($info, true) . "</pre>";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processPage');
        $rc = ciniki_web_processPage($ciniki, $settings, $args['base_url'] . '/exhibitions', $info, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        } */
//        $page_content = $rc['content'];
//      if( $application['details'] != '' ) {
//          $page_content .= "<article class='page'>\n"
//              . "<header class='entry-title'><h1 class='entry-title'>Exhibitor Application</h1></header>\n"
//              . "<div class='entry-content'>\n"
//              . "";
//          $rc = ciniki_web_processContent($ciniki, $settings, $application['details']);   
//          if( $rc['stat'] != 'ok' ) {
//              return $rc;
//          }
//          $page_content .= $rc['content'];
//
//          foreach($application['files'] as $fid => $file) {
//              $file = $file['file'];
//              $url = $args['base_url'] . '/exhibitions/download/' . $file['permalink'] . '.' . $file['extension'];
//              $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
//          }
//
//          $page_content .= "</div>\n"
//              . "</article>";
//      }
        $page['blocks'][] = array('type'=>'content', 'html'=>$page_content);
    }
    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' 
        ) {
        $exhibition_permalink = $args['uri_split'][0];
        $gallery_url = $args['base_url'] . "/" . $exhibition_permalink . "/gallery";
        $ciniki['response']['head']['og']['url'] .= '/' . $exhibition_permalink;

        //
        // Load the exhibition to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'web', 'exhibitionDetails');
        $rc = ciniki_artgallery_web_exhibitionDetails($ciniki, $settings, $business_id, $exhibition_permalink);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.45', 'msg'=>"I'm sorry, but we can't seem to find the image your requested.", $rc['err']));
        }
        $exhibition = $rc['exhibition'];

        $page['breadcrumbs'][] = array('name'=>$exhibition['name'], 'url'=>$args['base_url'] . '/' . $exhibition_permalink);
        if( isset($exhibition['short_description']) && $exhibition['short_description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($exhibition['short_description']);
        } elseif( isset($exhibition['description']) && $exhibition['description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($exhibition['description']);
        }

        $exhibition_date = $exhibition['start_month'];
        $exhibition_date .= " " . $exhibition['start_day'];
        if( $exhibition['end_day'] != '' && ($exhibition['start_day'] != $exhibition['end_day'] || $exhibition['start_month'] != $exhibition['end_month']) ) {
            if( $exhibition['end_month'] != '' && $exhibition['end_month'] == $exhibition['start_month'] ) {
                $exhibition_date .= " - " . $exhibition['end_day'];
            } else {
                $exhibition_date .= " - " . $exhibition['end_month'] . " " . $exhibition['end_day'];
            }
        }
        $exhibition_date .= ", " . $exhibition['start_year'];
        $page_title = $exhibition['name'];
        $page['title'] = $exhibition['name'];
        $page['meta_data'] = $exhibition_date;

        if( isset($args['uri_split'][1]) && $args['uri_split'][1] == 'gallery' && isset($args['uri_split'][2]) && $args['uri_split'][2] != '' ) {
            if( !isset($exhibition['images']) || count($exhibition['images']) < 1 ) {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.46', 'msg'=>"I'm sorry, but we can't seem to find the image your requested."));
            }

            $image_permalink = $args['uri_split'][2];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
            $rc = ciniki_web_galleryFindNextPrev($ciniki, $exhibition['images'], $image_permalink);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['img'] == NULL ) {
                $page['blocks'][] = array('type'=>'message', 'section'=>'exhibition-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
            } else {
                $page_title = $exhibition['name'] . ' - ' . $rc['img']['title'];
                $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$args['base_url'] . '/' . $exhibition_permalink . '/gallery/' . $image_permalink);
                if( $rc['img']['title'] != '' ) {
                    $page['title'] .= ' - ' . $rc['img']['title'];
                }
                $block = array('type'=>'galleryimage', 'section'=>'exhibition-image', 'primary'=>'yes', 'image'=>$rc['img']);
                if( $rc['prev'] != null ) {
                    $block['prev'] = array('url'=>$args['base_url'] . '/' . $exhibition_permalink . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                }
                if( $rc['next'] != null ) {
                    $block['next'] = array('url'=>$args['base_url'] . '/' . $exhibition_permalink . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                }
                $page['blocks'][] = $block;
            }
        } else {
            if( isset($exhibition['image_id']) && $exhibition['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes',
                    'image_id'=>$exhibition['image_id'], 'title'=>$exhibition['name'], 'caption'=>'');
            }
            $content = '';
            if( isset($exhibition['description']) && $exhibition['description'] != '' ) {
                $content = $exhibition['description'];
            } else {
                $content = $exhibition['short_description'];
            }
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

            //
            // Add the links if they exist
            //
            if( isset($exhibition['links']) && count($exhibition['links']) > 0 ) {
                if( isset($event['links']) && count($event['links']) > 0 ) {
                    $page['blocks'][] = array('type'=>'links', 'section'=>'links', 'title'=>'', 'links'=>$exhibition['links']);
                }
            }

            //
            // Check if share buttons should be shown
            //
            if( !isset($settings['page-exhibitions-share-buttons']) || $settings['page-exhibitions-share-buttons'] == 'yes' ) {
                $tags = array('Exhibitions');
                if( !isset($settings['page-events-share-buttons']) || $settings['page-events-share-buttons'] == 'yes' ) {
                    $tags = array();
                    $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$page['title'], 'tags'=>$tags);
                }
            }
            
            //
            // Add images if they exist
            //
            if( isset($exhibition['images']) && count($exhibition['images']) > 0 ) {
                $page['blocks'][] = array('type'=>'gallery', 'section'=>'gallery', 'title'=>'Additional Images',
                    'base_url'=>$args['base_url'] . "/" . $exhibition_permalink . "/gallery",
                    'images'=>$exhibition['images']);
            }
        }
    }

    //
    // Display the list of exhibitors if a specific one isn't selected
    //
    else {
        //
        // Check to see if there is an introduction message to display
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $business_id, 'ciniki.web', 'content', 'page-artgalleryexhibitions');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['response']['head']['og']['description'] = strip_tags('Upcoming Exhibitions');
        if( $page_past_cur == 1 && isset($rc['content']['page-artgalleryexhibitions-content']) && $rc['content']['page-artgalleryexhibitions-content'] != '' ) {
            $page_content = '';
            if( isset($settings['page-artgalleryexhibitions-image']) 
                && $settings['page-artgalleryexhibitions-image'] != '' 
                && $settings['page-artgalleryexhibitions-image'] > 0 
                ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes',
                    'image_id'=>$settings['page-artgalleryexhibitions-image'],
                    'captions'=>$settings['page-artgalleryexhibitions-image-caption'],
                    );
            }
            $content = $rc['content']['page-artgalleryexhibitions-content'];

            //
            // Check if there is an application
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'pageDetails');
            $rc = ciniki_info_web_pageDetails($ciniki, $settings, $business_id, array('content_type'=>10));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $application = $rc['content'];
            if( $application['content'] != '' ) {
                $content .= "\n\n<a href='" . $args['base_url'] . "/exhibitionapplication'>Apply to be an exhibitor</a>";
            }

            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$content);
        }

        //
        // Display list of upcoming exhibitions
        //
        $category = '';
        if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'category' 
            && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' 
            ) {
            $category = $args['uri_split'][1];
        }
        $num_current = 0;
        if( $page_past_cur == 1 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'web', 'exhibitionList');
            $rc = ciniki_artgallery_web_exhibitionList($ciniki, $settings, $business_id, 
                array('type'=>'current', 'limit'=>0, 'category'=>$category));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( count($rc['exhibitions']) > 0 ) {
                $exhibitions = $rc['exhibitions'];
//                $page_content .= "<article class='page'>\n"
//                    . "<header class='entry-title'><h1 class='entry-title'>"
//                    . (count($rc['exhibitions'])>1?'Current Exhibitions':'Current Exhibition') 
//                    . "</h1></header>\n"
//                    . "<div class='entry-content'>\n"
//                    . "";

                $num_current = count($exhibitions);
                if( $num_current > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processExhibitions');
                    $rc = ciniki_web_processExhibitions($ciniki, $settings, $exhibitions, 0);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page['blocks'][] = array('type'=>'content', 'section'=>'current-exhibitions',
                        'title'=>($num_current>1?'Current Exhibitions':'Current Exhibition'),
                        'html'=>$rc['content'],
                        );
                }
            }

            ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'web', 'exhibitionList');
            $rc = ciniki_artgallery_web_exhibitionList($ciniki, $settings, $business_id, 
                array('type'=>'upcoming', 'limit'=>0, 'category'=>$category));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $exhibitions = $rc['exhibitions'];
//            $page_content .= "<article class='page'>\n"
//                . "<header class='entry-title'><h1 class='entry-title'>Upcoming Exhibitions</h1></header>\n"
//                . "<div class='entry-content'>\n"
//                . "";

            if( count($exhibitions) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processExhibitions');
                $rc = ciniki_web_processExhibitions($ciniki, $settings, $exhibitions, 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'content', 'section'=>'exhibition-list', 
                    'title'=>($num_current > 0 ? 'Upcoming Exhibitions' : ''), 
                    'html'=>$rc['content']);
//                $page_content .= $rc['content'];
            } else {
                $page['blocks'][] = array('type'=>'content', 'title'=>'', 'content'=>'No upcoming exhibitions');
//                $page_content .= "<p>No upcoming exhibitions</p>";
            }

//            $page_content .= "</div>\n"
//                . "</article>\n"
//                . "";
        }

        //
        // Include past exhibitions if the user wants
        //
        if( isset($settings['page-artgalleryexhibitions-past']) && $settings['page-artgalleryexhibitions-past'] == 'yes' ) {
            //
            // Generate the content of the page
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'web', 'exhibitionList');
            if( $page_past_cur == 1 ) {
                $offset = 0;
            } else {
                $offset = $page_past_initial_limit + ($page_past_cur-2)*$page_past_limit;
            }
            $rc = ciniki_artgallery_web_exhibitionList($ciniki, $settings, $business_id, 
                array('type'=>'past', 
                    'category'=>$category,
                    'offset'=>$offset,
                    'limit'=>($page_past_cur==1?($page_past_initial_limit+1):($page_past_limit+1))));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $exhibitions = $rc['exhibitions'];
    
            if( count($exhibitions) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processExhibitions');
                $rc = ciniki_web_processExhibitions($ciniki, $settings, $exhibitions, 
                    array('page'=>$page_past_cur,
                        'limit'=>($page_past_cur==1?$page_past_initial_limit:$page_past_limit), 
                        'prev'=>'Newer Exhibitions &rarr;',
                        'next'=>'&larr; Older Exhibitions',
                        'base_url'=>$args['base_url'] . ($category!=''?'/category/'.$category:'')));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'content', 'title'=>'Past Exhibitions', 'html'=>$rc['content']);
                $page['blocks'][] = array('type'=>'content', 'html'=>$rc['nav']);
            } else {
                $page['blocks'][] = array('type'=>'content', 'title'=>'Past Exhibitions', 'content'=>'No past exhibitions');
            }

//            $page_content .= "</div>\n"
//                . "</article>\n"
//                . "";
//            if( isset($nav_content) && $nav_content != '' ) {   
//                $page_content .= $nav_content;
//            }
        }

        //
        // Check if the exhibition application should be displayed
        //
        if( isset($settings['page-artgalleryexhibitions-application-details']) 
            && $settings['page-artgalleryexhibitions-application-details'] == 'yes' 
            && $page_past_cur == 1
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'pageDetails');
            $rc = ciniki_info_web_pageDetails($ciniki, $settings, $business_id,
                array('content_type'=>10));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $application = $rc['content'];
            if( $application['content'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'html'=>"<p class='exhibitors-application'>"
                    . "<a href='" . $args['base_url'] . "/exhibitionapplication'>Apply to be an exhibitor</a></p>",
                    );
            }
        }
    }

    //
    // Check for categories
    //
    $page['submenu'] = array();
    if( ($ciniki['business']['modules']['ciniki.artgallery']['flags']&0x04) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artgallery', 'web', 'categories');
        $rc = ciniki_artgallery_web_categories($ciniki, $settings, $business_id, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            foreach($rc['categories'] as $category) {
                $page['submenu'][$category['permalink']] = array('name'=>$category['name'],
                    'url'=>$args['base_url'] . '/category/' . $category['permalink']);
            }
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
