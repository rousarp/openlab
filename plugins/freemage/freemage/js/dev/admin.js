/*
 * Front end javascript / jquery code if any
 */

/* globals Freemage, freemageParams, tinyMCE */
/* global Freemage:true */

var $ = jQuery;
 var freemageView = wp.media.View.extend({
 	template: wp.template( 'freemage-search' ),

 	className: 'attachments-browser',

 	events: {
		'click .attachments': 'imageSelected',
		'keyup #media-search-input': 'searchChanged',
		'change #freemage-provider-filters': 'filterChanged',
		'change #freemage-license-filters': 'filterChanged',
		'click li:not(.downloaded) .freemage-download-label, li:not(.downloaded) .freemage-download': 'downloadClicked',
		'wheel .attachments': 'scrolled'
 	},

 	initialize: function() {
		this._prevSearch = null;
		this.$window = $( window );
		this.resizeEvent = 'resize.media-modal-columns';
		this._downloading = [];
		this._scrollBottomSearching = false;
		this._currentPage = 1;

		_.bindAll( this, 'setColumns' );

		this.on( 'ready', this.bindEvents );
		this.controller.on( 'open', this.setColumns );
		this.controller.on( 'open', this.focusSearch.bind(this) );

		// Call this.setColumns() after this view has been rendered in the DOM so
		// attachments get proper width applied.
		_.defer( this.setColumns, this );
 	},

	bindEvents: function() {
		this.$window.off( this.resizeEvent ).on( this.resizeEvent, _.debounce( this.setColumns, 50 ) );
	},

	focusSearch: function() {
		this.$el.find( '#media-search-input' ).focus();
	},

 	render: function() {
 	    this.$el.html( this.template() );

		this.bindEvents();

 	    return this;
 	},

	// Adjust the width of each image in the search list.
	setColumns: function() {
		var width = this.$el.width();

		if ( width ) {
			this.columns = Math.min( Math.round( width / 200 ), 12 ) || 1;
			this.$el.closest( '.media-frame-content' ).attr( 'data-columns', this.columns );
		}
	},

	getLi: function( elem ) {
		var li = elem;
		while ( li.tagName !== 'LI' && li.tagName !== 'HTML' ) {
			li = li.parentNode;
		}
		if ( li.tagName === 'HTML' ) {
			return null;
		}
		return li;
	},

	search: function( forceSearch ) {
		if ( ! this._appendSearchResultsBound ) {
			this._appendSearchResultsBound = this.appendSearchResult.bind( this );
		}
		if ( ! this._doneSearchingBound ) {
			this._doneSearchingBound = this.doneSearching.bind( this );
		}

		clearTimeout( this.searchTimeout );
		this.searchTimeout = setTimeout( function() {

			var keyword = this.$el.find('#media-search-input').val().trim();

			if ( ( this._prevSearch !== keyword && keyword ) || ( forceSearch && keyword ) ) {
				this._prevSearch = keyword;
				this.$el.find('.spinner').addClass('is-active');
				this.clearSearchResults();
				this._scrollBottomSearching = true;
				this._currentPage = 1;

				var selectedProviders = this.$el.find( '#freemage-provider-filters' ).val();
				var selectedLicenses = this.$el.find( '#freemage-license-filters' ).val();

				Freemage.search( keyword, this._appendSearchResultsBound, this._doneSearchingBound, this._currentPage, selectedProviders, selectedLicenses );
			}

		}.bind(this), 700 );

	},

	scrolled: function() {
		if ( this._scrollBottomSearching ) {
			return;
		}
		if ( ! this.$el.find('.attachments li').length ) {
			return;
		}
		var container = this.$el.find('.attachments');
	    if ( container[0].scrollHeight - container.scrollTop() !== container.outerHeight() ) {
			return;
	    }

		this._currentPage++;
		this._scrollBottomSearching = true;
		var keyword = this.$el.find('#media-search-input').val().trim();

		this.$el.find('.spinner').addClass('is-active');

		var selectedProviders = this.$el.find( '#freemage-provider-filters' ).val();
		var selectedLicenses = this.$el.find( '#freemage-license-filters' ).val();

		Freemage.search( keyword, this._appendSearchResultsBound, this._doneSearchingBound, this._currentPage, selectedProviders, selectedLicenses );
	},

	searchChanged: function() {
		this.search();
	},

	filterChanged: function() {
		this.search( true );
	},

	appendSearchResult: function( result ) {
		if ( ! result.sizes.length ) {
			return;
		}

		result.orientation = 'landscape';
		if ( result.sizes[0].height > result.sizes[0].width ) {
			result.orientation = 'portrait';
		}

		result.preview = '';
		var width = this.$el.width();
		if ( ! width ) {
			width = 300;
		} else {
			width = width / this.columns;
		}
		var currThumbSize = 9999;
		var currSmallest = 9999;
		var smallestURL = '';
		for ( var i = 0; i < result.sizes.length; i++ ) {
			if ( result.orientation === 'landscape' && result.sizes[ i ].height >= width - 150 && result.sizes[ i ].height < currThumbSize ) {
				result.preview = result.sizes[ i ].url;
				currThumbSize = result.sizes[ i ].height;
			} else if ( result.orientation === 'portrait' && result.sizes[ i ].width >= width - 150 && result.sizes[ i ].width < currThumbSize ) {
				result.preview = result.sizes[ i ].url;
				currThumbSize = result.sizes[ i ].width;
			}
			if ( result.orientation === 'landscape' && result.sizes[ i ].height < currSmallest ) {
				smallestURL = result.sizes[ i ].url;
				currSmallest = result.sizes[ i ].height;
			} else if ( result.orientation === 'portrait' && result.sizes[ i ].width < currSmallest ) {
				smallestURL = result.sizes[ i ].url;
				currSmallest = result.sizes[ i ].width;
			}
		}
		if ( ! result.preview ) {
			result.preview = smallestURL;
		}

		var li = $('<li tabindex="0" role="checkbox" aria-checked="false" class="attachment save-ready">');
		li.attr( 'aria-label', result.title );
		li.html( wp.template( 'freemage-search-result' )( result ) );
		li.data( 'freemage', result );

		this.$el.find('.attachments').append( li );
	},

	doneSearching: function() {
		this.$el.find( '.spinner' ).removeClass( 'is-active' );
		this._scrollBottomSearching = false;

		if ( this.$el.find( 'ul.attachments li' ).length === 0 ) {
			this.$el.find( '.freemage-no-results' ).removeClass( 'freemage-hidden' );
		}
	},

	clearSearchResults: function() {
		this.$el.find( 'ul.attachments' ).html( '' );
		this.$el.find( '.freemage-no-results' ).addClass( 'freemage-hidden' );
	},

 	imageSelected: function(ev) {
		this.$el.find('.details').removeClass('details');
		var li = this.getLi( ev.target );
		if ( ! li ) {
			ev.target.blur();
			return;
		}
		li.classList.toggle( 'details' );
		li.blur();

		this.showSelectedDetails();
	},

	showSelectedDetails: function() {
		var details = this.$el.find('.freemage-details');

		// Allow providers to cleanup stuff they added in the details window.
		if ( typeof Freemage.providers !== 'undefined' ) {
			for ( var provider in Freemage.providers ) {
				if ( Freemage.providers.hasOwnProperty( provider ) ) {
					if ( typeof Freemage.providers[ provider ].onSelectCleanup !== 'undefined' ) {
						Freemage.providers[ provider ].onSelectCleanup( details );
					}
				}
			}
		}

		var li = this.getLi( this.$el.find('li.details')[0] );
		if ( ! li ) {
			details.css('display', 'none');
			return;
		}
		details.css('display', '');

		var label, data = $(li).data('freemage');
		details.find('.preview').attr('src', data.preview);
		details.find('.provider span').html( $('<a></a>').attr('href', data.provider_link).text( data.provider_name ).attr('target', '_freemage') );
		details.find('.title span').text( data.title ? data.title : '–' );
		details.find('.date span').text( data.date ? data.date : '–' );
		if ( data.url ) {
			label = data.url;
			if ( label.length > 30 ) {
				label = label.substring( 0, 30 ) + '...';
			}
			details.find('.source span').html( $('<a></a>').attr('href', data.url).text( label ).attr('target', '_freemage') );
		} else {
			details.find('.source span').html( '–' );
		}
		if ( data.user ) {
			details.find('.owner span').html( $('<a></a>').attr('href', data.user_link).text( data.user ).attr('target', '_freemage') );
		} else {
			details.find('.owner span').html( '–' );
		}
		if ( data.license && data.license_link ) {
			details.find('.license span').html( $('<a></a>').attr('href', data.license_link).text( data.license ).attr('target', '_freemage') );
		} else if ( data.license ) {
			details.find('.license span').html( data.license );
		} else {
			details.find('.license span').html( '–' );
		}
		details.find('.sizes span').html('');
		for ( var i = 0; i < data.sizes.length; i++ ) {
			var sizeLabel = data.sizes[i].width + 'x' + data.sizes[i].height;
			details.find('.sizes span').append('<br>');
			details.find('.sizes span').append( $('<a></a>').attr('href', data.sizes[i].url).text( sizeLabel ).attr('target', '_freemage') );
		}

		// Allow providers to change stuff in the details window.
		if ( typeof Freemage.providers[ data.provider ] !== 'undefined' ) {
			if ( typeof Freemage.providers[ data.provider ].onSelect !== 'undefined' ) {
				Freemage.providers[ data.provider ].onSelect( details, data );
			}
		}
	},

	downloadClicked: function(ev) {
		var li = this.getLi( ev.target );
		if ( ! li ) {
			return;
		}

		this.downloadImage( li, $(li).data('freemage') );
	},

	downloadImage: function( li, freemageData ) {
		var xhr = new XMLHttpRequest();
		xhr.onload = function() {
			if ( xhr.status >= 200 && xhr.status < 400 ) {
				// provider.onload( xhr.response, resultCallback );

				$(li).removeClass('downloading').addClass('downloaded');
				this.updateMediaManager( JSON.parse( xhr.response ), freemageData );
			}
		}.bind(this);

		xhr.onerror = function() {
			$(li).removeClass('downloading');
		}.bind(this);

		var payload = new FormData();
		payload.append( 'action', 'freemage_download_image' );
		payload.append( 'nonce', freemageParams.nonce );
		payload.append( 'data', JSON.stringify( freemageData ) );

		$(li).addClass('downloading');
	    xhr.open( 'POST', freemageParams.ajax_url );
	    xhr.send( payload );
	},

	basename: function( path ) {
		return path.split(/[\\/]/).pop();
	},

	/**
	 * Create an Attachment object containing the filtered image's data, and manually insert it into
	 * the library
	 */
	updateMediaManager: function( jsonData, freemageData ) {

		// If we are in a media modal window, or the Media > Library screen, some variables
		// are defined differently
		var library;
		if ( typeof wp.media.frame.state().get('library') !== 'undefined' ) {
			library = wp.media.frame.state().get('library');
		} else {
			library = wp.media.frame.library;
		}

        var newAttachment = new wp.media.model.Attachment();

        var sizes = {
            full: {
                height: jsonData.sizes_data.height,
                orientation: freemageData.orientation,
                url: jsonData.attachment_url,
                width: jsonData.sizes_data.width
            }
        };

        if ( typeof jsonData.sizes_data.sizes.large !== 'undefined' ) {
            sizes.large = {
                height: jsonData.sizes_data.sizes.large.height,
                orientation: freemageData.orientation,
                url: jsonData.attachment_url.replace( this.basename( jsonData.attachment_url ), '' ) + jsonData.sizes_data.sizes.large.file,
                width: jsonData.sizes_data.sizes.large.width
            };
        }

        if ( typeof jsonData.sizes_data.sizes.medium !== 'undefined' ) {
            sizes.medium = {
                height: jsonData.sizes_data.sizes.medium.height,
                orientation: freemageData.orientation,
                url: jsonData.attachment_url.replace( this.basename( jsonData.attachment_url ), '' ) + jsonData.sizes_data.sizes.medium.file,
                width: jsonData.sizes_data.sizes.medium.width
            };
        }

        if ( typeof jsonData.sizes_data.sizes.thumbnail !== 'undefined' ) {
            sizes.thumbnail = {
                height: jsonData.sizes_data.sizes.thumbnail.height,
                orientation: freemageData.orientation,
                url: jsonData.attachment_url.replace( this.basename( jsonData.attachment_url ), '' ) + jsonData.sizes_data.sizes.thumbnail.file,
                width: jsonData.sizes_data.sizes.thumbnail.width
            };
        }

        var nonces = {
            delete: jsonData.delete_nonce, // jshint ignore:line
            edit: jsonData.edit_nonce,
            update: jsonData.update_nonce
        };

        newAttachment.id = jsonData.id;
        newAttachment.attributes.alt = '';
        newAttachment.attributes.author = '0';
        newAttachment.attributes.caption = jsonData.attachment_data.post_excerpt;
        newAttachment.attributes.compat = jsonData.compat_fields;
        newAttachment.attributes.date = new Date();
        newAttachment.attributes.dateFormatted = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'][(new Date()).getMonth()] + ' ' + (new Date()).getDate() + ', ' + (new Date()).getFullYear();
        newAttachment.attributes.description = '';
        newAttachment.attributes.editLink = freemageParams.admin_post_url + '?post=' + jsonData.id + '&action=edit';
        newAttachment.attributes.filename = this.basename( jsonData.attachment_url );
        newAttachment.attributes.height = jsonData.sizes_data.height;
        newAttachment.attributes.icon = freemageParams.media_default_url;
        newAttachment.attributes.id = jsonData.id;
        newAttachment.attributes.link = jsonData.attachment_link;
        newAttachment.attributes.menuOrder = 0;
        newAttachment.attributes.mime = jsonData.attachment_data.post_mime_type;
        newAttachment.attributes.modified = new Date();
        newAttachment.attributes.name = jsonData.attachment_data.post_name;
        newAttachment.attributes.nonces = nonces;
        newAttachment.attributes.orientation = freemageData.orientation;
        newAttachment.attributes.sizes = sizes;
        newAttachment.attributes.status = 'inherit';
        newAttachment.attributes.subtype = this.basename( jsonData.attachment_data.post_mime_type );
        newAttachment.attributes.title = jsonData.attachment_data.post_title;
        newAttachment.attributes.type = 'image';
        newAttachment.attributes.uploadedTo = 0;
        newAttachment.attributes.url = jsonData.attachment_url;
        newAttachment.attributes.width = jsonData.sizes_data.width;

        // Add in our current list
        library.models.unshift( newAttachment );

        // Update the list
        library.reset( library.models );

        // Select the new attachment

		// If we are in a media modal window, update and select the new image
		// if ( typeof wp.media.frame.state().get('selection') !== 'undefined' ) {
        //     wp.media.frame.state().get('selection').reset();
        //     wp.media.frame.state().get('selection').add( newAttachment );
		// }
	}
});

(function() {
	var proxied = wp.media.view.MediaFrame.Select.prototype.browseRouter;
	wp.media.view.MediaFrame.Select.prototype.browseRouter = function( routerView ) {
		proxied.call( this, routerView );
		routerView.set({
			freemage: {
				text: 'Freemage Search',
				priority: 100
			}
		});
	};
})();


(function() {
	var proxied = wp.media.view.MediaFrame.Select.prototype.bindHandlers;
	wp.media.view.MediaFrame.Select.prototype.bindHandlers = function() {
		proxied.call( this );
		this.on( 'content:create:freemage', this.freemageSearchContent, this );
	};

	wp.media.view.MediaFrame.Select.prototype.freemageSearchContent = function( contentRegion ) {
		this.$el.removeClass('hide-toolbar');

		// if ( ! this._freemageView ) {
		// 	this._freemageView = new freemageView({
		// 		controller: this
		// 	});
		// }
		// contentRegion.view = this._freemageView;

		contentRegion.view = new freemageView({
			controller: this
		});
	};
})();


Freemage = {

	// To be filled up by other scripts.
	providers: {},

	_searchesDone: 0,
	_numProviders: 0,
	search: function( keyword, resultCallback, doneCallback, page, onlyThisProvider, selectedLicenses ) {
		if ( keyword.trim() === '' ) {
			return;
		}

		this._searchesDone = 0;
		this._numProviders = 0;

		if ( page > 5 ) {
			return;
		}

		var foundProvider = false;
		for ( var i in this.providers ) {

			if ( ! this.providers.hasOwnProperty( i ) ) {
				continue;
			}

			// If a provider is given, only search using this specific one.
			if ( onlyThisProvider && i !== onlyThisProvider ) {
				continue;
			}

			// If no provider is given, search them all but use only the active ones.
			if ( ! onlyThisProvider ) {
				if ( freemageParams.active_providers.indexOf( i ) === -1 ) {
					continue;
				}
			}

			// Lite version can only search flickr.
			if ( freemageParams.is_lite && freemageParams.lite_providers.indexOf( i ) === -1 ) {
				continue;
			}

			foundProvider = true;
			this._numProviders++;

			( function ( provider, resultCallback, page, doneCallback, selectedLicenses ) { // jshint ignore: line

				// Number of results per page. More providers = less per page so as not
				// to clog the UI.
				var per_page = 50;
				if ( ! onlyThisProvider && freemageParams.active_providers.length > 2 ) {
					per_page = 20;
				}

				var url = provider.formURL( {
					keyword: keyword,
					license: selectedLicenses,
					per_page: per_page,
					page: page
				} );

				// Don't search if no URL. Search may produce 0 results.
				if ( ! url ) {
					doneCallback();
					return;
				}

				var xhr = new XMLHttpRequest();
				xhr.onload = function() {
					if ( xhr.status >= 200 && xhr.status < 400 ) {
						provider.onload( xhr.response, resultCallback );
					}
					this._searchesDone++;
					if ( this._searchesDone === this._numProviders ) {
						doneCallback();
					}
				}.bind(this);

				xhr.onerror = function() {
					this._searchesDone++;
					if ( this._searchesDone === this._numProviders ) {
						doneCallback();
					}
				}.bind(this);

			    xhr.open( 'GET', url );

				if ( typeof provider.beforeSend !== 'undefined' )  {
					provider.beforeSend( xhr );
				}

			    xhr.send();

			}.bind(this) )( this.providers[ i ], resultCallback, page, doneCallback, selectedLicenses );
		}

		if ( ! foundProvider ) {
			doneCallback();
		}
	}
};



/**
 * If the feature image was changed into a freemage, add the attribution in the content.
 */
jQuery(document).ready(function($){
	if ( wp.media.featuredImage && wp.media.featuredImage.select ) {
		(function() {
			var proxied = wp.media.featuredImage.select;
			wp.media.featuredImage.select = function() {
				proxied.call( this );
				var selection = this.get('selection').single();

				if ( selection.attributes.caption && selection.attributes.filename.match( /^freemage-/ ) ) {

					if ( $( '#content' ).is( ':visible' ) ) {
						$('#content').val( $('#content').val() + '\n\n' + selection.attributes.caption );
					} else if ( tinyMCE && tinyMCE.activeEditor ) {
						var content = tinyMCE.activeEditor.getContent();
						tinyMCE.execCommand( 'mceSetContent', false, content + '<p>' + selection.attributes.caption + '</p>' );
					}
				}
			};
		})();
	}
});

/* globals Freemage, freemageParams */

Freemage.providers.flickr = {
	api: 'https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=28b31ed922d1780134dbfb2928d8ef55&text={keyword}&sort=interestingness-desc&content_type=&license={license}&extras=date_taken%2C+license%2C+owner_name%2C+url_sq%2C+url_t%2C+url_s%2C+url_m%2C+url_l%2C+url_o%2C+url_q%2C+description&per_page={per_page}&page={page}&format=json',
	formURL: function( args ) {

		var url = this.api;
		url = url.replace( /\{keyword\}/, args.keyword );
		url = url.replace( /\{per_page\}/, args.per_page );
		url = url.replace( /\{page\}/, args.page );

		// Flickr licenses: https://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html
		if ( args.license === 'noncommercial' ) {
			url = url.replace( /\{license\}/, '1,2,3,4,5,6,7' );
		} else if ( args.license === 'noattribution' ) {
			url = url.replace( /\{license\}/, '7' );
		} else {
			url = url.replace( /\{license\}/, '4,5,6,7' );
		}

		return url;
	},
	onload: function( response, resultCallback ) {
		response = response.replace( /^jsonFlickrApi\(/, '' );
		response = response.replace( /\)$/, '' );
		var data = JSON.parse( response );

		for ( var i = 0; i < data.photos.photo.length; i++ ) {
			var hit = data.photos.photo[ i ];
			var image = {
				provider: 'flickr',
				provider_name: freemageParams.providers.flickr,
				provider_link: 'http://flickr.com',
				user: hit.ownername,
				user_link: 'https://www.flickr.com/people/' + hit.owner,
				date: hit.datetaken,
				title: hit.title,
				url: 'https://www.flickr.com/photos/' + hit.owner + '/' + hit.id,
				badges: [],
				sizes: []
			};

			// Flickr licenses: https://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html
			if ( hit.license === '1' ) {
				image.license = 'Creative Commons Attribution-NonCommercial-ShareAlike';
				image.license_link = 'http://creativecommons.org/licenses/by-nc-sa/2.0/';
				image.license_shortname = 'CC BY-NC-SA';
				image.badges.push('attribution');
				image.badges.push('noncommercial');
			} else if ( hit.license === '2' ) {
				image.license = 'Creative Commons Attribution-NonCommercial';
				image.license_link = 'http://creativecommons.org/licenses/by-nc/2.0/';
				image.license_shortname = 'CC BY-NC';
				image.badges.push('attribution');
				image.badges.push('noncommercial');
			} else if ( hit.license === '3' ) {
				image.license = 'Creative Commons Attribution-NonCommercial-NoDerivs';
				image.license_link = 'http://creativecommons.org/licenses/by-nc-nd/2.0/';
				image.license_shortname = 'CC BY-NC-ND';
				image.badges.push('attribution');
				image.badges.push('noncommercial');
			} else if ( hit.license === '4' ) {
				image.license = 'Creative Commons Attribution';
				image.license_link = 'http://creativecommons.org/licenses/by/2.0/';
				image.license_shortname = 'CC BY';
				image.badges.push('attribution');
			} else if ( hit.license === '5' ) {
				image.license = 'Creative Commons Attribution-ShareAlike';
				image.license_link = 'http://creativecommons.org/licenses/by-sa/2.0/';
				image.license_shortname = 'CC BY-SA';
				image.badges.push('attribution');
			} else if ( hit.license === '6' ) {
				image.license = 'Creative Commons Attribution-NoDerivs';
				image.license_link = 'http://creativecommons.org/licenses/by-nd/2.0/';
				image.license_shortname = 'CC BY-ND';
				image.badges.push('attribution');
			} else if ( hit.license === '7' ) {
				image.license = 'No known copyright restrictions';
				image.license_link = 'http://flickr.com/commons/usage/';
				image.license_shortname = 'CC0';
				image.badges.push('zero');
			} else if ( hit.license === '8' ) {
				image.license = 'United States Government Work';
				image.license_link = 'http://www.usa.gov/copyright.shtml';
				image.license_shortname = 'U.S. Gov\'t Work';
				image.badges.push('warning');
			}

			if ( hit.url_s ) {
				image.sizes.push({
					url: hit.url_s,
					width: parseInt( hit.width_s, 10 ),
					height: parseInt( hit.height_s, 10 )
				});
			}
			if ( hit.url_m ) {
				image.sizes.push({
					url: hit.url_m,
					width: parseInt( hit.width_m, 10 ),
					height: parseInt( hit.height_m, 10 )
				});
			}
			if ( hit.url_l ) {
				image.sizes.push({
					url: hit.url_l,
					width: parseInt( hit.width_l, 10 ),
					height: parseInt( hit.height_l, 10 )
				});
			}
			if ( hit.url_o ) {
				image.sizes.push({
					url: hit.url_o,
					width: parseInt( hit.width_o, 10 ),
					height: parseInt( hit.height_o, 10 )
				});
			}

			resultCallback( image );
		}
	}
};




/**


 * Attribution: "Powered by Giphy"


 *


 * GET Endpoint: http://api.giphy.com/v1/gifs/search?q=funny+cat&limit=50&offset=0&api_key=d3ML1IkhgUlWvlja


 * Docs: https://github.com/Giphy/GiphyAPI#access-and-api-keys


 */








/* globals Freemage, freemageParams */





Freemage.providers.giphy = {


	api: 'http://api.giphy.com/v1/gifs/search?q={keyword}&limit={per_page}&offset={offset}&api_key=d3ML1IkhgUlWvlja',


	 formURL: function( args ) {


		if ( args.license === 'noattribution' ) {


			return '';


		}





		var url = this.api;


		url = url.replace( /\{keyword\}/, args.keyword );


		url = url.replace( /\{per_page\}/, args.per_page );


		url = url.replace( /\{offset\}/, args.page * args.per_page );





		return url;


	},


	onSelect: function( view ) {


		if ( view.find( '.freemage-powered-by-giphy' ).length === 0 ) {


			var poweredBy = jQuery('<IMG>');


			poweredBy.attr( 'src', freemageParams.plugin_url + 'freemage/images/powered-by-giphy.png' )


			.addClass( 'freemage-powered-by-giphy' );


			view.find( '.thumbnail' ).after( poweredBy );


		}


	},


	onSelectCleanup: function( view ) {


		if ( view.find( '.freemage-powered-by-giphy' ).length ) {


			view.find( '.freemage-powered-by-giphy' ).remove();


		}


	},


	 onload: function( response, resultCallback ) {


		var data = JSON.parse( response );





		for ( var i = 0; i < data.data.length; i++ ) {


			var hit = data.data[ i ];


			var image = {


				provider: 'giphy',


				provider_name: freemageParams.providers.giphy,


				provider_link: 'http://giphy.com',


				date: hit.import_datetime,


				user: hit.username,


				user_link: hit.source || hit.url,


				url: hit.url,


				title: hit.slug.replace( /\-\w+$/, '' ).replace( /\-/g, ' ' ),


				attribution: 'Powered by <a href="http://giphy.com">Giphy</a>',


				license: 'Giphy',


				license_link: 'http://giphy.com/terms',


				license_shortname: 'Giphy',


				badges: ['attribution'],


				sizes: []


			};





			if ( hit.user ) {


				image.user = hit.user.display_name;


				image.user_link = hit.user.profile_url;


			}





			if ( hit.images.fixed_height_downsampled ) {


				image.sizes.push({


					url: hit.images.fixed_height_downsampled.url,


					width: parseInt( hit.images.fixed_height_downsampled.width, 10 ),


					height: parseInt( hit.images.fixed_height_downsampled.height, 10 )


				});


			}





			if ( hit.images.original ) {


				image.sizes.push({


					url: hit.images.original.url,


					width: parseInt( hit.images.original.width, 10 ),


					height: parseInt( hit.images.original.height, 10 )


				});


			}





			resultCallback( image );


		}


	 }


  };



