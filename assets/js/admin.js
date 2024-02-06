/**
 * Filter the sites
 *
 * @package Admin JS
 */

jQuery( document ).ready(
	function () {
		// Listen for changes in the search filter input field.
		jQuery( '#post-smtp-select-sites-filter' ).on(
			'input',
			function () {
				var filterValue = jQuery( this ).val().toLowerCase();

				// Loop through each site div and check if the title matches the search filter.
				jQuery( '.post-smtp-mainwp-site' ).each(
					function () {
						var title = jQuery( this ).find( '.title' ).text().toLowerCase();

						// Hide the site div if the title does not match the search filter.
						if (title.indexOf( filterValue ) === -1) {
							jQuery( this ).hide();
						} else {
							jQuery( this ).show();
						}
					}
				);
			}
		);

		var currentURL = window.location.href;
		var site_id = PostSMTPGetParameterByName( 'site_id', currentURL );
		// Render Child Sites on Log Page.
		var _options = `<option value='-1'>${PSMainWP.allSites}</option>`;
		PSMainWP.childSites['main_site'] = PSMainWP.mainSite;

		jQuery.each(
			PSMainWP.childSites,
			function ( key, value ) {

				var _selected = '';

				if (currentURL !== null && key == site_id ) {

					_selected = 'selected';

				}

				_options += ` <option value='${key}' ${_selected}> ${value} </option>`;

			}
		)
		jQuery( '.ps-email-log-date-filter' ).after(
			`<div class ="ps-mainwp-site-filter">
				<label>Site
					<select class ="ps-mainwp-site-selector">
						${_options}
					</select>
				</label>
			</div>`
		);

		// Enable on child site.
		jQuery( document ).on(
			'click',
			'.enable-on-child-site',
			function ( e ) {
				
				var _checked = jQuery('.enable-on-child-site:checked').length - 1;
				var isChecked = jQuery( this ).is( ':checked' ) ? 1 : 0;
				var quotaMessage = `<div class="ui segment" style="padding-top:0px;padding-bottom:0px;margin-bottom:0px;background: transparent!important"><div id="mainwp-dashboard-info-box"></div><div id="mainwp-message-zone" class="ui message" style="display:none;"></div><div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="widgets"></i>To continue using Post SMTP Pro on all your child sites, you need to upgrade your license to a higher plan. <b>Your current license quota is limited to ${PSMainWP.sites} sites.</b> Exclusive Offer for MainWP users: <a href="https://postmansmtp.com/pricing/?utm_source=mainwp_dash&utm_medium=dash_notice&utm_campaign=mainwp_20discount" target="_blank">Avail discount on Lifetime Plans</a>🎉.
							</div>
						</div>`;

				if( PSMainWP.sites != false && isChecked && _checked >= PSMainWP.sites ) {
					
					jQuery( '#mainwp-top-header' ).append( quotaMessage );
					document.body.scrollTop = document.documentElement.scrollTop = 0;
					jQuery( this ).closest( '.title' ).find( '.ps-error' ).text( 'QUOTA EXCEEDED' );
					setTimeout(
					  function() {
						jQuery( this ).closest( '.title' ).find( '.ps-error' ).text( '' );
					  }, 10000);
					return false;
					
				}

				var clickedElement = jQuery( this );
				clickedElement.closest( '.title' ).find( 'span.spinner' ).addClass( 'is-active' );
				var siteID    = jQuery( this ).data( 'id' );

				jQuery.ajax(
					{
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'post-smtp-request-mwp-child',
							what: isChecked,
							site_id: siteID,
							security: jQuery( '.psmwp-security' ).val()
						},

						success: function () {

						},
						error: function ( res ) {

							if( res.status === 403 ) {
								
								jQuery( '#mainwp-top-header' ).append( quotaMessage );
								jQuery( clickedElement ).prop( 'checked', false );
								document.body.scrollTop = document.documentElement.scrollTop = 0;
								jQuery( clickedElement ).closest( '.title' ).find( '.ps-error' ).text( 'QUOTA EXCEEDED' );
								setTimeout(
								  function() {
									jQuery( clickedElement ).closest( '.title' ).find( '.ps-error' ).text( '' );
								  }, 10000);
								return false;
								
							}
							
							jQuery( clickedElement ).prop( 'checked', false );
							jQuery( clickedElement ).closest( '.title' ).find( '.ps-error' ).text( ` ${res.responseJSON.data.message}` );

						},
						complete: function () {

							clickedElement.closest( '.title' ).find( 'span.spinner' ).removeClass( 'is-active' );

						}
					}
				);

			}
		);

		jQuery( document ).on(
			'click',
			'.ps-enable-all',
			function ( e ) {

				e.preventDefault();
				postSMTPWMPEnableDisable( 1 );

			}
		)

		jQuery( document ).on(
			'click',
			'.ps-disable-all',
			function ( e ) {

				e.preventDefault();
				postSMTPWMPEnableDisable( 0 );

			}
		)

		// Code written in scripts/postman-email-logs.js.
	}
);

function postSMTPWMPEnableDisable( enable = 1 )
{

	var checkBoxs = jQuery( '.enable-on-child-site' );

	jQuery.each(
		checkBoxs,
		function ( key, value ) {

			isChecked = jQuery( value ).is( ':checked' );

			if (enable == 1 && ! isChecked ) {

				jQuery( value ).click();

			}
			if (enable == 0 && isChecked ) {

				jQuery( value ).click();

			}

		}
	)

}

function PostSMTPGetParameterByName( name, url )
{

	if ( ! url ) {
		url = window.location.href;
	}
	name      = name.replace( /[\[\]]/g, "\\$&" );
	var regex = new RegExp( "[?&]" + name + "(=([^&#]*)|&|#|$)" ),
	results   = regex.exec( url );
	if ( ! results ) {
		return null;
	}
	if ( ! results[2] ) {
		return '';
	}
	return decodeURIComponent( results[2].replace( /\+/g, " " ) );

}