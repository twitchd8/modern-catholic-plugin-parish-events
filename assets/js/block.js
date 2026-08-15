( function ( blocks, element, i18n, components, blockEditor ) {
    'use strict';

    const createElement = element.createElement;
    const InspectorControls = blockEditor.InspectorControls;
    const PanelBody = components.PanelBody;
    const TextControl = components.TextControl;
    const RangeControl = components.RangeControl;
    const SelectControl = components.SelectControl;

    blocks.registerBlockType( 'modern-catholic/events', {
        apiVersion: 3,
        title: i18n.__( 'Modern Catholic – Parish Events', 'modern-catholic-parish-events' ),
        description: i18n.__( 'Display a filtered event list or calendar.', 'modern-catholic-parish-events' ),
        icon: 'calendar-alt',
        category: 'widgets',
        attributes: {
            limit: { type: 'number', default: 5 },
            start: { type: 'string', default: 'today' },
            end: { type: 'string', default: '+3 months' },
            view: { type: 'string', default: 'list' },
            category: { type: 'string', default: '' }
        },
        edit: function ( properties ) {
            const attributes = properties.attributes;
            const controls = createElement(
                InspectorControls,
                {},
                createElement(
                    PanelBody,
                    { title: i18n.__( 'Event display', 'modern-catholic-parish-events' ), initialOpen: true },
                    createElement( SelectControl, {
                        label: i18n.__( 'View', 'modern-catholic-parish-events' ),
                        value: attributes.view,
                        options: [ { label: i18n.__( 'List', 'modern-catholic-parish-events' ), value: 'list' }, { label: i18n.__( 'Calendar', 'modern-catholic-parish-events' ), value: 'calendar' } ],
                        onChange: function ( value ) { properties.setAttributes( { view: value } ); }
                    } ),
                    createElement( RangeControl, {
                        label: i18n.__( 'Maximum events', 'modern-catholic-parish-events' ),
                        value: attributes.limit,
                        min: 1,
                        max: 100,
                        onChange: function ( value ) { properties.setAttributes( { limit: value } ); }
                    } ),
                    createElement( TextControl, {
                        label: i18n.__( 'Start date or relative value', 'modern-catholic-parish-events' ),
                        help: i18n.__( 'Examples: today or 2026-09-01', 'modern-catholic-parish-events' ),
                        value: attributes.start,
                        onChange: function ( value ) { properties.setAttributes( { start: value } ); }
                    } ),
                    createElement( TextControl, {
                        label: i18n.__( 'End date or relative value', 'modern-catholic-parish-events' ),
                        help: i18n.__( 'Examples: +3 months or 2026-12-31', 'modern-catholic-parish-events' ),
                        value: attributes.end,
                        onChange: function ( value ) { properties.setAttributes( { end: value } ); }
                    } ),
                    createElement( TextControl, {
                        label: i18n.__( 'Event Category slug', 'modern-catholic-parish-events' ),
                        value: attributes.category,
                        onChange: function ( value ) { properties.setAttributes( { category: value } ); }
                    } )
                )
            );

            return createElement(
                'div',
                { className: 'modern-catholic-events-block-preview' },
                controls,
                createElement( 'strong', {}, i18n.__( 'Parish Events', 'modern-catholic-parish-events' ) ),
                createElement( 'p', {}, i18n.sprintf( i18n.__( '%1$s view · up to %2$d events', 'modern-catholic-parish-events' ), attributes.view, attributes.limit ) )
            );
        },
        save: function () { return null; }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.components, window.wp.blockEditor );
