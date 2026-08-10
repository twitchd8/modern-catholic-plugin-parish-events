(function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, TextControl } = wp.components;
    const { Fragment, createElement: el } = wp.element;

    registerBlockType( 'parishpress/events', {
        title: __( 'Modern Catholic – Parish Events', 'parishpress-events' ),
        icon: 'calendar',
        category: 'widgets',
        attributes: {
            limit: { type: 'number', default: 5 },
        },
        edit: function( props ) {
            const { attributes: { limit }, setAttributes } = props;
            const limitValue = Number.isFinite( limit ) ? limit : 5;

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Settings', 'parishpress-events' ) },
                        el( TextControl, {
                            label: __( 'Number to show', 'parishpress-events' ),
                            type: 'number',
                            min: 1,
                            value: limitValue,
                            onChange: ( value ) => setAttributes( { limit: Math.max( 1, parseInt( value, 10 ) || 5 ) } ),
                        } )
                    )
                ),
                el(
                    'div',
                    { className: 'parishpress-block-placeholder' },
                    el( 'strong', null, __( 'Modern Catholic – Parish Events', 'parishpress-events' ) ),
                    el( 'div', null, __( 'Displays upcoming events.', 'parishpress-events' ) ),
                    el( 'div', null, __( 'Limit', 'parishpress-events' ), ': ', limitValue )
                )
            );
        },
        save: function() {
            return null;
        },
    } );
})( window.wp );
