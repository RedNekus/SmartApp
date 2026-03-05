import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, ExternalLink } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();

    const {
        recipientEmail = '',
        subjectPrefix = '',
        enableHubSpot = false,
        hubspotPortalId = '',
        hubspotFormId = '',
        hubspotAccessToken = '',
    } = attributes;

    return (
        <div {...blockProps}>
            <p>{__('Company Contact Form – Editor', 'company-contact-form')}</p>

            <InspectorControls>
                {/* --- Основные настройки --- */}
                <PanelBody title={__('General Settings', 'company-contact-form')}>
                    <TextControl
                        label={__('Recipient Email', 'company-contact-form')}
                        value={recipientEmail}
                        onChange={(val) => setAttributes({ recipientEmail: val })}
                    />
                    <TextControl
                        label={__('Subject Prefix', 'company-contact-form')}
                        value={subjectPrefix}
                        onChange={(val) => setAttributes({ subjectPrefix: val })}
                    />
                </PanelBody>

                {/* --- Настройки HubSpot --- */}
                <PanelBody 
                    title={__('HubSpot Integration', 'company-contact-form')} 
                    initialOpen={enableHubSpot}
                >
                    <ToggleControl
                        label={__('Enable HubSpot', 'company-contact-form')}
                        checked={enableHubSpot}
                        onChange={(val) => setAttributes({ enableHubSpot: val })}
                    />

                    {enableHubSpot && (
                        <>
                            <TextControl
                                label={__('Portal ID', 'company-contact-form')}
                                help={__('Account ID from HubSpot URL', 'company-contact-form')}
                                value={hubspotPortalId}
                                onChange={(val) => setAttributes({ hubspotPortalId: val })}
                            />
                            <TextControl
                                label={__('Form ID', 'company-contact-form')}
                                help={__('ID of the target HubSpot form', 'company-contact-form')}
                                value={hubspotFormId}
                                onChange={(val) => setAttributes({ hubspotFormId: val })}
                            />
                            <TextControl
                                label={__('Access Token', 'company-contact-form')}
                                help={__('Private App Token (kept secret)', 'company-contact-form')}
                                type="password"
                                value={hubspotAccessToken}
                                onChange={(val) => setAttributes({ hubspotAccessToken: val })}
                            />
                            
                            {/* Предупреждение о Mock-режиме */}
                            {!hubspotAccessToken && (
                                <div style={{
                                    marginTop: '12px',
                                    padding: '12px',
                                    background: '#fff3cd',
                                    borderLeft: '4px solid #ffc107',
                                    fontSize: '12px',
                                    color: '#856404'
                                }}>
                                    <strong>⚠️ {__('Mock Mode', 'company-contact-form')}</strong><br/>
                                    {__('No token configured. Data will be logged but not sent to HubSpot.', 'company-contact-form')}
                                </div>
                            )}

                            <div style={{ marginTop: '12px', fontSize: '11px' }}>
                                <ExternalLink href="https://app.hubspot.com/developer">
                                    {__('Get credentials in HubSpot', 'company-contact-form')}
                                </ExternalLink>
                            </div>
                        </>
                    )}
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
                {enableHubSpot && (
                    <div style={{ marginTop: '8px', color: '#ff9800', fontSize: '11px' }}>
                        + HubSpot Integration {hubspotAccessToken ? '✅' : '⚠️'}
                    </div>
                )}
            </div>
        </div>
    );
}
