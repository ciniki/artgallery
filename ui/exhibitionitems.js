//
// This app will handle the listing, additions and deletions of markets.  These are associated business.
//
function ciniki_artgallery_exhibitionitems() {
	//
	// Panels
	//
	this.sellerFlags = {
		'1':{'name':'Paid'},
		};
	this.init = function() {
		//
		// The panel to display seller info and items
		//
		this.seller = new M.panel('Seller',
			'ciniki_artgallery_exhibitionitems', 'seller',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.artgallery.main.seller');
		this.seller.data = {};
		this.seller.exhibition_id = 0;
		this.seller.customer_id = 0;
		this.seller.sections = {
			'customer_details':{'label':'Seller', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				},
			'reports':{'label':'', 'list':{
				'summarypdf':{'label':'Summary (PDF)', 'fn':'M.ciniki_artgallery_exhibitionitems.downloadSellerSummary(M.ciniki_artgallery_exhibitionitems.seller.exhibition_id,\'pdf\',M.ciniki_artgallery_exhibitionitems.seller.customer_id);'},
				'pricelist':{'label':'Price List (PDF)', 'fn':'M.ciniki_artgallery_exhibitionitems.downloadPriceList(M.ciniki_artgallery_exhibitionitems.seller.exhibition_id,\'pdf\',M.ciniki_artgallery_exhibitionitems.seller.customer_id);'},
				}},
			'items':{'label':'Items', 'type':'simplegrid', 'num_cols':7,
				'headerValues':['Code', 'Item', 'Price', 'Fee%', 'Sell Date', 'Fees', 'Sell Price'],
				'cellClasses':['', 'multiline'],
				'sortable':'yes', 'sortTypes':['text', 'text', 'altnumber', 'altnumber', 'altnumber'],
				'noData':'No items',
			},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add Item', 'fn':'M.ciniki_artgallery_exhibitionitems.itemEdit(\'M.ciniki_artgallery_exhibitionitems.sellerShow();\',0,M.ciniki_artgallery_exhibitionitems.seller.customer_id);'},
				}},
		};
		this.seller.sectionData = function(s) {
			if( s == 'info' || s == 'reports' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.seller.listLabel = function(s, i, d) { 
			if( s == 'info' ) { return d.label; }
			return '';
		};
		this.seller.listValue = function(s, i, d) {
			if( s == 'reports' ) { return d.label; }
			return this.data[i];
		};
		this.seller.listFn = function(s, i, d) {
			if( s == 'reports' ) { return d.fn; }
			return null;
		};
		this.seller.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch (j) {
					case 0: return d.detail.label;
					case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
				}
			}
			else if( s == 'items' ) { 
				if( j == 1 ) {
					var details = '';
					if( d.item.medium != null && d.item.medium != '' ) {
						details += d.item.medium;
					}
					if( d.item.size != null && d.item.size != '' ) {
						details += (details!=''?', ':'') + d.item.size;
					}
					if( d.item.item_condition != null && d.item.item_condition != '' ) {
						details += (details!=''?', ':'') + d.item.item_condition;
					}
					if( details != '' ) {
						return '<span class="maintext">' + d.item.name + '</span><span class="subtext">' + details + '</span>';
					} else {
						return d.item.name;
					}
				}
				switch(j) {
					case 0: return d.item.code;
					case 1: return d.item.name;
					case 2: return d.item.price;
					case 3: return d.item.fee_percent;
					case 4: return d.item.sell_date;
					case 5: return d.item.business_fee;
					case 6: return d.item.seller_amount;
				}
			}
		};
		this.seller.cellSortValue = function(s, i, j, d) {
			if( s == 'items' ) {
				switch(j) {
					case 0: return d.item.code;
					case 2: return d.item.price.replace(/\$/, '');
					case 5: return d.item.business_fee.replace(/\$/, '');
					case 6: return d.item.seller_amount.replace(/\$/, '');
				}
			}
		};
		this.seller.rowFn = function(s, i, d) {
			if( s == 'items' ) {
				return 'M.ciniki_artgallery_exhibitionitems.itemEdit(\'M.ciniki_artgallery_exhibitionitems.sellerShow();\',\'' + d.item.id + '\',0);';
			}
			return null;
		};
		this.seller.footerValue = function(s, i, d) {
			if( s == 'items' && this.data.item_totals != null ) {
				switch(i) {
					case 0: return '';
					case 1: return '';
					case 2: return this.data.item_totals.price;
					case 3: return '';
					case 4: return '';
					case 5: return this.data.item_totals.business_fee;
					case 6: return this.data.item_totals.seller_amount;
				}
			}
		};
		this.seller.addButton('add', 'Item', 'M.ciniki_artgallery_exhibitionitems.itemEdit(\'M.ciniki_artgallery_exhibitionitems.sellerShow();\',0,M.ciniki_artgallery_exhibitionitems.seller.customer_id);');
		this.seller.addClose('Back');

		//
		// The panel to edit an item
		//
		this.itemedit = new M.panel('Market',
			'ciniki_artgallery_exhibitionitems', 'itemedit',
			'mc', 'medium', 'sectioned', 'ciniki.artgallery.main.itemedit');
		this.itemedit.data = null;
		this.itemedit.customer_id = 0;
		this.itemedit.item_id = 0;
        this.itemedit.sections = { 
            'general':{'label':'General', 'fields':{
                'code':{'label':'Code', 'hint':'', 'type':'text', 'size':'small', 'livesearch':'yes'},
                'name':{'label':'Name', 'hint':'', 'type':'text', 'livesearch':'yes'},
                'medium':{'label':'Medium', 'hint':'', 'type':'text'},
                'size':{'label':'Size', 'hint':'', 'type':'text'},
                'item_condition':{'label':'Condition', 'hint':'', 'type':'text'},
                'price':{'label':'Price', 'hint':'', 'type':'text', 'size':'small'},
                'fee_percent':{'label':'Fee %', 'hint':'', 'type':'text', 'size':'small', 'onchangeFn':'M.ciniki_artgallery_exhibitionitems.itemedit.calc'},
                'sell_date':{'label':'Sell Date', 'hint':'', 'type':'date', 'onchangeFn':'M.ciniki_artgallery_exhibitionitems.itemedit.calc'},
                'sell_price':{'label':'Sell Price', 'hint':'', 'type':'text', 'size':'small', 'onchangeFn':'M.ciniki_artgallery_exhibitionitems.itemedit.calc'},
                'business_fee':{'label':'Business Fee', 'hint':'', 'type':'text', 'size':'small'},
                'seller_amount':{'label':'Seller Amount', 'hint':'', 'type':'text', 'size':'small'},
                }}, 
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_artgallery_exhibitionitems.itemSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_artgallery_exhibitionitems.itemDelete();'},
				}},
            };  
		this.itemedit.liveSearchCb = function(s, i, value) {
			if( i == 'name' ) {
				var rsp = M.api.getJSONBgCb('ciniki.artgallery.exhibitionItemSearch', {'business_id':M.curBusinessID, 'customer_id':M.ciniki_artgallery_exhibitionitems.itemedit.customer_id, 'start_needle':value, 'limit':15},
					function(rsp) {
						M.ciniki_artgallery_exhibitionitems.itemedit.liveSearchShow(s, i, M.gE(M.ciniki_artgallery_exhibitionitems.itemedit.panelUID + '_' + i), rsp.results);
					});
			}
		};
		this.itemedit.liveSearchResultValue = function(s, f, i, j, d) {
			if( (f == 'name' ) && d.result != null ) { return d.result.name; }
			return '';
		};
		this.itemedit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( (f == 'name' )
				&& d.result != null ) {
				return 'M.ciniki_artgallery_exhibitionitems.itemedit.updateItem(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.code) + '\',\'' + escape(d.result.name) + '\',\'' + escape(d.result.price) + '\',\'' + escape(d.result.fee_percent) + '\');';
			}
		};
		this.itemedit.updateItem = function(s, fid, code, name, medium, size, item_condition, price, fee_percent) {
			M.gE(this.panelUID + '_code').value = unescape(code);
			M.gE(this.panelUID + '_name').value = unescape(name);
			M.gE(this.panelUID + '_medium').value = unescape(medium);
			M.gE(this.panelUID + '_size').value = unescape(size);
			M.gE(this.panelUID + '_item_condition').value = unescape(item_condition);
			M.gE(this.panelUID + '_price').value = unescape(price);
			M.gE(this.panelUID + '_fee_percent').value = unescape(fee_percent);
			this.removeLiveSearch(s, fid);
		};
		this.itemedit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.itemedit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artgallery.exhibitionItemHistory', 'args':{'business_id':M.curBusinessID, 
				'item_id':this.item_id, 'field':i}};
		}
		this.itemedit.calc = function(s, i) {
			var sell_date = this.formFieldValue(this.sections[s].fields['sell_date'], 'sell_date');
			if( sell_date == '' ) { return true; }
			var price = this.formFieldValue(this.sections[s].fields['price'], 'price');
			var sell_price = this.formFieldValue(this.sections[s].fields['sell_price'], 'sell_price');
			if( sell_price == '' ) {
				this.setFieldValue('sell_price', price);
				sell_price = price;
			}
			var fee_percent = parseFloat(this.formFieldValue(this.sections[s].fields['fee_percent'], 'fee_percent'));
			var business_fee = this.formFieldValue(this.sections[s].fields['business_fee'], 'business_fee');
			var seller_amount = this.formFieldValue(this.sections[s].fields['seller_amount'], 'seller_amount');
			var sp = parseFloat(sell_price.replace(/\$/,''));
			if( fee_percent > 0 ) {
				bf = (sp * (fee_percent/100));
				bf = bf.toFixed(2);
			} 
			this.setFieldValue('business_fee', '$' + bf);
			this.setFieldValue('seller_amount', '$' + (sp - bf).toFixed(2));
		};
		this.itemedit.addButton('save', 'Save', 'M.ciniki_artgallery_exhibitionitems.itemSave();');
		this.itemedit.addClose('Cancel');

	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_artgallery_exhibitionitems', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

//		if( M.curBusiness.modules['ciniki.artgallery'] != null 
//			&& (M.curBusiness.modules['ciniki.artgallery'].flags&0x01) ) {
//			this.itemedit.sections.general.fields.flags.active = 'yes';
//		} else {
//			this.itemedit.sections.general.fields.flags.active = 'no';
//		}

//		if( args.item_id != null && args.item_id == '0' ) {
//			this.itemEdit(	
		this.sellerShow(cb, args.exhibition_id, args.customer_id);
	}

//	this.downloadInventory = function(eid, format) {
//		var args = {'business_id':M.curBusinessID, 'exhibition_id':eid, 'output':format};
//		M.api.openPDF('ciniki.artgallery.marketInventory', args);
//	};
//
	this.downloadPriceList = function(eid, format, sid) {
		var args = {'business_id':M.curBusinessID, 'exhibition_id':eid, 'customer_id':sid, 'output':format};
		M.api.openPDF('ciniki.artgallery.exhibitionPriceList', args);
	};

	this.downloadSellerSummary = function(eid, format, sid) {
		var args = {'business_id':M.curBusinessID, 'exhibition_id':eid, 'output':format};
		if( sid != null && sid > 0 ) {
			args.customer_id = sid;
		}
		M.api.openPDF('ciniki.artgallery.exhibitionSellerSummary', args);
	};

	//
	// Seller functions
	//
	this.sellerShow = function(cb, eid, sid) {
		this.seller.reset();
		if( eid != null ) { this.seller.exhibition_id = eid; }
		if( sid != null ) { this.seller.customer_id = sid; }
		var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionItemList', {'business_id':M.curBusinessID, 
			'exhibition_id':this.seller.exhibition_id, 'customer_id':this.seller.customer_id, 'items':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_artgallery_exhibitionitems.seller;
				p.data = rsp;
				p.refresh();
				p.show(cb);
			});
	};

	this.itemAdd = function(cid) {
		this.sellerEdit('M.ciniki_artgallery_exhibitionitems.sellerShow();', 0, M.ciniki_artgallery_exhibitionitems.seller.exhibition_id, cid);		
	};

	//
	// Item functions
	//
	this.itemEdit = function(cb, iid, sid) {
		this.itemedit.reset();
		if( iid != null ) { this.itemedit.item_id = iid; }
		if( sid != null ) { this.itemedit.customer_id = sid; }
		this.itemedit.sections._buttons.buttons.delete.visible = 'no';

		if( this.itemedit.item_id > 0 ) {
			this.itemedit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.artgallery.exhibitionItemGet', {'business_id':M.curBusinessID, 
				'item_id':this.itemedit.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_artgallery_exhibitionitems.itemedit;
					p.data = rsp.item;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.itemedit.data = {};
			this.itemedit.show(cb);
		}
	};

	this.itemSave = function() {
		if( this.itemedit.item_id > 0 ) {
			var c = this.itemedit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.artgallery.exhibitionItemUpdate', {'business_id':M.curBusinessID, 
					'item_id':M.ciniki_artgallery_exhibitionitems.itemedit.item_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_artgallery_exhibitionitems.itemedit.close();
					});
			} else {
				this.itemedit.close();
			}
		} else {
			var c = this.itemedit.serializeForm('yes');
			c += '&exhibition_id=' + this.seller.exhibition_id;
			c += '&customer_id=' + this.itemedit.customer_id;
			M.api.postJSONCb('ciniki.artgallery.exhibitionItemAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_artgallery_exhibitionitems.itemedit.close();
				});
		}
	};

	this.itemDelete = function() {
		if( confirm("Are you sure you want to remove this item?") ) {
			var rsp = M.api.getJSONCb('ciniki.artgallery.exhibitionItemDelete', 
				{'business_id':M.curBusinessID, 'item_id':M.ciniki_artgallery_exhibitionitems.itemedit.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_artgallery_exhibitionitems.itemedit.close();
				});
		}
	}
};
