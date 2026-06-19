import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import './docs.scss';

// ── Small reusable components ─────────────────────────────────────────────────

const Step = ({ number, title, children }) => (
    <div className="capfw-doc-step">
        <div className="capfw-doc-step-number">{number}</div>
        <div className="capfw-doc-step-body">
            <h4 className="capfw-doc-step-title">{title}</h4>
            <div className="capfw-doc-step-content">{children}</div>
        </div>
    </div>
);

const Callout = ({ type = 'info', children }) => (
    <div className={`capfw-doc-callout capfw-doc-callout--${type}`}>
        <span className="capfw-doc-callout-icon">
            {type === 'warning' ? '⚠️' : type === 'success' ? '✅' : type === 'danger' ? '🚫' : 'ℹ️'}
        </span>
        <div>{children}</div>
    </div>
);

const CodeInline = ({ children }) => (
    <code className="capfw-doc-code-inline">{children}</code>
);

const FieldRow = ({ label, value, desc }) => (
    <div className="capfw-doc-field-row">
        <div className="capfw-doc-field-label">{label}</div>
        <div className="capfw-doc-field-value">
            <CodeInline>{value}</CodeInline>
            {desc && <span className="capfw-doc-field-desc">{desc}</span>}
        </div>
    </div>
);

const SectionHeading = ({ id, children }) => (
    <h3 className="capfw-doc-section-heading" id={id}>{children}</h3>
);

// ── Token Flow Visual Step ─────────────────────────────────────────────────────
const TokenFlowStep = ({ num, label, sublabel, icon, active = false }) => (
    <div className={`capfw-token-flow-step${active ? ' capfw-token-flow-step--active' : ''}`}>
        <div className="capfw-token-flow-icon">{icon}</div>
        <div className="capfw-token-flow-num">{num}</div>
        <div className="capfw-token-flow-label">{label}</div>
        {sublabel && <div className="capfw-token-flow-sublabel">{sublabel}</div>}
    </div>
);

const TokenFlowArrow = () => (
    <div className="capfw-token-flow-arrow">→</div>
);

// ── Download helper ───────────────────────────────────────────────────────────
const generateDocContent = () => {
    return `CAPTAIN FUNNEL FOR WHATSAPP — API CREDENTIALS SETUP GUIDE
======================================================
Plugin: Captain Funnel for WhatsApp
Version: 0.0.1

This document explains how to create your WhatsApp Cloud API credentials
from Meta and connect them to the plugin.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WHAT YOU NEED (3 credentials from Meta)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. ACCESS TOKEN    — Starts with "EAA..." — authenticates your API calls
2. PHONE NUMBER ID — Numeric only — ID of your WhatsApp business phone
3. WABA ID         — Numeric only — Your WhatsApp Business Account ID

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PREREQUISITES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ A Facebook personal account
✓ A Meta Business Account (free — business.facebook.com)
✓ A phone number NOT registered on any WhatsApp app
  (Using a registered number will deregister it from the app!)
✓ Business name, address, website ready for verification

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 1 — CREATE META BUSINESS ACCOUNT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Go to: https://business.facebook.com
2. Log in with your Facebook account
3. Click "Create Account"
4. Enter: Business name, Your name, Business email
5. Note your Business Account ID from Business Settings → Business Info

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 2 — CREATE META DEVELOPER APP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Go to: https://developers.facebook.com
2. Click "My Apps" → "Create App"
3. Use case: Select "Other"
4. App type: Select "Business"
5. App name: e.g. "My Store WhatsApp"
6. Connect to your Meta Business Account → Click "Create App"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 3 — ADD WHATSAPP TO YOUR APP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. On the App Dashboard, scroll to find "WhatsApp" product
2. Click "Set up" on the WhatsApp card
3. Connect or create a WhatsApp Business Account (WABA)
4. You'll land on the WhatsApp Quickstart page

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 4 — COPY YOUR CREDENTIALS FROM API SETUP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Go to: App Dashboard → WhatsApp → API Setup

From this page, copy:

  Temporary Access Token  → EAAxxxx (expires 24h — for testing only)
  Phone Number ID         → Select number in "From" dropdown → copy ID below
  WhatsApp Business ID    → Listed as "WhatsApp Business Account ID"

⚠ The temporary token expires every 24 hours.
  You MUST create a permanent token (Step 6) for the plugin to work.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 5 — ADD YOUR BUSINESS PHONE NUMBER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Go to: WhatsApp → Phone Numbers → Add phone number
2. Enter:
   - Display name (your business name — needs Meta approval)
   - Category (e.g. Retail, E-commerce)
   - Business description
3. Enter the phone number and verify via OTP (SMS or voice call)

🚫 WARNING: This number will be DEREGISTERED from any WhatsApp app.
   Use a dedicated business number you don't use on your phone.

4. After adding, go to WhatsApp → API Setup
5. Select your new number from "From" dropdown
6. Copy the Phone Number ID shown below the dropdown

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 6 — CREATE PERMANENT ACCESS TOKEN (MOST IMPORTANT)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

The permanent token is created through a System User in Meta Business Suite.
This is different from the App Dashboard.

TOKEN CREATION FLOW:
  Meta Business Suite → System Users → Create User → Assign App → Generate Token

DETAILED STEPS:

1. Go to: https://business.facebook.com/settings
   Path: Users → System Users → Add

2. Create System User:
   - System user name: e.g. "WhatsApp Bot"
   - Role: Admin
   - Click "Create System User"

3. Assign App to System User:
   - Click "Add Assets" button
   - Select "Apps" tab
   - Choose your WhatsApp app
   - Set permission to "Full Control"
   - Click Save

4. Generate Token:
   - Click "Generate New Token"
   - Select your WhatsApp app
   - Token Expiration: NEVER (very important!)
   - Enable these permissions:
     ✓ whatsapp_business_messaging
     ✓ whatsapp_business_management
     ✓ business_management
   - Click "Generate Token"

5. COPY THE TOKEN IMMEDIATELY:
   - The token is shown only ONCE
   - It starts with "EAA..."
   - Store it in a password manager or secure notes
   - This is your PERMANENT ACCESS TOKEN for the plugin

⚠ If you lose this token, you must generate a new one.
ℹ Keep "Token Expiration: Never" — otherwise your plugin stops working.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 7 — ENTER CREDENTIALS IN THE PLUGIN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Go to: WordPress Admin → WA Funnel → Settings
2. Fill in:
   - Access Token     → paste your permanent token (EAA...)
   - Phone Number ID  → paste the numeric Phone Number ID
   - Business Acct ID → paste your WABA ID
3. Click "Save Settings"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 8 — TEST THE CONNECTION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. On the Settings page, click "Test Connection"
2. The plugin will call the Meta API and verify credentials

✅ "Connection successful!" → Everything is working correctly
✗  "Connection failed"     → Check token and Phone Number ID

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
GOING LIVE (PRODUCTION MODE)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

By default your app is in Development mode (can only message 5 test numbers).
To send to any customer:

1. Complete Business Verification (Meta Business Suite → Security Center)
2. Get Display Name approved (1–3 business days)
3. Submit App for Review (App Review → whatsapp_business_messaging permission)
4. Switch app to Live mode in App Dashboard top bar

ℹ In Development mode you can add up to 5 test recipient numbers for free.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❓ Test Connection says "Connection failed"
   → Use permanent token (not 24h temporary token)
   → Verify Phone Number ID matches the selected number in API Setup
   → Check System User has whatsapp_business_messaging permission
   → App should not be suspended in App Dashboard

❓ Messages not received
   → In Development mode, add recipient in API Setup → "To" section
   → Phone must be in E.164 format: country code + number, no spaces
     Example: 919876543210 for India (+91 98765 43210)
   → Check Logs page in plugin for API error details

❓ Token expired
   → You used the temporary token — create permanent via System User (Step 6)
   → When generating, set expiry to "Never"

❓ "Recipient phone number not in allowed list"
   → App is in Development mode — add test recipients or go Live

❓ Order notifications not firing
   → Settings → enable the relevant order statuses
   → Integrations page → WooCommerce must be enabled
   → Customer order must have a billing phone number

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FAQ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Q: Is WhatsApp Cloud API free?
A: 1,000 free service conversations/month. Then $0.005–$0.09/conversation by country.

Q: Can I use my personal WhatsApp number?
A: No. It will be deregistered from the app. Use a dedicated business number.

Q: Do I need WhatsApp message templates?
A: Only for marketing messages outside a 24h customer window. Order
   notifications triggered by WooCommerce events can be free-form text.

Q: What phone number format is needed?
A: E.164 — country code + number, digits only, no +, no spaces.
   Example: 919876543210 for +91 98765 43210 (India)

Q: Why is my display name "Pending"?
A: Meta review takes 1–3 business days. Messages still send during this time.

Q: Can one Meta App serve multiple stores?
A: Yes — add multiple phone numbers, each store uses a different Phone Number ID.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OFFICIAL LINKS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Meta Developers Portal   : https://developers.facebook.com
Meta Business Suite      : https://business.facebook.com
WhatsApp Cloud API Docs  : https://developers.facebook.com/docs/whatsapp/cloud-api
Pricing & Limits         : https://developers.facebook.com/docs/whatsapp/pricing

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Generated by Captain Funnel for WhatsApp
https://developers.facebook.com/docs/whatsapp/cloud-api
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;
};

const handleDownload = () => {
    const content  = generateDocContent();
    const blob     = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url      = URL.createObjectURL(blob);
    const link     = document.createElement('a');
    link.href      = url;
    link.download  = 'whatsapp-api-credentials-setup-guide.txt';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

// ── Table of Contents ─────────────────────────────────────────────────────────
const TOC_ITEMS = [
    { id: 'overview',        label: 'Overview' },
    { id: 'prerequisites',   label: 'Prerequisites' },
    { id: 'meta-business',   label: 'Step 1 — Meta Business Account' },
    { id: 'create-app',      label: 'Step 2 — Create a Meta App' },
    { id: 'whatsapp-setup',  label: 'Step 3 — WhatsApp Setup' },
    { id: 'get-credentials', label: 'Step 4 — Get Your Credentials' },
    { id: 'phone-number',    label: 'Step 5 — Add a Phone Number' },
    { id: 'permanent-token', label: 'Step 6 — Create Permanent Token' },
    { id: 'plugin-settings', label: 'Step 7 — Enter in Plugin' },
    { id: 'test-message',    label: 'Step 8 — Send a Test Message' },
    { id: 'go-live',         label: 'Going Live (Production)' },
    { id: 'troubleshoot',    label: 'Troubleshooting' },
    { id: 'faq',             label: 'FAQ' },
];

// ── Main Component ────────────────────────────────────────────────────────────
const DocsCredentials = () => {
    const [activeSection, setActiveSection] = useState('overview');
    const [downloaded,    setDownloaded]    = useState(false);

    const scrollTo = (id) => {
        setActiveSection(id);
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const onDownload = () => {
        handleDownload();
        setDownloaded(true);
        setTimeout(() => setDownloaded(false), 3000);
    };

    return (
        <div className="capfw-docs-page">

            {/* Page header */}
            <div className="capfw-docs-page-header">
                <div>
                    <h2 className="capfw-page-title">
                        {__('WhatsApp Cloud API — Setup Guide', 'captain-funnel-for-whatsapp')}
                    </h2>
                    <p className="capfw-page-subtitle">
                        {__('Complete step-by-step guide to create your Meta App, get API credentials, and connect the plugin.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                {/* Download button */}
                <button
                    type="button"
                    className={`capfw-docs-download-btn${downloaded ? ' capfw-docs-download-btn--done' : ''}`}
                    onClick={onDownload}
                    title={__('Download as .txt file — share with ChatGPT or Claude for help', 'captain-funnel-for-whatsapp')}
                >
                    {downloaded ? (
                        <>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            {__('Downloaded!', 'captain-funnel-for-whatsapp')}
                        </>
                    ) : (
                        <>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            {__('Download Guide', 'captain-funnel-for-whatsapp')}
                        </>
                    )}
                </button>
            </div>

            {/* Download helper tip */}
            <div className="capfw-docs-download-tip">
                <span>💡</span>
                <span>
                    {__('Confused about any step? Download this guide and share the .txt file with ', 'captain-funnel-for-whatsapp')}
                    <strong>ChatGPT</strong> {__('or', 'captain-funnel-for-whatsapp')} <strong>Claude</strong>
                    {__(' — they can walk you through it in your language.', 'captain-funnel-for-whatsapp')}
                </span>
            </div>

            <div className="capfw-docs-layout">

                {/* Left TOC sidebar */}
                <aside className="capfw-docs-toc">
                    <div className="capfw-docs-toc-title">{__('On This Page', 'captain-funnel-for-whatsapp')}</div>
                    <nav>
                        {TOC_ITEMS.map(item => (
                            <button
                                key={item.id}
                                type="button"
                                className={`capfw-docs-toc-link${activeSection === item.id ? ' capfw-docs-toc-link--active' : ''}`}
                                onClick={() => scrollTo(item.id)}
                            >
                                {item.label}
                            </button>
                        ))}
                    </nav>

                    {/* Download in sidebar too */}
                    <button
                        type="button"
                        className="capfw-docs-toc-download"
                        onClick={onDownload}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" width="13" height="13">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        {downloaded ? __('Downloaded!', 'captain-funnel-for-whatsapp') : __('Download Guide', 'captain-funnel-for-whatsapp')}
                    </button>

                    {/* Quick links box */}
                    <div className="capfw-docs-quicklinks">
                        <div className="capfw-docs-quicklinks-title">{__('Official Links', 'captain-funnel-for-whatsapp')}</div>
                        <a href="https://developers.facebook.com" target="_blank" rel="noopener noreferrer" className="capfw-docs-ext-link">Meta Developers Portal ↗</a>
                        <a href="https://business.facebook.com" target="_blank" rel="noopener noreferrer" className="capfw-docs-ext-link">Meta Business Suite ↗</a>
                        <a href="https://developers.facebook.com/docs/whatsapp/cloud-api" target="_blank" rel="noopener noreferrer" className="capfw-docs-ext-link">WhatsApp Cloud API Docs ↗</a>
                        <a href="https://developers.facebook.com/docs/whatsapp/pricing" target="_blank" rel="noopener noreferrer" className="capfw-docs-ext-link">Pricing & Limits ↗</a>
                    </div>
                </aside>

                {/* Main content */}
                <div className="capfw-docs-content">

                    {/* ── OVERVIEW ─────────────────────────────────────────── */}
                    <section id="overview">
                        <SectionHeading id="overview">{__('Overview', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <p>{__('Captain Funnel for WhatsApp uses the official WhatsApp Cloud API by Meta to send messages. You need three credentials from Meta to connect the plugin.', 'captain-funnel-for-whatsapp')}</p>
                        <div className="capfw-doc-credentials-preview">
                            <FieldRow label="Access Token"        value="EAAxxxxxxxxxxxxxxx..." desc={__('Authenticates your API calls — created via System User', 'captain-funnel-for-whatsapp')} />
                            <FieldRow label="Phone Number ID"     value="1234567890123456"     desc={__('ID of your WhatsApp business phone number', 'captain-funnel-for-whatsapp')} />
                            <FieldRow label="Business Account ID" value="9876543210987654"     desc={__('Your WhatsApp Business Account (WABA) ID', 'captain-funnel-for-whatsapp')} />
                        </div>
                        <Callout type="info">
                            {__('Meta offers 1,000 free service conversations per month. After that, pricing is per conversation by country ($0.005–$0.09). See the Pricing link in the sidebar.', 'captain-funnel-for-whatsapp')}
                        </Callout>
                    </section>

                    {/* ── PREREQUISITES ────────────────────────────────────── */}
                    <section id="prerequisites">
                        <SectionHeading id="prerequisites">{__('Prerequisites', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <p>{__('Before you begin, make sure you have:', 'captain-funnel-for-whatsapp')}</p>
                        <div className="capfw-doc-checklist">
                            {[
                                __('A Facebook account (personal account is fine)', 'captain-funnel-for-whatsapp'),
                                __('A Meta Business Account (free — business.facebook.com)', 'captain-funnel-for-whatsapp'),
                                __('A phone number NOT already registered on WhatsApp personal or business app', 'captain-funnel-for-whatsapp'),
                                __('Business details ready — name, address, website (for verification later)', 'captain-funnel-for-whatsapp'),
                            ].map((item, i) => (
                                <div key={i} className="capfw-doc-checklist-item">
                                    <span className="capfw-doc-check">✓</span>
                                    <span>{item}</span>
                                </div>
                            ))}
                        </div>
                        <Callout type="warning">
                            {__('The phone number used for WhatsApp Business API must NOT be active on any WhatsApp app. Adding it here will deregister it from the app permanently. Use a dedicated business SIM or virtual number.', 'captain-funnel-for-whatsapp')}
                        </Callout>
                    </section>

                    {/* ── STEP 1 ───────────────────────────────────────────── */}
                    <section id="meta-business">
                        <SectionHeading id="meta-business">{__('Step 1 — Create a Meta Business Account', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <Step number="1" title={__('Go to Meta Business Suite', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Open', 'captain-funnel-for-whatsapp')} <a href="https://business.facebook.com" target="_blank" rel="noopener noreferrer">business.facebook.com</a> {__('and log in with your Facebook account.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <Step number="2" title={__('Create a Business Account', 'captain-funnel-for-whatsapp')}>
                            <p>{__('If you don\'t have a Business Account, click "Create Account". Enter your business name, your name, and business email.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <Step number="3" title={__('Note your Business Account ID', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Go to Business Settings → Business Info. Your Business Account ID is shown there. Keep it handy.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                    </section>

                    {/* ── STEP 2 ───────────────────────────────────────────── */}
                    <section id="create-app">
                        <SectionHeading id="create-app">{__('Step 2 — Create a Meta Developer App', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <Step number="1" title={__('Open Meta Developers Portal', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Go to', 'captain-funnel-for-whatsapp')} <a href="https://developers.facebook.com" target="_blank" rel="noopener noreferrer">developers.facebook.com</a> {__('and log in.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <Step number="2" title={__('Create a New App', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Click "My Apps" → "Create App". Select:', 'captain-funnel-for-whatsapp')}</p>
                            <ul className="capfw-doc-list">
                                <li><strong>{__('Use case:', 'captain-funnel-for-whatsapp')}</strong>{__(' Other', 'captain-funnel-for-whatsapp')}</li>
                                <li><strong>{__('App type:', 'captain-funnel-for-whatsapp')}</strong>{__(' Business', 'captain-funnel-for-whatsapp')}</li>
                                <li><strong>{__('App name:', 'captain-funnel-for-whatsapp')}</strong>{__(' e.g. "My Store WhatsApp"', 'captain-funnel-for-whatsapp')}</li>
                            </ul>
                        </Step>
                        <Step number="3" title={__('Connect to Business Account', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Select the Meta Business Account from Step 1. Click "Create App".', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                    </section>

                    {/* ── STEP 3 ───────────────────────────────────────────── */}
                    <section id="whatsapp-setup">
                        <SectionHeading id="whatsapp-setup">{__('Step 3 — Add WhatsApp to Your App', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <Step number="1" title={__('Add WhatsApp Product', 'captain-funnel-for-whatsapp')}>
                            <p>{__('On the App Dashboard, scroll to find the "WhatsApp" product card. Click "Set up".', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <Step number="2" title={__('Connect a WhatsApp Business Account', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Select "Create new WhatsApp Business Account" or connect an existing one. Meta will create a WABA automatically.', 'captain-funnel-for-whatsapp')}</p>
                            <Callout type="info">{__('WABA (WhatsApp Business Account) is specifically for WhatsApp messaging — it is different from your Meta Business Account.', 'captain-funnel-for-whatsapp')}</Callout>
                        </Step>
                        <Step number="3" title={__('WhatsApp Quickstart', 'captain-funnel-for-whatsapp')}>
                            <p>{__('You\'ll land on the WhatsApp Quickstart page with a free Meta test number. Use this to test the connection initially.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                    </section>

                    {/* ── STEP 4 ───────────────────────────────────────────── */}
                    <section id="get-credentials">
                        <SectionHeading id="get-credentials">{__('Step 4 — Get Your Credentials', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <p>{__('On the WhatsApp API Setup page (App Dashboard → WhatsApp → API Setup), find:', 'captain-funnel-for-whatsapp')}</p>
                        <div className="capfw-doc-credentials-preview" style={{marginTop:'14px'}}>
                            <FieldRow label="Temporary Token"   value="EAAxxxx... (24hr)" desc={__('Testing only — expires in 24 hours. Create permanent token in Step 6.', 'captain-funnel-for-whatsapp')} />
                            <FieldRow label="Phone Number ID"   value='"From" dropdown → copy ID' desc={__('Select your phone number in "From" section → the numeric ID appears below the dropdown', 'captain-funnel-for-whatsapp')} />
                            <FieldRow label="WABA ID"           value='"WhatsApp Business Account ID"' desc={__('Listed on the same API Setup page', 'captain-funnel-for-whatsapp')} />
                        </div>
                        <Callout type="warning">
                            {__('Phone Number ID and WABA ID are numeric only (no letters). Access Token starts with "EAA". Copy them carefully.', 'captain-funnel-for-whatsapp')}
                        </Callout>
                    </section>

                    {/* ── STEP 5 ───────────────────────────────────────────── */}
                    <section id="phone-number">
                        <SectionHeading id="phone-number">{__('Step 5 — Add Your Business Phone Number', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <p>{__('The default Meta test number can only message 5 pre-approved contacts. Add your own business number to message real customers.', 'captain-funnel-for-whatsapp')}</p>
                        <Step number="1" title={__('Go to WhatsApp → Phone Numbers → Add Phone Number', 'captain-funnel-for-whatsapp')}>
                            <ul className="capfw-doc-list">
                                <li><strong>{__('Display name:', 'captain-funnel-for-whatsapp')}</strong>{__(' Your business name (needs Meta approval)', 'captain-funnel-for-whatsapp')}</li>
                                <li><strong>{__('Category:', 'captain-funnel-for-whatsapp')}</strong>{__(' e.g. Retail, E-commerce', 'captain-funnel-for-whatsapp')}</li>
                                <li><strong>{__('Description:', 'captain-funnel-for-whatsapp')}</strong>{__(' Brief business description', 'captain-funnel-for-whatsapp')}</li>
                            </ul>
                        </Step>
                        <Step number="2" title={__('Verify Your Phone Number', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Enter the phone number → receive OTP via SMS or voice → enter OTP to verify.', 'captain-funnel-for-whatsapp')}</p>
                            <Callout type="danger">{__('This number will be DEREGISTERED from WhatsApp app after adding here. Use a dedicated business number.', 'captain-funnel-for-whatsapp')}</Callout>
                        </Step>
                        <Step number="3" title={__('Copy the New Phone Number ID', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Go to WhatsApp → API Setup → select your new number from "From" dropdown → copy the Phone Number ID shown below.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                    </section>

                    {/* ── STEP 6 — PERMANENT TOKEN (enhanced) ──────────────── */}
                    <section id="permanent-token">
                        <SectionHeading id="permanent-token">{__('Step 6 — Create a Permanent Access Token', 'captain-funnel-for-whatsapp')}</SectionHeading>

                        <p>{__('The temporary token from the Quickstart page expires every 24 hours. You must create a permanent token for the plugin to keep working.', 'captain-funnel-for-whatsapp')}</p>

                        <Callout type="warning">
                            {__('Permanent tokens are created in Meta Business Suite via a System User — NOT from the App Dashboard. This is where most people get confused.', 'captain-funnel-for-whatsapp')}
                        </Callout>

                        {/* Visual flow diagram */}
                        <div className="capfw-token-flow">
                            <div className="capfw-token-flow-title">{__('Token Creation Flow', 'captain-funnel-for-whatsapp')}</div>
                            <div className="capfw-token-flow-row">
                                <TokenFlowStep num="1" icon="🏢" label="Meta Business Suite"   sublabel="business.facebook.com/settings" />
                                <TokenFlowArrow />
                                <TokenFlowStep num="2" icon="👤" label="System Users"          sublabel="Users → System Users → Add" />
                                <TokenFlowArrow />
                                <TokenFlowStep num="3" icon="📱" label="Assign App"            sublabel="Add Assets → Apps → Full Control" />
                                <TokenFlowArrow />
                                <TokenFlowStep num="4" icon="🔑" label="Generate Token"        sublabel="Expiry: Never + 3 permissions" active={true} />
                                <TokenFlowArrow />
                                <TokenFlowStep num="5" icon="📋" label="Copy & Save"           sublabel="Shown ONCE — store securely" />
                            </div>
                        </div>

                        {/* Detailed steps */}
                        <Step number="1" title={__('Go to Meta Business Suite → Settings', 'captain-funnel-for-whatsapp')}>
                            <p>
                                {__('URL:', 'captain-funnel-for-whatsapp')}{' '}
                                <a href="https://business.facebook.com/settings" target="_blank" rel="noopener noreferrer">
                                    business.facebook.com/settings
                                </a>
                            </p>
                            <div className="capfw-doc-path">
                                Settings → Users → System Users
                            </div>
                        </Step>

                        <Step number="2" title={__('Create a System User', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Click "Add" to create a new System User:', 'captain-funnel-for-whatsapp')}</p>
                            <div className="capfw-doc-field-box">
                                <div className="capfw-doc-field-box-row">
                                    <span className="capfw-doc-field-box-label">{__('System User Name', 'captain-funnel-for-whatsapp')}</span>
                                    <span className="capfw-doc-field-box-value">{__('e.g. "WhatsApp Bot" or "WA Automation"', 'captain-funnel-for-whatsapp')}</span>
                                </div>
                                <div className="capfw-doc-field-box-row">
                                    <span className="capfw-doc-field-box-label">{__('Role', 'captain-funnel-for-whatsapp')}</span>
                                    <span className="capfw-doc-field-box-value capfw-doc-field-box-value--important">{__('Admin (required)', 'captain-funnel-for-whatsapp')}</span>
                                </div>
                            </div>
                            <p style={{marginTop:'10px'}}>{__('Click "Create System User".', 'captain-funnel-for-whatsapp')}</p>
                        </Step>

                        <Step number="3" title={__('Assign Your WhatsApp App to the System User', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Click "Add Assets" button on the System User page:', 'captain-funnel-for-whatsapp')}</p>
                            <div className="capfw-doc-path">
                                Add Assets → Apps tab → Select your WhatsApp app → Full Control → Save
                            </div>
                            <Callout type="info">
                                {__('If you don\'t see your app in the list, make sure you connected it to your Business Account when creating it in Step 2.', 'captain-funnel-for-whatsapp')}
                            </Callout>
                        </Step>

                        <Step number="4" title={__('Generate the Permanent Token', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Click "Generate New Token" on the System User row. A dialog will open:', 'captain-funnel-for-whatsapp')}</p>

                            <div className="capfw-doc-token-config">
                                <div className="capfw-doc-token-config-title">{__('Token Configuration', 'captain-funnel-for-whatsapp')}</div>

                                <div className="capfw-doc-token-config-row">
                                    <div className="capfw-doc-token-config-label">{__('Select App', 'captain-funnel-for-whatsapp')}</div>
                                    <div className="capfw-doc-token-config-value">
                                        {__('Choose your WhatsApp app from the dropdown', 'captain-funnel-for-whatsapp')}
                                    </div>
                                </div>

                                <div className="capfw-doc-token-config-row capfw-doc-token-config-row--highlight">
                                    <div className="capfw-doc-token-config-label">
                                        ⚠ {__('Token Expiration', 'captain-funnel-for-whatsapp')}
                                    </div>
                                    <div className="capfw-doc-token-config-value">
                                        <strong>{__('Select "Never"', 'captain-funnel-for-whatsapp')}</strong>
                                        <span className="capfw-doc-token-config-note">
                                            {__(' — If you choose any other option, the token will expire and your plugin will stop sending messages.', 'captain-funnel-for-whatsapp')}
                                        </span>
                                    </div>
                                </div>

                                <div className="capfw-doc-token-config-row">
                                    <div className="capfw-doc-token-config-label">{__('Permissions', 'captain-funnel-for-whatsapp')}</div>
                                    <div className="capfw-doc-token-config-value">
                                        <p style={{marginBottom:'8px'}}>{__('Enable all three of these:', 'captain-funnel-for-whatsapp')}</p>
                                        <div className="capfw-doc-perm-list">
                                            {[
                                                { name: 'whatsapp_business_messaging',  desc: __('Send & receive WhatsApp messages', 'captain-funnel-for-whatsapp') },
                                                { name: 'whatsapp_business_management', desc: __('Manage WhatsApp business settings', 'captain-funnel-for-whatsapp') },
                                                { name: 'business_management',          desc: __('Access business account info', 'captain-funnel-for-whatsapp') },
                                            ].map(p => (
                                                <div key={p.name} className="capfw-doc-perm-item">
                                                    <span className="capfw-doc-perm-check">✓</span>
                                                    <div>
                                                        <CodeInline>{p.name}</CodeInline>
                                                        <span className="capfw-doc-perm-desc"> — {p.desc}</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p style={{marginTop:'12px'}}>{__('Click "Generate Token".', 'captain-funnel-for-whatsapp')}</p>
                        </Step>

                        <Step number="5" title={__('Copy Your Token — Shown Only Once!', 'captain-funnel-for-whatsapp')}>
                            <div className="capfw-doc-token-reveal">
                                <div className="capfw-doc-token-reveal-icon">🔑</div>
                                <div className="capfw-doc-token-reveal-content">
                                    <div className="capfw-doc-token-reveal-title">{__('Your token will look like this:', 'captain-funnel-for-whatsapp')}</div>
                                    <div className="capfw-doc-token-example">EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</div>
                                    <ul className="capfw-doc-list" style={{marginTop:'10px'}}>
                                        <li>{__('Copy the entire token immediately', 'captain-funnel-for-whatsapp')}</li>
                                        <li>{__('Store it in a password manager (1Password, Bitwarden, etc.) or secure notes', 'captain-funnel-for-whatsapp')}</li>
                                        <li>{__('If you close this dialog without copying, you must generate a NEW token', 'captain-funnel-for-whatsapp')}</li>
                                    </ul>
                                </div>
                            </div>
                            <Callout type="danger">
                                {__('This token is shown ONLY ONCE. Meta will never show it again. If lost, you must generate a new one from the System User page.', 'captain-funnel-for-whatsapp')}
                            </Callout>
                        </Step>
                    </section>

                    {/* ── STEP 7 ───────────────────────────────────────────── */}
                    <section id="plugin-settings">
                        <SectionHeading id="plugin-settings">{__('Step 7 — Enter Credentials in the Plugin', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <Step number="1" title={__('Go to WA Funnel → Settings', 'captain-funnel-for-whatsapp')}>
                            <p>{__('In your WordPress admin, go to WA Funnel → Settings in the left sidebar.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <Step number="2" title={__('Fill in the Three Fields', 'captain-funnel-for-whatsapp')}>
                            <div className="capfw-doc-credentials-preview" style={{marginTop:'10px'}}>
                                <FieldRow label="Access Token"        value={__('Paste your permanent token (EAA...)', 'captain-funnel-for-whatsapp')} desc="" />
                                <FieldRow label="Phone Number ID"     value={__('Paste the numeric Phone Number ID', 'captain-funnel-for-whatsapp')} desc="" />
                                <FieldRow label="Business Account ID" value={__('Paste your WABA ID', 'captain-funnel-for-whatsapp')} desc="" />
                            </div>
                        </Step>
                        <Step number="3" title={__('Click "Save Settings"', 'captain-funnel-for-whatsapp')}>
                            <p>{__('Click the green "Save Settings" button. Your credentials are now stored securely in your WordPress database.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                    </section>

                    {/* ── STEP 8 ───────────────────────────────────────────── */}
                    <section id="test-message">
                        <SectionHeading id="test-message">{__('Step 8 — Test the Connection', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <Step number="1" title={__('Click "Test Connection" on the Settings page', 'captain-funnel-for-whatsapp')}>
                            <p>{__('The plugin calls the Meta API with your credentials and verifies them in real time.', 'captain-funnel-for-whatsapp')}</p>
                        </Step>
                        <div className="capfw-doc-test-results">
                            <div className="capfw-doc-test-result capfw-doc-test-result--success">
                                <span>✓</span>
                                <div>
                                    <strong>{__('Connection successful!', 'captain-funnel-for-whatsapp')}</strong>
                                    <p>{__('Your credentials are correct. The plugin is ready to send WhatsApp messages.', 'captain-funnel-for-whatsapp')}</p>
                                </div>
                            </div>
                            <div className="capfw-doc-test-result capfw-doc-test-result--error">
                                <span>✗</span>
                                <div>
                                    <strong>{__('Connection failed', 'captain-funnel-for-whatsapp')}</strong>
                                    <p>{__('Check that your Access Token is the permanent one (not the 24h token), and the Phone Number ID matches the token\'s assigned number.', 'captain-funnel-for-whatsapp')}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* ── GO LIVE ──────────────────────────────────────────── */}
                    <section id="go-live">
                        <SectionHeading id="go-live">{__('Going Live (Production)', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <p>{__('In Development mode you can only message 5 manually added test contacts. To send to any real customer number:', 'captain-funnel-for-whatsapp')}</p>
                        <div className="capfw-doc-live-steps">
                            {[
                                { num: 1, title: __('Complete Business Verification', 'captain-funnel-for-whatsapp'), desc: __('Meta Business Suite → Security Center → Start Verification. Upload business documents.', 'captain-funnel-for-whatsapp') },
                                { num: 2, title: __('Get Display Name Approved', 'captain-funnel-for-whatsapp'), desc: __('Your WhatsApp business display name needs Meta review — usually 1–3 business days.', 'captain-funnel-for-whatsapp') },
                                { num: 3, title: __('Submit App for Review', 'captain-funnel-for-whatsapp'), desc: __('App Dashboard → App Review → Request "whatsapp_business_messaging" permission. Describe your use case.', 'captain-funnel-for-whatsapp') },
                                { num: 4, title: __('Switch to Live Mode', 'captain-funnel-for-whatsapp'), desc: __('After approval, toggle your app from Development to Live in the App Dashboard top bar.', 'captain-funnel-for-whatsapp') },
                            ].map(item => (
                                <div key={item.num} className="capfw-doc-live-step">
                                    <div className="capfw-doc-live-step-num">{item.num}</div>
                                    <div><strong>{item.title}</strong><p>{item.desc}</p></div>
                                </div>
                            ))}
                        </div>
                        <Callout type="info">
                            {__('In Development mode you can add up to 5 test recipient numbers for free — useful for testing before going live.', 'captain-funnel-for-whatsapp')}
                        </Callout>
                    </section>

                    {/* ── TROUBLESHOOTING ──────────────────────────────────── */}
                    <section id="troubleshoot">
                        <SectionHeading id="troubleshoot">{__('Troubleshooting', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <div className="capfw-doc-trouble-list">
                            {[
                                {
                                    problem: __('Test Connection says "Connection failed"', 'captain-funnel-for-whatsapp'),
                                    fixes: [
                                        __('Use the permanent token (not the 24-hour temporary token from Quickstart)', 'captain-funnel-for-whatsapp'),
                                        __('Verify the Phone Number ID matches the number selected in API Setup', 'captain-funnel-for-whatsapp'),
                                        __('Ensure the System User has whatsapp_business_messaging permission', 'captain-funnel-for-whatsapp'),
                                        __('Check the app is not suspended in App Dashboard', 'captain-funnel-for-whatsapp'),
                                    ],
                                },
                                {
                                    problem: __('Messages are not being received', 'captain-funnel-for-whatsapp'),
                                    fixes: [
                                        __('In Development mode, add recipient in Meta Developers → WhatsApp → API Setup → "To" section', 'captain-funnel-for-whatsapp'),
                                        __('Phone must be E.164 format — country code + number, no +, no spaces (e.g. 919876543210)', 'captain-funnel-for-whatsapp'),
                                        __('Check the Logs page in the plugin for the API response and exact error', 'captain-funnel-for-whatsapp'),
                                    ],
                                },
                                {
                                    problem: __('Token expired / stopped working', 'captain-funnel-for-whatsapp'),
                                    fixes: [
                                        __('You used the temporary token — create a permanent one via System User (Step 6)', 'captain-funnel-for-whatsapp'),
                                        __('When generating token, set expiry to "Never" — other options cause expiry', 'captain-funnel-for-whatsapp'),
                                    ],
                                },
                                {
                                    problem: __('Error: "Recipient phone number not in allowed list"', 'captain-funnel-for-whatsapp'),
                                    fixes: [__('App is in Development mode — add test recipients in API Setup, or complete business verification and go Live', 'captain-funnel-for-whatsapp')],
                                },
                                {
                                    problem: __('Order notifications not firing', 'captain-funnel-for-whatsapp'),
                                    fixes: [
                                        __('Settings → Order Status Notifications → enable the relevant statuses', 'captain-funnel-for-whatsapp'),
                                        __('Integrations page → WooCommerce must be enabled', 'captain-funnel-for-whatsapp'),
                                        __('Customer order must have a billing phone number', 'captain-funnel-for-whatsapp'),
                                        __('Check Logs page — if status is "failed", the response shows the exact error', 'captain-funnel-for-whatsapp'),
                                    ],
                                },
                            ].map((item, i) => (
                                <div key={i} className="capfw-doc-trouble-item">
                                    <div className="capfw-doc-trouble-problem">❓ {item.problem}</div>
                                    <ul className="capfw-doc-list">{item.fixes.map((f, j) => <li key={j}>{f}</li>)}</ul>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* ── FAQ ──────────────────────────────────────────────── */}
                    <section id="faq">
                        <SectionHeading id="faq">{__('Frequently Asked Questions', 'captain-funnel-for-whatsapp')}</SectionHeading>
                        <div className="capfw-doc-faq-list">
                            {[
                                { q: __('Is WhatsApp Cloud API free?', 'captain-funnel-for-whatsapp'), a: __('Meta offers 1,000 free service conversations per month. After that, pricing is per conversation by country — typically $0.005–$0.09. Marketing messages cost more than utility messages.', 'captain-funnel-for-whatsapp') },
                                { q: __('Can I use my personal WhatsApp number?', 'captain-funnel-for-whatsapp'), a: __('No. Adding a number to WhatsApp Business API deregisters it from the WhatsApp app permanently. Use a dedicated business SIM or virtual number (Twilio, etc.).', 'captain-funnel-for-whatsapp') },
                                { q: __('Do I need WhatsApp message templates?', 'captain-funnel-for-whatsapp'), a: __('Only for marketing messages outside a 24-hour customer window. WooCommerce order notifications are utility messages and can be sent as free-form text without pre-approved templates.', 'captain-funnel-for-whatsapp') },
                                { q: __('What phone number format is needed?', 'captain-funnel-for-whatsapp'), a: __('E.164 format — country code + number, digits only, no +, no spaces. Example: 919876543210 for India (+91 98765 43210). The plugin strips non-numeric characters automatically.', 'captain-funnel-for-whatsapp') },
                                { q: __('Why is my display name "Pending"?', 'captain-funnel-for-whatsapp'), a: __('WhatsApp display names require Meta review (1–3 business days). Messages still send during this time — the phone number shows instead of the business name until approved.', 'captain-funnel-for-whatsapp') },
                                { q: __('Can one Meta App serve multiple stores?', 'captain-funnel-for-whatsapp'), a: __('Yes — add multiple phone numbers to one WABA. Each store uses a different Phone Number ID but can share the same Access Token and WABA ID.', 'captain-funnel-for-whatsapp') },
                            ].map((item, i) => (
                                <div key={i} className="capfw-doc-faq-item">
                                    <div className="capfw-doc-faq-q">Q: {item.q}</div>
                                    <div className="capfw-doc-faq-a">{item.a}</div>
                                </div>
                            ))}
                        </div>
                    </section>

                </div>
            </div>
        </div>
    );
};

export default DocsCredentials;
