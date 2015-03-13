//
// The locations app to manage locations for an artgallery
//
function ciniki_artgallery_locations() {
	this.init = function() {
		//
		// The edit panel for location
		//
		this.edit = new M.panel('Edit',
			'ciniki_artgallery_locations', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.artgallery.locations.edit');
		this.edit.data = {};
		this.edit.location_id = 0;
		this.edit.sections = {
			'info':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'address1':{'label':'Address', 'type':'text'},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text'},
				'province':{'label':'Province', 'type':'text'},
				'postal':{'label':'Postal', 'type':'text'},
				'url':{'label':'URL', 'type':'text', 'hint':''},
				}},
			'_map':{'label':'Location Map', 'aside':'yes', 'visible':'yes', 'fields':{
//				'page-contact-google-map':{'label':'Display Map', 'type':'multitoggle', 'default':'no', 'toggles':this.activeToggles},
				'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
				'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
				}},
			'_map_buttons':{'label':'', 'aside':'yes', 'buttons':{
				'_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_artgallery_locations.edit.lookupLatLong();'},
				}},
			'_notes':{'label':'Notes', 'active':'yes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_locations.locationSave();'},
				'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_artgallery_locations.locationDelete();'},
				}},
		};
		this.edit.lookupLatLong = function() {
			M.startLoad();
			if( document.getElementById('googlemaps_js') == null) {
				var script = document.createElement("script");
				script.id = 'googlemaps_js';
				script.type = "text/javascript";
				script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curBusiness.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_artgallery_locations.edit.lookupGoogleLatLong";
				document.body.appendChild(script);
			} else {
				this.lookupGoogleLatLong();
			}
		};
		this.edit.lookupGoogleLatLong = function() {
			var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( { 'address': address}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					M.ciniki_artgallery_locations.edit.setFieldValue('latitude', results[0].geometry.location.lat());
					M.ciniki_artgallery_locations.edit.setFieldValue('longitude', results[0].geometry.location.lng());
					M.stopLoad();
				} else {
					alert('We were unable to lookup your latitude/longitude, please check your address in Settings: ' + status);
					M.stopLoad();
				}
			});	
		};
		this.edit.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.locationHistory','args':{'business_id':M.curBusinessID, 
				'location_id':M.ciniki_artgallery_locations.edit.location_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_artgallery_locations.locationSave();');
		this.edit.addClose('Cancel');
	}
	
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_locations', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}
	
		this.locationEdit(cb,args.location_id);
	}

	//
	// The edit form takes care of editing existing, or add new.
	// It can also be used to add the same person to an artgallery
	// as an location and sponsor and organizer, etc.
	//
	this.locationEdit = function(cb, lid) {
		if( lid != null ) { this.edit.location_id = lid; }
		if( this.edit.location_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
		} else {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
		}
		M.api.getJSONCb('ciniki.artgallery.locationGet', {'business_id':M.curBusinessID, 'location_id':this.edit.location_id}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_artgallery_locations.edit;
			p.data = rsp.location;
			p.refresh();
			p.show(cb);
		});
	};

	this.locationSave = function() {
		if( this.edit.location_id > 0 ) {
			// Update contact
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.artgallery.locationUpdate', 
					{'business_id':M.curBusinessID, 'location_id':this.edit.location_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_artgallery_locations.edit.close();
					});
			} else {
				M.ciniki_artgallery_locations.edit.close();
			}
		} else {
			// Add contact
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.artgallery.locationAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_artgallery_locations.edit.close();
				});
		}
	};

	this.locationDelete = function() {
		if( confirm("Are you sure you want to remove this location?") ) {
			var rsp = M.api.getJSONCb('ciniki.artgallery.locationDelete', 
				{'business_id':M.curBusinessID, 'location_id':M.ciniki_artgallery_locations.edit.location_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_artgallery_locations.edit.close();
				});
		}
	};
}
