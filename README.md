# Mandrill - Mandrill bounce processing.

**Do not use this yet! Even the read me below is wrong!**

Mandrill is one of many emailing services (e.g. SMTP relays).

However, Mandrill can be configured to send bounce information directly to
CiviCRM using webhooks, enabling the normal CiviMail mailing reports.

This extension provides this functionality.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.0+
* CiviCRM 5.x (tested with 5.13)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl mandrill@https://github.com/artfulrobot/mandrill/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/artfulrobot/mandrill.git
cv en mandrill
```

## Usage

### Step 1: configure your webhooks at Mandrill

Log in to mandrill's website and find the Webhooks page.

Create a webhook and configure it to respond to **hard_bounce** and
**soft_bounce** events.  The webhook URL for your site, will look like:

- Drupal 7: `https://example.com/civicrm/mandrill/webhook`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/webhook`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/webhook`

### Step 2: enter your Mandrill webhook key

Nb. the API key is *not* your Mandrill password (nor your domain's SMTP/API
key/password). You can find it on the Mandrill webhooks page.

Once you've got it, visit the settings page at:

- Drupal 7: `https://example.com/civicrm/mandrill/settings`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/settings`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/settings`

Put it in the box and press Save.

### Step 3: enter your Mandrill SMTP settings

Put these in the usual place:

Administer » System Settings » Outbound Email (SMTP/Sendmail)

Right, you're all set. Do some testing.

## How does this extension differ from the MTE extension?

This extension focusses on simplicity; all it really does is make sure bounces
are handled when using Mandrill. It leaves click and open recording to CiviCRM
(which seems to do a pretty good job).

The [MTE - Mandrill Transactional
Emails](https://github.com/JMAConsulting/biz.jmaconsulting.mte/)  extension
does a whole lot more, but that can cause serious problems if you do many
mailings. Specifically it (optionally) sends transactional mailings through
Mandrill (you get this anyway by just putting your Mandrill SMTP details in);
creates several activities for each recipient each with a full copy of the
email (this caused me disk space issues and MySQL performance issues as the
activities table grew to 50GB!); implements its own system of bounce handling.

If you're looking to store copies of transactional emails (e.g. contribution
receipt emails) then you could try the [transactional
emails](https://civicrm.org/extensions/transactional-emails) extension.

## How this extension works (technical overview)

It uses a hook to copy CiviCRM's VERP header to a header that Mandrill will
store and pass on when it calls webhooks.

The webhook handler then extracts that VERP data and other info from Mandrill to
create hard/soft bounces.

