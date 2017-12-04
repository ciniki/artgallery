//
// The app to add/edit artgallery exhibit links
//
function ciniki_artgallery_exhibitionlinks() {
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Link',
            'ciniki_artgallery_exhibitionlinks', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitionlinks.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.exhibition_id = 0;
        this.edit.sections = {
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'url':{'label':'URL', 'type':'text'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitionlinks.saveLink();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitionlinks.deleteLink();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.artgallery.exhibitionLinkHistory','args':{'tnid':M.curTenantID, 
                'exhibition_link_id':M.ciniki_artgallery_exhibitionlinks.edit.exhibition_link_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitionlinks.saveLink();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitionlinks', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.exhibition_id);
        } else if( args.exhibition_link_id != null && args.exhibition_link_id > 0 ) {
            this.showEdit(cb, args.exhibition_link_id, 0);
        }
        return false;
    }

    this.showEdit = function(cb, lid, eid) {
        if( lid != null ) {
            this.edit.exhibition_link_id = lid;
        }
        if( eid != null ) {
            this.edit.exhibition_id = eid;
        }
        if( this.edit.exhibition_link_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionLinkGet', 
                {'tnid':M.curTenantID, 'exhibition_link_id':this.edit.exhibition_link_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_artgallery_exhibitionlinks.edit;
                    p.data = rsp.link;
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

    this.saveLink = function() {
        if( this.edit.exhibition_link_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.artgallery.exhibitionLinkUpdate', 
                    {'tnid':M.curTenantID, 
                    'exhibition_link_id':this.edit.exhibition_link_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_artgallery_exhibitionlinks.edit.close();
                            }
                        });
            } else {
                M.ciniki_artgallery_exhibitionlinks.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            c += '&exhibition_id=' + encodeURIComponent(this.edit.exhibition_id);
            var rsp = M.api.postJSONFormData('ciniki.artgallery.exhibitionLinkAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } else {
                        M.ciniki_artgallery_exhibitionlinks.edit.close();
                    }
                });
        }
    };

    this.deleteLink = function() {
        if( confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?') ) {
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionLinkDelete', {'tnid':M.curTenantID, 
                'exhibition_link_id':this.edit.exhibition_link_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_artgallery_exhibitionlinks.edit.close();
                });
        }
    };
}
