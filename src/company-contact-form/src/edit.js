import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    
    const { recipientEmail, subjectPrefix, enableHubSpot } = attributes;

    return createElement('div', blockProps,
        createElement('p', null, __('Company Contact Form - Editor', 'company-contact-form')),
        
        createElement(InspectorControls, null,
            createElement(PanelBody, { title: __('Settings', 'company-contact-form') },
                createElement(TextControl, {
                    label: __('Recipient Email', 'company-contact-form'),
                    value: recipientEmail || '',
                    onChange: (val) => setAttributes({ recipientEmail: val })
                }),
                createElement(TextControl, {
                    label: __('Subject Prefix', 'company-contact-form'),
                    value: subjectPrefix || '',
                    onChange: (val) => setAttributes({ subjectPrefix: val })
                }),
                createElement(ToggleControl, {
                    label: __('Enable HubSpot Integration', 'company-contact-form'),
                    checked: enableHubSpot || false,
                    onChange: (val) => setAttributes({ enableHubSpot: val })
                })
            )
        ),
        
        createElement('div', { className: 'ccf-editor-placeholder' },
            createElement('strong', null, __('📬 Contact Form', 'company-contact-form')),
            createElement('br'),
            createElement('small', null, __('Fields: First Name, Last Name, Email, Message', 'company-contact-form'))
        )
    );
}
