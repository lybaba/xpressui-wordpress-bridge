/**
 * Gutenberg block script for the IntakeFlow Form embed.
 *
 * A single dropdown lists installed workflows AND hosted links. Selecting one shows a
 * clean preview card with the exact shortcode that will render on the published page.
 * (No live form render in the editor — the runtime is front-end only, so a server
 * render here would show an unstyled, non-interactive form.)
 */
( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;

    blocks.registerBlockType( 'xpressui/workflow-embed', {
        title: 'IntakeFlow Form',
        icon: 'feedback',
        category: 'widgets',
        attributes: {
            workflowId: { type: 'string', default: '' },
            isHostedLink: { type: 'boolean', default: false },
            projectSlug: { type: 'string', default: '' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var data = window.xpressuiEditorData || {};
            var workflows = data.workflows || [];
            var links = data.links || [];

            // Encode the type in the option value so one dropdown covers both:
            //   wf:<slug>        → [xpressui id="<slug>"]
            //   link:<slug>:<id> → [xpressui id="<slug>" link="<id>"]
            var options = [ { value: '', label: data.placeholder || 'Select…' } ];
            workflows.forEach( function( w ) { options.push( { value: 'wf:' + w.value, label: w.label } ); } );
            links.forEach( function( l ) { options.push( { value: 'link:' + l.value, label: '🔗 ' + l.label } ); } );

            var current = '';
            if ( attributes.workflowId ) {
                if ( attributes.isHostedLink ) {
                    if ( attributes.projectSlug ) {
                        current = 'link:' + attributes.projectSlug + ':' + attributes.workflowId;
                    } else {
                        // Legacy block: search options for a matching link ID suffix
                        options.forEach( function( o ) {
                            if ( o.value.indexOf( 'link:' ) === 0 && o.value.endsWith( ':' + attributes.workflowId ) ) {
                                current = o.value;
                            }
                        } );
                        // If still not found, fallback
                        if ( ! current ) {
                            current = 'link::' + attributes.workflowId;
                        }
                    }
                } else {
                    current = 'wf:' + attributes.workflowId;
                }
            }

            var currentLabel = '';
            options.forEach( function( o ) { if ( o.value === current ) { currentLabel = o.label; } } );
            var shortcode = attributes.workflowId
                ? ( attributes.isHostedLink
                    ? '[xpressui id="' + (attributes.projectSlug || '') + '" link="' + attributes.workflowId + '"]'
                    : '[xpressui id="' + attributes.workflowId + '"]' )
                : '';

            var inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: 'Form Configuration', initialOpen: true },
                    el( SelectControl, {
                        label: 'Select form or hosted link',
                        value: current,
                        options: options,
                        onChange: function( val ) {
                            if ( ! val ) {
                                setAttributes( { workflowId: '', isHostedLink: false, projectSlug: '' } );
                                return;
                            }
                            var isLink = val.indexOf( 'link:' ) === 0;
                            if ( isLink ) {
                                var parts = val.replace( /^link:/, '' ).split( ':' );
                                setAttributes( {
                                    projectSlug: parts[0] || '',
                                    workflowId: parts[1] || '',
                                    isHostedLink: true
                                } );
                            } else {
                                setAttributes( {
                                    projectSlug: '',
                                    workflowId: val.replace( /^wf:/, '' ),
                                    isHostedLink: false
                                } );
                            }
                        }
                    } )
                )
            );

            var body;
            if ( attributes.workflowId ) {
                body = el(
                    'div',
                    { style: { border: '1px solid #38bdf8', padding: '18px 20px', background: '#f0f9ff', borderRadius: '10px', fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif' } },
                    el( 'strong', { style: { color: '#0369a1', fontSize: '15px' } }, 'IntakeFlow Form' ),
                    el( 'div', { style: { fontSize: '13px', color: '#0f172a', marginTop: '4px', fontWeight: 600 } }, currentLabel || attributes.workflowId ),
                    el( 'code', { style: { display: 'inline-block', marginTop: '10px', padding: '4px 8px', background: '#ffffff', border: '1px solid #cbd5e1', borderRadius: '6px', fontSize: '12px', color: '#334155' } }, shortcode ),
                    el( 'div', { style: { fontSize: '12px', color: '#64748b', marginTop: '8px' } }, data.renderNote || 'This form renders on the published page.' )
                );
            } else {
                body = el(
                    'div',
                    { style: { border: '2px dashed #38bdf8', padding: '24px', background: '#f0f9ff', borderRadius: '10px', textAlign: 'center', fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif' } },
                    el( 'strong', { style: { color: '#0369a1', fontSize: '15px' } }, 'IntakeFlow Form' ),
                    el( 'div', { style: { fontSize: '13px', color: '#64748b', marginTop: '6px' } }, 'Select a form or hosted link in the block settings.' )
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
    window.wp.components
);
