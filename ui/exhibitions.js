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
		this.menu.data = {};
		this.menu.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'_':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No exhibitions',
				'addTxt':'Add Exhibition',
				'addFn':'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showMenu();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data; }
		this.menu.cellValue = function(s, i, j, d) {
			var location = '';
			if( d.exhibition.location != '' ) {
				location = ' <span class="subdue">' + d.exhibition.location + '</span>';
			}
			return '<span class="maintext">' + d.exhibition.name + location + '</span>'
				+ '<span class="subtext">' + d.exhibition.start_date + ' - ' + d.exhibition.end_date + '</span>';
		};
		this.menu.rowFn = function(s, i, d) { 
			return 'M.ciniki_artgallery_exhibitions.showExhibition(\'M.ciniki_artgallery_exhibitions.showMenu();\',\'' + d.exhibition.id + '\');'; 
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
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'start_date':{'label':'Start'},
				'end_date':{'label':'End'},
				'location':{'label':'Location', 'visible':'no'},
				'webcollections_text':{'label':'Web Collections'},
				}},
			'short_description':{'label':'Brief Description', 'type':'htmlcontent'},
			'long_description':{'label':'Full Description', 'type':'htmlcontent'},
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
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_artgallery_exhibitions.showEdit(\'M.ciniki_artgallery_exhibitions.showExhibition();\',M.ciniki_artgallery_exhibitions.exhibition.exhibition_id);'},
				}},
		};
		this.exhibition.sectionData = function(s) {
			if( s == 'images' ) { return this.data.images; }
			if( s == 'links' ) { return this.data.links; }
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
						return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.artgallery.getImage', {'business_id':M.curBusinessID, 'image_id':d.image.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />'; 
					}
				} else {
					return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
				}
			}
			if( s == 'images' && j == 1 ) { 
				return '<span class="maintext">' + d.image.name + '</span><span class="subtext">' + d.image.description + '</span>'; 
			}
		};
		this.exhibition.rowFn = function(s, i, d) {
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.artgallery.exhibitionlinks\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_link_id\':\'' + d.link.id + '\'});';
			}
			if( s == 'images' ) {
				return 'M.startApp(\'ciniki.artgallery.exhibitionimages\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_image_id\':\'' + d.image.id + '\'});';
			}
		};
		this.exhibition.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
			}
		};
		this.exhibition.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.exhibition.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.exhibition.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.artgallery.exhibitionimages\',null,\'M.ciniki_artgallery_exhibitions.showExhibition();\',\'mc\',{\'exhibition_image_id\':\'' + d.image.id + '\'});';
		};
		this.exhibition.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.artgallery.exhibitionImageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 
				'exhibition_id':M.ciniki_artgallery_exhibitions.exhibition.exhibition_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.exhibition.addDropImageRefresh = function() {
			if( M.ciniki_artgallery_exhibitions.exhibition.exhibition_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet', {'business_id':M.curBusinessID, 
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
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
			}},
			'info':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'start_date':{'label':'Start', 'type':'date'},
				'end_date':{'label':'End', 'type':'date'},
				'location':{'label':'Location', 'type':'text'},
				'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
				}},
			'_webcollections':{'label':'Web Collections', 'aside':'yes', 'active':'no', 'fields':{
				'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
				}},
			'_short_description':{'label':'Brief Description', 'fields':{
				'short_description':{'label':'', 'hidelabel':'yes', 'size':'small', 'type':'textarea'},
				}},
			'_long_description':{'label':'Full Description', 'fields':{
				'long_description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitions.saveExhibition();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitions.deleteExhibition();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.exhibitionHistory','args':{'business_id':M.curBusinessID, 
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
	}
	
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Check if web collections are enabled
		//
		if( M.curBusiness.modules['ciniki.web'] != null 
			&& (M.curBusiness.modules['ciniki.web'].flags&0x08) ) {
			this.exhibition.sections.info.list.webcollections_text.visible = 'yes';
			this.edit.sections._webcollections.active = 'yes';
		} else {
			this.exhibition.sections.info.list.webcollections_text.visible = 'no';
			this.edit.sections._webcollections.active = 'no';
		}

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitions', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}
	
		this.showMenu(cb);
	}

	this.showMenu = function(cb, year) {
		if( year != null ) { this.menu.year = year; }
		// Get the list of existing artgallery
		var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionList', 
			{'business_id':M.curBusinessID, 'year':this.menu.year, 'years':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_exhibitions.menu;
				p.sections.years.tabs = {};
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
				}
				p.data = rsp.exhibitions;
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
		if( mid != null ) {
			this.edit.exhibition_id = mid;
		}
		if( this.edit.exhibition_id > 0 ) {
//			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet', {'business_id':M.curBusinessID, 
				'exhibition_id':this.edit.exhibition_id, 'webcollections':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_artgallery_exhibitions.edit;
					p.data = rsp.exhibition;
					p.refresh();
					p.show(cb);
				});
		} else if( this.edit.sections._webcollections.active == 'yes' ) {
			this.edit.reset();
			this.edit.data = {};
			// Get the list of collections
			M.api.getJSONCb('ciniki.web.collectionList', {'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_exhibitions.edit;
				p.data = {};
				if( rsp.collections != null ) {
					p.data['_webcollections'] = rsp.collections;
				}
				p.refresh();
				p.show(cb);
			});
		} else {
			this.edit.reset();
			this.edit.data = {};
//			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.showExhibition = function(cb, mid) {
		if( mid != null ) {
			this.exhibition.exhibition_id = mid;
		}
		var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionGet',
			{'business_id':M.curBusinessID, 'exhibition_id':this.exhibition.exhibition_id, 
			'links':'yes', 'images':'yes', 'webcollections':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_exhibitions.exhibition;
				p.data = rsp.exhibition;

				if( rsp.exhibition.long_description != null && rsp.exhibition.long_description != '' ) {
					p.sections.long_description.visible = 'yes';
				} else {
					p.sections.long_description.visible = 'no';
				}
				if( rsp.exhibition.short_description != null && rsp.exhibition.short_description != '' ) {
					p.sections.short_description.visible = 'yes';
				} else {
					p.sections.short_description.visible = 'no';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.saveExhibition = function() {
		if( this.edit.exhibition_id > 0 ) {
			// Update contact
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionUpdate', 
					{'business_id':M.curBusinessID, 'exhibition_id':this.edit.exhibition_id}, c, function(rsp) {
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
			// Add contact
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_artgallery_exhibitions.edit.close();
				});
		}
	};

	this.deleteExhibition = function() {
		if( confirm("Are you sure you want to remove this exhibition and all images and links?") ) {
			var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionDelete', 
				{'business_id':M.curBusinessID, 'exhibition_id':M.ciniki_artgallery_exhibitions.edit.exhibition_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_artgallery_exhibitions.exhibition.close();
				});
		}
	};
}
