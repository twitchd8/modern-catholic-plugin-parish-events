( function () {
    'use strict';

    const byId = function ( id ) { return document.getElementById( id ); };
    const setValue = function ( key, value ) {
        const field = byId( 'modern-catholic-event-' + key.replaceAll( '_', '-' ) );
        if ( field && value !== undefined && value !== null ) {
            field.value = value;
        }
    };

    const componentValue = function ( components, type, shortValue ) {
        const component = components.find( function ( item ) { return item.types && item.types.includes( type ); } );
        if ( ! component ) { return ''; }
        return shortValue ? ( component.shortText || '' ) : ( component.longText || '' );
    };

    function initializeFormattedAddress() {
        const output = byId( 'modern-catholic-event-formatted-address' );
        const fields = {
            street: byId( 'modern-catholic-event-street-address' ),
            locality: byId( 'modern-catholic-event-address-locality' ),
            region: byId( 'modern-catholic-event-address-region' ),
            postal: byId( 'modern-catholic-event-postal-code' ),
            country: byId( 'modern-catholic-event-address-country' )
        };
        if ( ! output ) { return; }

        const update = function ( invalidatePlace ) {
            const regionPostal = [ fields.region?.value.trim(), fields.postal?.value.trim() ].filter( Boolean ).join( ' ' );
            const localityLine = [ fields.locality?.value.trim(), regionPostal ].filter( Boolean ).join( ', ' );
            output.value = [ fields.street?.value.trim(), localityLine, fields.country?.value.trim() ].filter( Boolean ).join( ', ' );
            if ( invalidatePlace ) {
                setValue( 'google_place_id', '' );
                setValue( 'latitude', '' );
                setValue( 'longitude', '' );
            }
        };

        Object.values( fields ).forEach( function ( field ) {
            if ( field ) {
                field.addEventListener( 'input', function () { update( true ); } );
                field.addEventListener( 'change', function () { update( true ); } );
            }
        } );
        update( false );
    }

    function initializeRecurrenceDisclosure() {
        const frequency = byId( 'modern-catholic-event-recurrence-frequency' );
        const monthlyMode = byId( 'modern-catholic-event-monthly-mode' );
        const ending = byId( 'modern-catholic-event-recurrence-end-type' );
        if ( ! frequency ) { return; }

        const recurrenceSections = document.querySelectorAll( '[data-modern-catholic-events-recurrence-section]' );
        const monthlySections = document.querySelectorAll( '[data-modern-catholic-events-monthly-section]' );
        const endingSections = document.querySelectorAll( '[data-modern-catholic-events-ending-section]' );

        const update = function () {
            const repeats = frequency.value !== 'none';
            recurrenceSections.forEach( function ( section ) {
                const condition = section.dataset.modernCatholicEventsRecurrenceSection;
                section.hidden = condition === 'repeating' ? ! repeats : frequency.value !== condition;
            } );
            monthlySections.forEach( function ( section ) {
                section.hidden = ! repeats || frequency.value !== 'monthly' || ! monthlyMode || monthlyMode.value !== section.dataset.modernCatholicEventsMonthlySection;
            } );
            endingSections.forEach( function ( section ) {
                section.hidden = ! repeats || ! ending || ending.value !== section.dataset.modernCatholicEventsEndingSection;
            } );
        };

        frequency.addEventListener( 'change', update );
        monthlyMode?.addEventListener( 'change', update );
        ending?.addEventListener( 'change', update );
        update();
    }

    async function initializePlaces() {
        const host = byId( 'modern-catholic-events-places' );
        if ( ! host || ! window.google || ! window.google.maps || ! window.google.maps.importLibrary ) { return; }

        try {
            const places = await window.google.maps.importLibrary( 'places' );
            const autocomplete = new places.PlaceAutocompleteElement();
            autocomplete.setAttribute( 'aria-label', host.dataset.placeholder || 'Search for a venue or address' );
            autocomplete.setAttribute( 'placeholder', host.dataset.placeholder || 'Search for a venue or address' );
            host.appendChild( autocomplete );

            autocomplete.addEventListener( 'gmp-select', async function ( event ) {
                const place = event.placePrediction.toPlace();
                await place.fetchFields( { fields: [ 'id', 'displayName', 'formattedAddress', 'addressComponents', 'location' ] } );
                const components = place.addressComponents || [];
                const streetNumber = componentValue( components, 'street_number', false );
                const route = componentValue( components, 'route', false );
                setValue( 'venue_name', place.displayName );
                setValue( 'street_address', [ streetNumber, route ].filter( Boolean ).join( ' ' ) );
                setValue( 'address_locality', componentValue( components, 'locality', false ) || componentValue( components, 'postal_town', false ) );
                setValue( 'address_region', componentValue( components, 'administrative_area_level_1', true ) );
                setValue( 'postal_code', componentValue( components, 'postal_code', false ) );
                setValue( 'address_country', componentValue( components, 'country', false ) );
                setValue( 'google_place_id', place.id );
                if ( place.location ) {
                    setValue( 'latitude', place.location.lat() );
                    setValue( 'longitude', place.location.lng() );
                }
                setValue( 'formatted_address', place.formattedAddress );
            } );
        } catch ( error ) {
            host.hidden = true;
        }
    }

    function initializeAllDay() {
        const toggle = byId( 'modern-catholic-event-all-day' );
        const times = [ byId( 'modern-catholic-event-start-time' ), byId( 'modern-catholic-event-end-time' ) ];
        if ( ! toggle ) { return; }
        const update = function () {
            times.forEach( function ( field ) {
                if ( field ) { field.closest( '.modern-catholic-events-field' ).hidden = toggle.checked; }
            } );
        };
        toggle.addEventListener( 'change', update );
        update();
    }

    document.addEventListener( 'DOMContentLoaded', function () {
        initializeAllDay();
        initializeFormattedAddress();
        initializeRecurrenceDisclosure();
        let attempts = 0;
        const timer = window.setInterval( function () {
            attempts += 1;
            if ( window.google && window.google.maps ) {
                window.clearInterval( timer );
                initializePlaces();
            } else if ( attempts > 40 ) {
                window.clearInterval( timer );
            }
        }, 250 );
    } );
} )();
