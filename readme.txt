=== Captain Funnel for WhatsApp ===
Contributors: devangvachheta
Tags: whatsapp, automation, notifications, funnel, forms
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate WhatsApp customer journeys — order notifications, form submissions, funnel automation, review requests, and coupon campaigns.

== Description ==

Captain Funnel for WhatsApp lets you build complete WhatsApp customer journeys — from order notifications to multi-step follow-up funnels, review requests, and coupon campaigns.

**Key Features:**

* WhatsApp Cloud API integration (official Meta API — no third-party gateway required)
* Automated order status notifications (Pending, Processing, Completed, Cancelled, Refunded, etc.)
* Funnel builder — create multi-step WhatsApp sequences with custom delays (hours, days, or weeks)
* Review request automation (links to any platform)
* Coupon campaign messaging
* Product recommendation messaging
* Message template builder with dynamic variables
* Analytics dashboard — sent, failed, and pending message counts
* Full message log with trigger reference
* WP-Cron powered scheduled messages
* Translation ready

**Supported Integrations:**

* WooCommerce — order status triggers
* Easy Digital Downloads — purchase triggers
* LearnDash — course enrollment and completion triggers
* LifterLMS — course enrollment triggers
* TutorLMS — course enrollment triggers
* MemberPress — membership triggers
* Paid Memberships Pro — membership triggers
* Restrict Content Pro — membership triggers
* User Registration — new user triggers
* Amelia — appointment booking triggers
* Bookly — appointment booking triggers
* MotoPress Hotel Booking — booking triggers
* Contact Form 7 — form submission triggers
* WPForms — form submission triggers
* Gravity Forms — form submission triggers
* Fluent Forms — form submission triggers
* Elementor Forms (Pro) — form submission triggers
* Custom Webhook — trigger from any external source

**Supported Template Variables:**

`{customer_name}` `{order_number}` `{order_total}` `{order_status}` `{customer_phone}` `{store_name}` `{coupon_code}` `{tracking_number}` `{delivery_date}`

== Privacy Policy ==

This plugin sends data to the WhatsApp Cloud API (Meta Platforms, Inc.) when a configured trigger fires. The data sent includes the recipient's phone number and message content derived from the triggering event (e.g. order details or form fields). No data is collected or stored by the plugin author. Please refer to Meta's privacy policy: https://www.whatsapp.com/legal/privacy-policy

== Installation ==

1. Upload the `captain-funnel-for-whatsapp` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. WooCommerce is optional — install it only if you want WooCommerce order-status triggers; every other integration (forms, LMS, booking, membership, custom) works without it.
4. Go to **WA Funnel → Settings** and enter your WhatsApp Cloud API credentials.
5. Configure message templates under **WA Funnel → Templates**.
6. Create funnels under **WA Funnel → Funnels**.

== Frequently Asked Questions ==

= Do I need a WhatsApp Business account? =
Yes. You need a Meta Business account with the WhatsApp Cloud API enabled to use this plugin.

= Which WhatsApp API does the plugin use? =
The plugin uses the official WhatsApp Cloud API provided by Meta (graph.facebook.com).

= Can I create multi-step follow-up sequences? =
Yes. Use the Funnel Builder to create sequences with multiple steps, each with its own delay (hours, days, or weeks) and message template.

= Is the plugin translation ready? =
Yes. A `.pot` file is included in the `languages/` folder.

== Screenshots ==

1. Dashboard — message statistics overview
2. WhatsApp Settings — API credentials configuration
3. Message Templates — per-status template editor
4. Funnels — funnel list and builder
5. Logs — full message activity log

== External services ==

This plugin connects to the WhatsApp Cloud API to send automated WhatsApp messages — order status updates, funnel sequences, review requests, coupon campaigns, and product recommendations.

It sends the recipient's phone number along with relevant message content (e.g. order ID, order total, order status, store name, or form submission details) every time a configured trigger fires (an order status change, a form submission, or another enabled integration event) and a matching message template is active. No data is sent if no template is configured for that trigger.

This service is provided by "Meta Platforms, Inc.": [terms of use](https://www.whatsapp.com/legal/terms-of-service), [privacy policy](https://www.whatsapp.com/legal/privacy-policy).

== Changelog ==

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 0.0.1 =
Initial release.
