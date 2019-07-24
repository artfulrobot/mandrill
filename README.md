# Mandrill - Mandrill bounce processing.

**Do not use this yet! Even the read me below is wrong!**

Mandrill is one of many emailing services (e.g. SMTP relays). While many services
offer to send all your bounced email to a particular email address (e.g. so
CiviCRM can process bounces), Mandrill does not.

However, Mandrill can be configured to send bounce information directly to
CiviCRM using webhooks, enabling the normal CiviMail mailing reports.

This extension provides this functionality.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.0+
* CiviCRM 5.x (tested with 5.8.1)

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

For **Permanent Failure** and **Temporary Failure** events, enter the webhook
URL for your site, which will look like:

- Drupal 7: `https://example.com/civicrm/mandrill/webhook`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/webhook`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/webhook`

### Step 2: enter your Mandrill API key

Nb. the API key is *not* your Mandrill password (nor your domain's SMTP
password). You can find it on the page for your domain on the Mandrill admin
interface.

Once you've got it, visit the settings page at:

- Drupal 7: `https://example.com/civicrm/mandrill/settings`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/settings`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/settings`

Put it in the box and press Save.

Right, you're all set.


## Hey what's with the name?

Gunny is a strong coarse material. Such that you might make sacks out of. Like
post/mail sacks. And this is about mailings. So Mandrill. And, what we're
**not** interested in at all is guns, so it's a deliberate subversion of
Mandrill's name. After all, who wants to be shot by email?
