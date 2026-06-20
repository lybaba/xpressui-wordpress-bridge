/**
 * Gutenberg block script stub for IntakeFlow Form.
 */
( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;

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
            var workflows = window.xpressuiEditorData ? window.xpressuiEditorData.workflows : [];
            var placeholder = window.xpressuiEditorData ? window.xpressuiEditorData.placeholder : 'Select...';

            var options = [ { value: '', label: placeholder } ].concat( workflows );

            return el(
                element.Fragment,
                null,
                el(
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
                ),
                el(
                    'div',
                    { style: { border: '1px solid #38bdf8', padding: '20px', background: '#f0f9ff', borderRadius: '10px', textAlign: 'center', fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif' } },
                    el( 'strong', { style: { color: '#0369a1', fontSize: '15px' } }, 'IntakeFlow Embed Form' ),
                    el( 'div', { style: { fontSize: '13px', color: '#64748b', marginTop: '4px' } }, 'Workflow: ' + ( attributes.workflowId || '(none)' ) )
                )
            );
        },
        save: function() {
            return null; // Rendered dynamically in PHP via callback
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components
);
