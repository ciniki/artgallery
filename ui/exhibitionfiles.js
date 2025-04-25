//
// The artgallery app to manage the artgallery for the tenant
//
function ciniki_artgallery_exhibitionfiles() {
    //
    // The panel to display the add form
    //
    this.add = new M.panel('Add File', 'ciniki_artgallery_exhibitionfiles', 'add', 'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitionfiles.edit');
    this.add.default_data = {'type':'20'};
    this.add.data = {}; 
    this.add.sections = {
        '_file':{'label':'File', 'fields':{
            'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
            }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitionfiles.add.save();'},
            }},
    };
    this.add.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) {
            return this.data[i]; 
        } 
        return ''; 
    };
    this.add.save = function() {
        var c = this.serializeFormData('yes');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.artgallery.exhibitionFileAdd', {'tnid':M.curTenantID, 'exhibition_id':this.exhibition_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } else {
                    M.ciniki_artgallery_exhibitionfiles.add.file_id = rsp.id;
                    M.ciniki_artgallery_exhibitionfiles.add.close();
                }
            });
        } else {
            this.close();
        }
    };
    this.add.open = function(cb, eid) {
        this.reset();
        this.data = {'name':''};
        this.file_id = 0;
        this.exhibition_id = eid;
        this.refresh();
        this.show(cb);
    };
    this.add.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitionfiles.add.save();');
    this.add.addClose('Cancel');

    //
    // The panel to display the edit form
    //
    this.edit = new M.panel('File', 'ciniki_artgallery_exhibitionfiles', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitionfiles.edit');
    this.edit.file_id = 0;
    this.edit.data = null;
    this.edit.sections = {
        'info':{'label':'Details', 'type':'simpleform', 'fields':{
            'name':{'label':'Name', 'type':'text', },
            }},
        '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitionfiles.edit.save();'},
                'download':{'label':'Download', 'fn':'M.ciniki_artgallery_exhibitionfiles.edit.downloadFile(M.ciniki_artgallery_exhibitionfiles.edit.file_id);'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitionfiles.edit.remove();'},
            }},
    };
    this.edit.fieldValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.edit.sectionData = function(s) {
        return this.data[s];
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.artgallery.exhibitionFileHistory', 'args':{'tnid':M.curTenantID, 
            'file_id':this.file_id, 'field':i}};
    };
    this.edit.open = function(cb, fid) {
        if( fid != null ) { this.file_id = fid; }
        M.api.getJSONCb('ciniki.artgallery.exhibitionFileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_artgallery_exhibitionfiles.edit;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    };
    this.edit.save = function() {
        var c = this.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.artgallery.exhibitionFileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_artgallery_exhibitionfiles.edit.close();
            });
        }
    };
    this.edit.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionFileDelete', {'tnid':M.curTenantID, 'file_id':M.ciniki_artgallery_exhibitionfiles.edit.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_artgallery_exhibitionfiles.edit.close();
            });
        });
    };
    this.edit.downloadFile = function(fid) {
        M.api.openFile('ciniki.artgallery.exhibitionFileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitionfiles.edit.save();');
    this.edit.addClose('Cancel');

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitionfiles', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.file_id != null && args.file_id > 0 ) {
            this.edit.open(cb, args.file_id);
        } else if( args.exhibition_id != null && args.exhibition_id > 0 && args.add != null && args.add == 'yes' ) {
            this.add.open(cb, args.exhibition_id);
        } else {
            M.alert('Invalid request');
        }
    }
}
