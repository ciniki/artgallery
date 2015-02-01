//
// The artgallery app to manage an artists collection
//
function ciniki_artgallery_info() {
	this.init = function() {
		//
		// Setup the main panel to list the collection
		//
		this.menu = new M.panel('Files',
			'ciniki_artgallery_info', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.menu');
		this.menu.data = {};
		this.menu.sections = {
			'_menu':{'label':'', 'list':{
				'membership':{'label':'Membership Application', 'fn':'M.ciniki_artgallery_info.showMembership(\'M.ciniki_artgallery_info.showMenu();\');'},
				'exhibition':{'label':'Exhibition Application', 'fn':'M.ciniki_artgallery_info.showExhibition(\'M.ciniki_artgallery_info.showMenu();\');'},
				}},
			};
		this.menu.addClose('Back');

		//
		// The panel to display the add form
		//
		this.membership = new M.panel('Membership Details',
			'ciniki_artgallery_info', 'membership',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.membership');
		this.membership.data = {};	
		this.membership.sections = {
			'membership-details-html':{'label':'Membership Details', 'type':'htmlcontent'},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_artgallery_info.showEditMembership(\'M.ciniki_artgallery_info.showMembership();\');'},
				}},
			'applications':{'label':'Application Forms',
				'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add Application',
				'addFn':'M.ciniki_artgallery_info.showAddFile(\'M.ciniki_artgallery_info.showMembership();\',1);',
				}
		};
		this.membership.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return d.file.name; }
		};
		this.membership.rowFn = function(s, i, d) {
			return 'M.ciniki_artgallery_info.showEditFile(\'M.ciniki_artgallery_info.showMembership();\', \'' + d.file.id + '\');'; 
		};
		this.membership.sectionData = function(s) { 
			return this.data[s];
		};
		this.membership.addClose('Back');

		//
		// The panel to display the edit membership details form
		//
		this.editmembership = new M.panel('Membership',
			'ciniki_artgallery_info', 'editmembership',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.editmembership');
		this.editmembership.file_id = 0;
		this.editmembership.data = null;
		this.editmembership.sections = {
			'_description':{'label':'Description', 'type':'simpleform', 'fields':{
				'membership-details':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_info.saveMembership();'},
			}},
		};
		this.editmembership.fieldValue = function(s, i, d) { 
			return this.data[i]; 
		}
		this.editmembership.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.settingsHistory','args':{'business_id':M.curBusinessID, 'setting':i}};
		};
		this.editmembership.addButton('save', 'Save', 'M.ciniki_artgallery_info.saveMembership();');
		this.editmembership.addClose('Cancel');

		//
		// The panel to display the exhibition form
		//
		this.exhibition = new M.panel('Exhibition Application',
			'ciniki_artgallery_info', 'exhibition',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.exhibition');
		this.exhibition.data = {};	
		this.exhibition.sections = {
			'exhibition-application-details-html':{'label':'Application Details', 'type':'htmlcontent'},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_artgallery_info.showEditExhibition(\'M.ciniki_artgallery_info.showExhibition();\');'},
				}},
			'applications':{'label':'Application Forms',
				'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add Application',
				'addFn':'M.ciniki_artgallery_info.showAddFile(\'M.ciniki_artgallery_info.showExhibition();\',2);',
				}
		};
		this.exhibition.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return d.file.name; }
		};
		this.exhibition.rowFn = function(s, i, d) {
			return 'M.ciniki_artgallery_info.showEditFile(\'M.ciniki_artgallery_info.showExhibition();\', \'' + d.file.id + '\');'; 
		};
		this.exhibition.sectionData = function(s) { 
			return this.data[s];
		};
		this.exhibition.addClose('Back');

		//
		// The panel to display the edit exhibition details form
		//
		this.editexhibition = new M.panel('Exhibition',
			'ciniki_artgallery_info', 'editexhibition',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.editexhibition');
		this.editexhibition.file_id = 0;
		this.editexhibition.data = null;
		this.editexhibition.sections = {
			'_description':{'label':'Description', 'type':'simpleform', 'fields':{
				'exhibition-application-details':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_info.saveExhibition();'},
			}},
		};
		this.editexhibition.fieldValue = function(s, i, d) { 
			return this.data[i]; 
		}
		this.editexhibition.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.settingsHistory','args':{'business_id':M.curBusinessID, 'setting':i}};
		};
		this.editexhibition.addButton('save', 'Save', 'M.ciniki_artgallery_info.saveExhibition();');
		this.editexhibition.addClose('Cancel');

		//
		// The panel to display the add form
		//
		this.addfile = new M.panel('Add File',
			'ciniki_artgallery_info', 'addfile',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.editfile');
		this.addfile.default_data = {'type':'1'};
		this.addfile.data = {};	
		this.addfile.sections = {
			'_file':{'label':'File', 'fields':{
				'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
			}},
			'info':{'label':'Information', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
			}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_info.addFile();'},
			}},
		};
		this.addfile.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) {
				return this.data[i]; 
			} 
			return ''; 
		};
		this.addfile.addButton('save', 'Save', 'M.ciniki_artgallery_info.addFile();');
		this.addfile.addClose('Cancel');

		//
		// The panel to display the edit form
		//
		this.editfile = new M.panel('File',
			'ciniki_artgallery_info', 'editfile',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.info.editfiles');
		this.editfile.file_id = 0;
		this.editfile.data = null;
		this.editfile.sections = {
			'info':{'label':'Details', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
			}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_info.saveFile();'},
				'download':{'label':'Download', 'fn':'M.ciniki_artgallery_info.downloadFile(M.ciniki_artgallery_info.editfile.file_id);'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_info.deleteFile();'},
			}},
		};
		this.editfile.fieldValue = function(s, i, d) { 
			return this.data[i]; 
		}
		this.editfile.sectionData = function(s) {
			return this.data[s];
		};
		this.editfile.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.fileHistory','args':{'business_id':M.curBusinessID, 
				'file_id':M.ciniki_artgallery_info.editfile.file_id, 'field':i}};
		};
		this.editfile.addButton('save', 'Save', 'M.ciniki_artgallery_info.saveFile();');
		this.editfile.addClose('Cancel');
	}

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_info', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		this.showMenu(cb);
	}

	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	};

	this.showMembership = function(cb) {
		this.membership.data = {};
		M.startLoad();
		var rsp = M.api.getJSONCb('ciniki.artgallery.settingsGet', 
			{'business_id':M.curBusinessID, 'processhtml':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.stopLoad();
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_info.membership;
				if( rsp.settings != null && rsp.settings['membership-details'] != null ) {
					p.data['membership-details-html'] = rsp.settings['membership-details-html'];
				} else {
					p.data['membership-details-html'] = '';
				}
				var rsp = M.api.getJSON('ciniki.artgallery.fileList', 
					{'business_id':M.curBusinessID, 'type':'1'});
				if( rsp.stat != 'ok' ) {
					M.stopLoad();
					M.api.err(rsp);
					return false;
				}
				p.data.applications = rsp.files;
				M.stopLoad();
				p.refresh();
				p.show(cb);
			});
	};

	this.showEditMembership = function(cb) {
		this.editmembership.data = {};
		var rsp = M.api.getJSONCb('ciniki.artgallery.settingsGet', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_info.editmembership;
				if( rsp.settings != null && rsp.settings['membership-details'] != null ) {
					p.data['membership-details'] = rsp.settings['membership-details'];
				} else {
					p.data['membership-details'] = '';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.saveMembership = function() {
		var c = this.editmembership.serializeFormData('no');
		if( c != null ) {
			var rsp = M.api.postJSONFormData('ciniki.artgallery.settingsUpdate', 
				{'business_id':M.curBusinessID}, c,
				function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} else {
						M.ciniki_artgallery_info.editmembership.close();
					}
				});
		} else {
			M.ciniki_artgallery_info.editmembership.close();
		}
	};

	this.showExhibition = function(cb) {
		this.exhibition.data = {};
		var rsp = M.api.getJSONCb('ciniki.artgallery.settingsGet', 
			{'business_id':M.curBusinessID, 'processhtml':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_info.exhibition;
				if( rsp.settings != null && rsp.settings['exhibition-application-details'] != null ) {
					p.data['exhibition-application-details-html'] = rsp.settings['exhibition-application-details-html'];
				} else {
					p.data['exhibition-application-details-html'] = '';
				}
				var rsp = M.api.getJSONCb('ciniki.artgallery.fileList', 
					{'business_id':M.curBusinessID, 'type':'2'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						var p = M.ciniki_artgallery_info.exhibition;
						p.data.applications = rsp.files;
						p.refresh();
						p.show(cb);
					});
			});
	};

	this.showEditExhibition = function(cb) {
		this.editexhibition.data = {};
		var rsp = M.api.getJSONCb('ciniki.artgallery.settingsGet', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_info.editexhibition;
				if( rsp.settings != null && rsp.settings['exhibition-application-details'] != null ) {
					p.data['exhibition-application-details'] = rsp.settings['exhibition-application-details'];
				} else {
					p.data['exhibition-application-details'] = '';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.saveExhibition = function() {
		var c = this.editexhibition.serializeFormData('no');
		if( c != null ) {
			var rsp = M.api.postJSONFormData('ciniki.artgallery.settingsUpdate', 
				{'business_id':M.curBusinessID}, c,
				function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} else {
						M.ciniki_artgallery_info.editexhibition.close();
					}
				});
		} else {
			M.ciniki_artgallery_info.editexhibition.close();
		}
	};

	this.showAddFile = function(cb, type) {
		this.addfile.reset();
		this.addfile.data = {'type':type};
		this.addfile.refresh();
		this.addfile.show(cb);
	};

	this.addFile = function() {
		var c = this.addfile.serializeFormData('yes');

		if( c != '' ) {
			var rsp = M.api.postJSONFormData('ciniki.artgallery.fileAdd', 
				{'business_id':M.curBusinessID, 'type':this.addfile.data.type}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} else {
							M.ciniki_artgallery_info.addfile.close();
						}
					});
		} else {
			M.ciniki_artgallery_info.addfile.close();
		}
	};

	this.showEditFile = function(cb, fid) {
		if( fid != null ) {
			this.editfile.file_id = fid;
		}
		var rsp = M.api.getJSONCb('ciniki.artgallery.fileGet', {'business_id':M.curBusinessID, 
			'file_id':this.editfile.file_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_info.editfile;
				p.data = rsp.file;
				p.refresh();
				p.show(cb);
			});
	};

	this.saveFile = function() {
		var c = this.editfile.serializeFormData('no');

		if( c != '' ) {
			var rsp = M.api.postJSONFormData('ciniki.artgallery.fileUpdate', 
				{'business_id':M.curBusinessID, 'file_id':this.editfile.file_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} else {
							M.ciniki_artgallery_info.editfile.close();
						}
					});
		}
	};

	this.deleteFile = function() {
		if( confirm('Are you sure you want to delete \'' + this.editfile.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
			var rsp = M.api.getJSONCb('ciniki.artgallery.fileDelete', {'business_id':M.curBusinessID, 
				'file_id':M.ciniki_artgallery_info.editfile.file_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_artgallery_info.editfile.close();
				});
		}
	};

	this.downloadFile = function(fid) {
		M.api.openFile('ciniki.artgallery.fileDownload', {'business_id':M.curBusinessID, 'file_id':fid});
	};
}
