//
// The app to add/edit artgallery exhibit images
//
function ciniki_artgallery_exhibitionimages() {
    this.flags = {
        '1':{'name':'Sold'},
        };
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_artgallery_exhibitionimages', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitionimages.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.exhibition_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'flags':{'label':'Options', 'type':'flags', 'join':'yes', 'flags':this.flags},
                'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitionimages.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitionimages.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.artgallery.exhibitionImageHistory','args':{'tnid':M.curTenantID, 
                'exhibition_image_id':M.ciniki_artgallery_exhibitionimages.edit.exhibition_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_artgallery_exhibitionimages.edit.setFieldValue('image_id', iid);
            return true;
        }
        this.edit.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitionimages.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitionimages', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.exhibition_id);
        } else if( args.exhibition_image_id != null && args.exhibition_image_id > 0 ) {
            this.showEdit(cb, args.exhibition_image_id, 0);
        } else {
            return false;
        }
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) { this.edit.exhibition_image_id = iid; }
        if( eid != null ) { this.edit.exhibition_id = eid; }
        if( this.edit.exhibition_image_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionImageGet', 
                {'tnid':M.curTenantID, 'exhibition_image_id':this.edit.exhibition_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_artgallery_exhibitionimages.edit;
                    p.data = rsp.image;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.exhibition_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.artgallery.exhibitionImageUpdate', 
                    {'tnid':M.curTenantID, 
                    'exhibition_image_id':this.edit.exhibition_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_artgallery_exhibitionimages.edit.close();
                            }
                        });
            } else {
                M.ciniki_artgallery_exhibitionimages.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            c += '&exhibition_id=' + encodeURIComponent(this.edit.exhibition_id);
            var rsp = M.api.postJSONFormData('ciniki.artgallery.exhibitionImageAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } else {
                        M.ciniki_artgallery_exhibitionimages.edit.close();
                    }
                });
        }
    };

    this.deleteImage = function() {
        M.confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionImageDelete', {'tnid':M.curTenantID, 
                'exhibition_image_id':M.ciniki_artgallery_exhibitionimages.edit.exhibition_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_artgallery_exhibitionimages.edit.close();
                });
        });
    };
}
