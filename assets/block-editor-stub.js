/**
 * Gutenberg block script for the IntakeFlow Form embed.
 *
 * Shows a live server-rendered preview of the selected workflow in the editor
 * (via wp.serverSideRender) instead of a bare placeholder, plus an inspector
 * control to pick the workflow / hosted link.
 */
( function( blocks, element, blockEditor, components, serverSideRender ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var ServerSideRender = serverSideRender; // wp.serverSideRender (default export)

    blocks.registerBlockType( 'xpressui/workflow-embed', {
        title: 'IntakeFlow Form',
        icon: 'feedback',
        category: 'widgets',
        attributes: {
            workflowId: { type: 'string', default: '' },
            isHostedLink: { type: 'boolean', default: false }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var data = window.xpressuiEditorData || {};
            var workflows = data.workflows || [];
            var placeholder = data.placeholder || 'Select a workflow...';

            var options = [ { value: '', label: placeholder } ].concat( workflows );

            var inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: 'Form Configuration', initialOpen: true },
                    el( SelectControl, {
                        label: 'Select Workflow',
                        value: attributes.workflowId,
                        options: options,
                        onChange: function( val ) {
                            setAttributes( { workflowId: val } );
                        }
                    } ),
                    el( ToggleControl, {
                        label: 'Is Hosted Link?',
                        checked: attributes.isHostedLink,
                        onChange: function( val ) {
                            setAttributes( { isHostedLink: val } );
                        }
                    } )
                )
            );

            var body;
            if ( attributes.workflowId && ServerSideRender ) {
                // Live preview of the actual rendered form (server-rendered).
                body = el( ServerSideRender, {
                    block: 'xpressui/workflow-embed',
                    attributes: attributes
                } );
            } else {
                body = el(
                    'div',
                    { style: { border: '2px dashed #38bdf8', padding: '24px', background: '#f0f9ff', borderRadius: '10px', textAlign: 'center', fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif' } },
                    el( 'strong', { style: { color: '#0369a1', fontSize: '15px' } }, 'IntakeFlow Form' ),
                    el( 'div', { style: { fontSize: '13px', color: '#64748b', marginTop: '6px' } }, 'Select a workflow in the block settings to preview it here.' )
                );
            }

            return el( element.Fragment, null, inspector, body );
        },
        save: function() {
            return null; // Rendered dynamically in PHP via the render_callback.
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender
);
