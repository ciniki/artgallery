//
// The exhibitions app to manage exhibitions for an artgallery
//
function ciniki_artgallery_exhibitions() {
    this.webFlags = {'1':{'name':'Hidden'}};
    this.init = function() {
        //
        // Setup the main panel to list the exhibitions 
        //
        this.menu = new M.panel('Exhibitions',
            'ciniki_artgallery_exhibitions', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitions.menu');
        this.menu.year = 0;
        this.menu.location_id = 0; 
        this.menu.customer_id = 0; 
        this.menu.category = '';
        this.menu.data = {};
        this.menu.sections = {
            '_tabs':{'label':'', 'selected':'categories', 'aside':'yes', 'visible':'no', 'type':'paneltabs', 'selected':'', 'tabs':{
                'categories':{'label':'Categories', 'visible':'no', 'fn':'M.ciniki_artgallery_exhibitions.menu.switchTab(\'categories\');'},
                'locations':{'label':'Locations', 'visible':'no', 'fn':'M.ciniki_artgallery_exhibitions.menu.switchTab(\'locations\');'},
                'sellers':{'label':'Exhibitors', 'visible':'no', 'fn':'M.ciniki_artgallery_exhibitions.menu.switchTab(\'sellers\');'},
                }},
            'categories':{'label':'Categories', 'aside':'yes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
                },
            'locations':{'label':'Locations', 'aside':'yes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Location',
                'addFn':'M.startApp(\'ciniki.artgallery.locations\',null,\'M.ciniki_artgallery_exhibitions.showMenu();\',\'mc\',{\'location_id\':0});',
                },
            'sellers':{'label':'Exhibitors', 'aside':'yes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
                },
            'search':{'label':'', 'visible':'yes', 'type':'livesearchgrid', 'livesearchcols':1,
                'hint':'Search Exhibitions', 'noData':'No exhibitions found',
                'cellClasses':['multiline'],
                'headerValues':null,
                },
            'location_notes':{'label':'Location', 'visible':'no', 'type':'htmlcontent'},
            'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
            'exhibitions':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline'],
                'noData':'No exhibitions',
                'addTxt':'Add Exhibition',
                'addFn':'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showMenu();\',0);',
                },
            };
        this.menu.switchTab = function(tab) {
            this.sections._tabs.selected = tab;
            for(var i in this.sections._tabs.tabs) {
                this.sections[i].visible = (i==this.sections._tabs.selected?'yes':'no');
            }
            this.refresh();
        };
        this.menu.sectionData = function(s) { 
            if( s == 'location_notes' ) { 
                if( this.data[s] != null ) {
                    return this.data[s].replace(/\n/g, '<br/>'); 
                } else {
                    return '';
                }
            }
            return this.data[s]; 
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'exhibitions' || s == 'search' ) {
                var location = '';
                if( d.exhibition.location != '' ) {
                    location = ' <span class="subdue">' + d.exhibition.location + '</span>';
                }
                return '<span class="maintext">' + d.exhibition.name + location + '</span>'
                    + '<span class="subtext">' + d.exhibition.start_date + ' - ' + d.exhibition.end_date + '</span>';
            } else if( s == 'categories' ) {
                return d.tag.name + ' <span class="count">' + d.tag.num_exhibitions + '</span>';
            } else if( s == 'locations' ) {
                return d.location.name + ' <span class="count">' + d.location.num_exhibitions + '</span>';
            } else if( s == 'sellers' ) {
                return d.customer.first + ' ' + d.customer.last + ' <span class="count">' + d.customer.num_exhibitions + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) { 
            if( s == 'exhibitions' || s == 'search' ) {
                return 'M.ciniki_artgallery_exhibitions.showExhibition(\'M.ciniki_artgallery_exhibitions.showMenu();\',\'' + d.exhibition.id + '\');'; 
            } else if( s == 'categories' ) {
                return 'M.ciniki_artgallery_exhibitions.showMenu(null,0,0,0,\'' + d.tag.permalink + '\',\'' + escape(d.tag.name) + '\');';
            } else if( s == 'locations' ) {
                return 'M.ciniki_artgallery_exhibitions.showMenu(null,0,\'' + d.location.id + '\',0,\'\',\'' + escape(d.location.name) + '\');';
            } else if( s == 'sellers' ) {
                return 'M.ciniki_artgallery_exhibitions.showMenu(null,0,0,\'' + d.customer.id + '\',\'\',\'' + escape(d.customer.first + ' ' + d.customer.last) + '\');';
            }
        };
        this.menu.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) {
                M.api.getJSONBgCb('ciniki.artgallery.exhibitionSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'15'}, 
                    function(rsp) { 
                        M.ciniki_artgallery_exhibitions.menu.liveSearchShow('search', null, M.gE(M.ciniki_artgallery_exhibitions.menu.panelUID + '_' + s), rsp.exhibitions); 
                    });
                return true;
            }
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            return this.cellValue(s, i, j, d);
        };
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return this.rowFn(s, i, d);
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showMenu();\',0);');
        this.menu.addClose('Back');

        //
        // The exhibition panel will show the information for a exhibition/sponsor/organizer
        //
        this.exhibition = new M.panel('Exhibition',
            'ciniki_artgallery_exhibitions', 'exhibition',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.artgallery.exhibitions.exhibition');
        this.exhibition.data = {};
        this.exhibition.exhibition_id = 0;
        this.exhibition.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
                }},
            'info':{'label':'', 'aside':'yes', 'list':{
                'name':{'label':'Name'},
                'start_date':{'label':'Start'},
                'end_date':{'label':'End'},
                'location':{'label':'Location', 'visible':'no'},
                'location_text':{'label':'Location', 'visible':'no'},
                'categories_text':{'label':'Categories'},
                'webcollections_text':{'label':'Web Collections'},
                }},
            'short_description':{'label':'Synopsis', 'type':'htmlcontent'},
            'long_description':{'label':'Description', 'type':'htmlcontent'},
            'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline'],
                'noData':'No links added',
                'addTxt':'Add Link',
                'addFn':'M.startApp(\'ciniki.artgallery.exhibitionlinks\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_id\':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id,\'add\':\'yes\'});',
                },
            'images':{'label':'Gallery', 'type':'simplethumbs'},
            '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'addTxt':'Add Image',
                'addFn':'M.startApp(\'ciniki.artgallery.exhibitionimages\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_id\':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id,\'add\':\'yes\'});',
                },
            'sellers':{'label':'Inventory', 'visible':'no', 'type':'simplegrid', 'num_cols':5,
                'headerValues':['Seller', '# Items', 'Value', 'Fees', 'Net'],
                'cellClasses':['', ''],
                'sortable':'yes', 'sortTypes':['text', 'number', 'altnumber', 'altnumber', 'altnumber'],
                'noData':'No Inventory',
                'addTxt':'Add Seller',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'next\':\'M.ciniki_artgallery_exhibitions.inventoryAdd\',\'customer_id\':0});',
                'changeTxt':'Download Price List',
                'changeFn':'M.ciniki_artgallery_exhibitions.downloadPriceList(M.ciniki_artgallery_exhibitions.exhibition.exhibition_id,\'pdf\');',
            },
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showExhibition();\',M.ciniki_artgallery_exhibitions.exhibition.exhibition_id);'},
                'copy':{'label':'New Location', 'visible':'no', 'fn':'M.ciniki_artgallery_exhibitions.exhibitionNewLocation(M.ciniki_artgallery_exhibitions.exhibition.cb,M.ciniki_artgallery_exhibitions.exhibition.exhibition_id);'},
                }},
        };
        this.exhibition.sectionData = function(s) {
            if( s == 'images' ) { return this.data.images; }
            if( s == 'links' ) { return this.data.links; }
            if( s == 'sellers' ) { return this.data.sellers; }
            if( s == 'short_description' || s == 'long_description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
            return this.sections[s].list;
            };
        this.exhibition.listLabel = function(s, i, d) {
            if( s == 'info' ) { 
                return d.label; 
            }
            return null;
        };
        this.exhibition.listValue = function(s, i, d) {
            return this.data[i];
        };
        this.exhibition.fieldValue = function(s, i, d) {
            if( i == 'long_description' || i == 'short_description' ) { 
                return this.data[i].replace(/\n/g, '<br/>');
            }
            return this.data[i];
        };
        this.exhibition.cellValue = function(s, i, j, d) {
            if( s == 'links' && j == 0 ) {
                return '<span class="maintext">' + d.link.name + '</span><span class="subtext">' + d.link.url + '</span>';
            }
            if( s == 'images' && j == 0 ) { 
                if( d.image.image_id > 0 ) {
                    if( d.image.image_data != null && d.image.image_data != '' ) {
                        return '<img width="75px" height="75px" src=\'' + d.image.image_data + '\' />'; 
                    } else {
                        return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.artgallery.getImage', {'tnid':M.curTenantID, 'image_id':d.image.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />'; 
                    }
                } else {
                    return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
                }
            }
            if( s == 'images' && j == 1 ) { 
                return '<span class="maintext">' + d.image.name + '</span><span class="subtext">' + d.image.description + '</span>'; 
            }
            if( s == 'sellers' ) { 
                switch(j) {
                    case 0: return d.seller.first + ' ' + d.seller.last;
                    case 1: return d.seller.num_items;
                    case 2: return d.seller.total_price;
                    case 3: return d.seller.total_tenant_fee;
                    case 4: return d.seller.total_seller_amount;
                }
            }
        };
        this.exhibition.cellSortValue = function(s, i, j, d) {
            if( s == 'sellers' ) {
                switch(j) {
                    case 0: return d.seller.last;
                    case 1: return d.seller.num_items;
                    case 2: return d.seller.total_price.replace(/\$/, '');
                    case 3: return d.seller.total_tenant_fee.replace(/\$/, '');
                    case 4: return d.seller.total_seller_amount.replace(/\$/, '');
                }
            }
        };
        this.exhibition.rowFn = function(s, i, d) {
            if( s == 'links' ) {
                return 'M.startApp(\'ciniki.artgallery.exhibitionlinks\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_link_id\':\'' + d.link.id + '\'});';
            }
            else if( s == 'images' ) {
                return 'M.startApp(\'ciniki.artgallery.exhibitionimages\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_image_id\':\'' + d.image.id + '\'});';
            }
            else if( s == 'sellers' ) {
                return 'M.startApp(\'ciniki.artgallery.exhibitionitems\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_id\':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id,\'customer_id\':\'' + d.seller.id + '\'});';
            }
        };
        this.exhibition.thumbFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.artgallery.exhibitionimages\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_image_id\':\'' + d.image.id + '\'});';
        };
        this.exhibition.addDropImage = function(iid) {
            var rsp = M.api.getJSON('ciniki.artgallery.exhibitionImageAdd',
                {'tnid':M.curTenantID, 'image_id':iid, 
                'exhibition_id':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id});
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            return true;
        };
        this.exhibition.addDropImageRefresh = function() {
            if( M.ciniki_artgallery_exhibitions.exhibition.exhibition_id > 0 ) {
                var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet', {'tnid':M.curTenantID, 
                    'exhibition_id':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id, 'images':'yes'}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_artgallery_exhibitions.exhibition;
                        p.data.images = rsp.exhibition.images;
                        p.refreshSection('images');
                    });
            }
        };
        this.exhibition.addButton('edit', 'Edit', 'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showExhibition();\',M.ciniki_artgallery_exhibitions.exhibition.exhibition_id);');
        this.exhibition.addClose('Back');

        //
        // The edit panel for exhibition
        //
        this.edit = new M.panel('Edit',
            'ciniki_artgallery_exhibitions', 'edit',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.artgallery.exhibitions.edit');
        this.edit.data = {};
        this.edit.exhibition_id = 0;
        this.edit.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'info':{'label':'', 'aside':'yes', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'start_date':{'label':'Start', 'type':'date'},
                'end_date':{'label':'End', 'type':'date'},
                'location':{'label':'Location', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'location_id':{'label':'Location', 'type':'select', 'options':{}},
                'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
                }},
            '_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
                }},
            '_webcollections':{'label':'Web Collections', 'aside':'yes', 'active':'no', 'fields':{
                'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
                }},
            '_short_description':{'label':'Synopsis', 'fields':{
                'short_description':{'label':'', 'hidelabel':'yes', 'size':'small', 'type':'textarea'},
                }},
            '_long_description':{'label':'Description', 'fields':{
                'long_description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitions.saveExhibition();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitions.deleteExhibition();'},
                }},
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'location' ) {
                var rsp = M.api.getJSONBgCb('ciniki.artgallery.exhibitionSearchField', {'tnid':M.curTenantID, 'field':i, 'start_needle':value, 'limit':15},
                    function(rsp) {
                        M.ciniki_artgallery_exhibitions.edit.liveSearchShow(s, i, M.gE(M.ciniki_artgallery_exhibitions.edit.panelUID + '_' + i), rsp.results);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( (f == 'location' ) && d.result != null ) { return d.result.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( (f == 'location' )
                && d.result != null ) {
                return 'M.ciniki_artgallery_exhibitions.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
            }
        };
        this.edit.updateField = function(s, fid, result) {
            M.gE(this.panelUID + '_' + fid).value = unescape(result);
            this.removeLiveSearch(s, fid);
        };
        this.edit.fieldValue = function(s, i, d) {
            if( this.data[i] != null ) { return this.data[i]; }
            return '';
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.artgallery.exhibitionHistory','args':{'tnid':M.curTenantID, 
                'exhibition_id':M.ciniki_artgallery_exhibitions.edit.exhibition_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            this.setFieldValue('primary_image_id', iid);
            return true;
        };
        this.edit.deleteImage = function(fid) {
            this.setFieldValue(fid, 0, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitions.saveExhibition();');
        this.edit.addClose('Cancel');

        //
        // The new location panel for exhibition
        //
        this.newlocation = new M.panel('New Location',
            'ciniki_artgallery_exhibitions', 'newlocation',
            'mc', 'medium', 'sectioned', 'ciniki.artgallery.exhibitions.newlocation');
        this.newlocation.data = {};
        this.newlocation.old_exhibition_id = 0;
        this.newlocation.sections = {
            'info':{'label':'', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'start_date':{'label':'Start', 'type':'date'},
                'end_date':{'label':'End', 'type':'date'},
                'location':{'label':'Location', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'location_id':{'label':'Location', 'type':'select', 'options':{}},
                'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
                }},
            '_categories':{'label':'Categories', 'active':'no', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
                }},
            '_webcollections':{'label':'Web Collections', 'active':'no', 'fields':{
                'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitions.exhibitionCopy();'},
                }},
        };
        this.newlocation.fieldValue = function(s, i, d) {
            if( this.data[i] != null ) { return this.data[i]; }
            return '';
        };
        this.newlocation.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitions.exhibitionCopy();');
        this.newlocation.addClose('Cancel');
    }
    
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Check if web collections are enabled
        //
        if( M.curTenant.modules['ciniki.web'] != null 
            && (M.curTenant.modules['ciniki.web'].flags&0x08) > 0 ) {
            this.exhibition.sections.info.list.webcollections_text.visible = 'yes';
            this.edit.sections._webcollections.active = 'yes';
            this.newlocation.sections._webcollections.active = 'yes';
        } else {
            this.exhibition.sections.info.list.webcollections_text.visible = 'no';
            this.edit.sections._webcollections.active = 'no';
            this.newlocation.sections._webcollections.active = 'no';
        }
    
        //
        // Check if locations or exhibition inventory is 
        //
        if( M.curTenant.modules['ciniki.artgallery'] != null 
            && (M.curTenant.modules['ciniki.artgallery'].flags&0x05) > 0 ) {
            this.menu.size = 'medium mediumaside';
            var tab_count = 0;
            if( (M.curTenant.modules['ciniki.artgallery'].flags&0x02) > 0 ) {
                tab_count++;
                this.menu.sections._tabs.selected = 'sellers';
                this.menu.sections._tabs.tabs.sellers.visible = 'yes';
                this.menu.sections.sellers.visible = 'yes';
                this.exhibition.sections.sellers.visible = 'yes';
            } else {
                this.menu.sections._tabs.tabs.sellers.visible = 'no';
                this.menu.sections.sellers.visible = 'no';
                this.exhibition.sections.sellers.visible = 'no';
            }
            if( (M.curTenant.modules['ciniki.artgallery'].flags&0x01) > 0 ) {
                tab_count++;
                this.menu.sections._tabs.selected = 'locations';
                this.menu.sections._tabs.tabs.locations.visible = 'yes';
                this.menu.sections.locations.visible = 'yes';
                this.exhibition.sections.info.list.location.visible = 'no';
                this.exhibition.sections.info.list.location_text.visible = 'yes';
                this.edit.sections.info.fields.location_id.active = 'yes';
                this.newlocation.sections.info.fields.location_id.active = 'yes';
                this.exhibition.sections._buttons.buttons.copy.visible = 'yes';
            } else {
                this.menu.sections._tabs.tabs.locations.visible = 'no';
                this.menu.sections.locations.visible = 'no';
                this.exhibition.sections.info.list.location.visible = 'yes';
                this.exhibition.sections.info.list.location_text.visible = 'no';
                this.edit.sections.info.fields.location_id.active = 'no';
                this.newlocation.sections.info.fields.location_id.active = 'no';
                this.exhibition.sections._buttons.buttons.copy.visible = 'no';
            }
            if( (M.curTenant.modules['ciniki.artgallery'].flags&0x04) > 0 ) {
                tab_count++;
                this.menu.sections._tabs.selected = 'categories';
                this.menu.sections._tabs.tabs.categories.visible = 'yes';
                this.menu.sections.categories.visible = 'yes';
                this.exhibition.sections.info.list.categories_text.visible = 'yes';
                this.edit.sections._categories.active = 'yes';
                this.newlocation.sections._categories.active = 'yes';
            } else {
                this.menu.sections._tabs.tabs.categories.visible = 'no';
                this.menu.sections.categories.visible = 'no';
                this.exhibition.sections.info.list.categories_text.visible = 'no';
                this.edit.sections._categories.active = 'no';
                this.newlocation.sections._categories.active = 'no';
            }
            if( tab_count > 1 ) {
                this.menu.sections._tabs.visible = 'yes';
            } else {
                this.menu.sections._tabs.visible = 'no';
            }
            this.edit.sections.info.fields.location.active = 'no';
            this.newlocation.sections.info.fields.location.active = 'no';
            // Setup visible sections under tabs
            for(var i in this.menu.sections._tabs.tabs) {
                this.menu.sections[i].visible = (i==this.menu.sections._tabs.selected?'yes':'no');
            }
        } else {
            this.menu.size = 'medium';
            this.menu.sections._tabs.visible = 'no';
            this.menu.sections.locations.visible = 'no';
            this.menu.sections.sellers.visible = 'no';
            this.exhibition.sections.sellers.visible = 'no';
            this.exhibition.sections.info.list.location.visible = 'yes';
            this.exhibition.sections.info.list.categories_text.visible = 'no';
            this.edit.sections._categories.visible = 'no';
            this.edit.sections.info.fields.location.active = 'yes';
            this.edit.sections.info.fields.location_id.active = 'no';
            this.newlocation.sections._categories.visible = 'no';
            this.newlocation.sections.info.fields.location.active = 'yes';
            this.newlocation.sections.info.fields.location_id.active = 'no';
            this.exhibition.sections.info.list.location_text.visible = 'no';
            this.exhibition.sections._buttons.buttons.copy.visible = 'no';
        }

        //
        // Check if inventory should be shown
        //
//      if( M.curTenant.modules['ciniki.artgallery'] != null 
//          && (M.curTenant.modules['ciniki.artgallery'].flags&0x02) > 0 ) {
//          this.exhibition.sections.sellers.visible = 'yes';
//      } else {
//          this.exhibition.sections.sellers.visible = 'no';
//      }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitions', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }
    
        this.showMenu(cb,0,0,0,'','');
    }

    this.showMenu = function(cb, year, lid, cid, category, label) {
        if( year != null ) { 
    //      if( year != this.menu.year ) { this.menu.location_id = 0; }
            this.menu.year = year; 
        }
        if( lid != null ) { this.menu.location_id = lid; }
        if( cid != null ) { this.menu.customer_id = cid; }
        if( category != null ) { this.menu.category = category; }
        if( label != null ) { this.menu.sections.exhibitions.label = unescape(label); }
        if( this.menu.location_id > 0 ) {
            this.menu.addButton('edit', 'Edit', 'M.startApp(\'ciniki.artgallery.locations\',null,\'M.ciniki_artgallery_exhibitions.showMenu()\',\'mc\',{\'location_id\':\'' + this.menu.location_id + '\'});');
        } else {
            this.menu.delButton('edit');
        }
        // Get the list of existing artgallery
        M.api.getJSONCb('ciniki.artgallery.exhibitionList', 
            {'tnid':M.curTenantID, 'year':this.menu.year, 'years':'yes', 'locations':'yes', 'sellers':'yes', 'categories':'yes',
                'location_id':this.menu.location_id, 'customer_id':this.menu.customer_id, 'category':this.menu.category}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_artgallery_exhibitions.menu;
                    p.sections.years.tabs = {};
                    p.data = rsp;
                    if( rsp.years != null && rsp.years != '' ) {
                        years = rsp.years.split(',');
                        if( years.length > 1 ) {
                            if( rsp.year != null && rsp.year != '' ){
                                p.year = rsp.year;
                                p.sections.years.selected = rsp.year;
                            }
                            for(i in years) {
                                p.sections.years.tabs[years[i]] = {'label':years[i], 'fn':'M.ciniki_artgallery_exhibitions.showMenu(null,' + years[i] + ');'};
                            }
                            p.sections.years.visible = 'yes';
                        } else {
                            p.sections.years.visible = 'no';
                        }
                    } else {
                        p.sections.years.visible = 'no';
                    }
                    p.data.location_notes = '';
                    if( p.sections._tabs.tabs.locations.visible == 'yes' && p.sections._tabs.selected == 'locations'
                        && p.location_id != null && p.location_id > 0 && rsp.location != null && rsp.location.notes != null && rsp.location.notes != '' 
                        ) {
                        p.sections.location_notes.visible = 'yes';
                        p.data.location_notes = rsp.location.notes;
                    } else {
                        p.sections.location_notes.visible = 'no';
                    }
                    if( p.sections._tabs.tabs.categories.visible == 'yes' && p.sections._tabs.selected == 'categories' 
                        && rsp.categories != null && rsp.categories.length > 0 
                        ) {
                        p.sections.categories.visible = 'yes';
                    } else {
                        p.sections.categories.visible = 'no';
                    }
                    if( p.sections._tabs.tabs.sellers.visible == 'yes' && p.sections._tabs.selected == 'sellers'
                        && rsp.sellers != null && rsp.sellers.length > 0 
                        ) {
                        p.sections.sellers.visible = 'yes';
                    } else {
                        p.sections.sellers.visible = 'no';
                    }
                    p.refresh();
                    p.show(cb);
                }); 
    };

    //
    // The edit form takes care of editing existing, or add new.
    // It can also be used to add the same person to an artgallery
    // as an exhibition and sponsor and organizer, etc.
    //
    this.showEdit = function(cb, mid) {
        this.edit.reset();
        this.edit.data = {};
        if( mid != null ) { this.edit.exhibition_id = mid; }
        this.edit.sections._buttons.buttons.delete.visible = (this.edit.exhibition_id>0?'yes':'no');
        var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet', {'tnid':M.curTenantID, 
            'exhibition_id':this.edit.exhibition_id, 'categories':'yes', 'locations':'yes', 'webcollections':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_artgallery_exhibitions.edit;
                p.data = rsp.exhibition;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                    }
                }
                var locations = {'0':'--'};
                if( rsp.locations != null ) {
                    for(i in rsp.locations) {
                        locations[rsp.locations[i].location.id] = rsp.locations[i].location.name;
                    }
                }
                p.sections.info.fields.location_id.options = locations;
                p.refresh();
                p.show(cb);
            });
    };

    this.showExhibition = function(cb, mid) {
        this.exhibition.reset();
        if( mid != null ) { this.exhibition.exhibition_id = mid; }
        var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet',
            {'tnid':M.curTenantID, 'exhibition_id':this.exhibition.exhibition_id, 
            'links':'yes', 'images':'yes', 'webcollections':'yes', 'sellers':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_artgallery_exhibitions.exhibition;
                p.data = rsp.exhibition;

                if( (M.curTenant.modules['ciniki.artgallery'].flags&0x01) == 0 && rsp.exhibition.location != null && rsp.exhibition.location != '' ) {
                    p.sections.info.list.location.visible = 'yes';
                } else {
                    p.sections.info.list.location.visible = 'no';
                }
                if( rsp.exhibition.short_description != null && rsp.exhibition.short_description != '' ) {
                    p.sections.short_description.visible = 'yes';
                } else {
                    p.sections.short_description.visible = 'no';
                }
                if( rsp.exhibition.long_description != null && rsp.exhibition.long_description != '' ) {
                    p.sections.long_description.visible = 'yes';
                } else {
                    p.sections.long_description.visible = 'no';
                }
                if( rsp.exhibition.sellers != null && rsp.exhibition.sellers.length > 0 ) {
                    p.sections.sellers.changeTxt = 'Download Price List';
                } else {
                    p.sections.sellers.changeTxt = '';
                }
                if( rsp.exhibition.categories != null ) {
                    p.data.categories_text = rsp.exhibition.categories.replace(/::/g, ', ');
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.saveExhibition = function() {
        if( this.edit.exhibition_id > 0 ) {
            // Update exhibition
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionUpdate', 
                    {'tnid':M.curTenantID, 'exhibition_id':this.edit.exhibition_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_artgallery_exhibitions.edit.close();
                    });
            } else {
                M.ciniki_artgallery_exhibitions.edit.close();
            }
        } else {
            // Add exhibition
            var c = this.edit.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_artgallery_exhibitions.edit.close();
                });
        }
    };

    this.inventoryAdd = function(cid) {
        M.startApp('ciniki.artgallery.exhibitionitems',null,'M.ciniki_artgallery_exhibitions.showExhibition();','mc',{'item_id':0, 'customer_id':cid, 'exhibition_id':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id});
    }

    this.exhibitionNewLocation = function(cb, eid) {
        if( eid != null ) { this.newlocation.old_exhibition_id = eid; }
        var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet', {'tnid':M.curTenantID, 
            'exhibition_id':this.newlocation.old_exhibition_id, 'categories':'yes', 'locations':'yes', 'webcollections':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_artgallery_exhibitions.newlocation;
                p.data = rsp.exhibition;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                    }
                }
                var locations = {'0':'--'};
                if( rsp.locations != null ) {
                    for(i in rsp.locations) {
                        locations[rsp.locations[i].location.id] = rsp.locations[i].location.name;
                    }
                }
                p.sections.info.fields.location_id.options = locations;
                p.data.location_id = 0;
                p.refresh();
                p.show(cb);
            });
    };

    this.exhibitionCopy = function() {
        // Add exhibition
        var c = this.newlocation.serializeForm('yes');
        var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionDuplicate', 
            {'tnid':M.curTenantID, 'old_exhibition_id':this.newlocation.old_exhibition_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_artgallery_exhibitions.newlocation.close();
            });
    };

    this.deleteExhibition = function() {
        if( confirm("Are you sure you want to remove this exhibition and all images and links?") ) {
            var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionDelete', 
                {'tnid':M.curTenantID, 'exhibition_id':M.ciniki_artgallery_exhibitions.edit.exhibition_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_artgallery_exhibitions.exhibition.close();
                });
        }
    };

    this.downloadPriceList = function(eid, format) {
        var args = {'tnid':M.curTenantID, 'exhibition_id':eid, 'output':format};
        M.api.openPDF('ciniki.artgallery.exhibitionPriceList', args);
    };

}
