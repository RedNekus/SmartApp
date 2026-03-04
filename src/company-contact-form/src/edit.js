import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();

    const {
        recipientEmail = '',
        subjectPrefix = '',
        enableHubSpot = false,
    } = attributes;

    return (
        <div {...blockProps}>
            <p>{__('Company Contact Form – Editor', 'company-contact-form')}</p>

            <InspectorControls>
                <PanelBody title={__('Settings', 'company-contact-form')}>
                    <TextControl
                        label={__('Recipient Email', 'company-contact-form')}
                        value={recipientEmail}
                        onChange={(val) =>
                            setAttributes({ recipientEmail: val })
                        }
                    />

                    <TextControl
                        label={__('Subject Prefix', 'company-contact-form')}
                        value={subjectPrefix}
                        onChange={(val) =>
                            setAttributes({ subjectPrefix: val })
                        }
                    />

                    <ToggleControl
                        label={__('Enable HubSpot Integration', 'company-contact-form')}
                        checked={enableHubSpot}
                        onChange={(val) =>
                            setAttributes({ enableHubSpot: val })
                        }
                    />
                </PanelBody>
            </InspectorControls>

            <div className="ccf-editor-placeholder">
                <strong>
                    {__('📬 Contact Form', 'company-contact-form')}
                </strong>
                <br />
                <small>
                    {__(
                        'Fields: First Name, Last Name, Email, Message',
                        'company-contact-form'
                    )}
                </small>
            </div>
        </div>
    );
}
