# Mandrill bounce processing.

Mandrill is one of many emailing services (e.g. SMTP relays). Once you have an
account you can copy the SMTP settings they provide into CiviCRM's Outbound
Email settings to use it.

However Mandrill handles bounces itself and so for CiviCRM to get hold of this
information we need to configure Mandrill to send that to CiviCRM. This
extension provides that functionality.

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

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git)
repo for this extension and install it with the command-line tool
[cv](https://github.com/civicrm/cv).

```bash
cd /path/to/your/civicrm/extensions/directory
git clone https://github.com/artfulrobot/mandrill.git
cv en mandrill
```

## Usage

### Step 1: configure your webhooks at Mandrill

Log in to Mandrill's website and find the Webhooks page.

Create a webhook and configure it to respond to these events.

- `hard_bounce` (e.g. mailbox does not exist)
- `soft_bounce` (e.g. mailbox full)
- `reject` (this is what Mandrill sends when a previous attempt at mailing that
    email received a hard bounce)
- `spam` (user marks your message as spam - *how dare they!*)

The webhook URL for your site, will look like:

- Drupal 7: `https://example.com/civicrm/mandrill/webhook`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/webhook`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/webhook`

### Step 2: enter your Mandrill webhook key in CiviCRM

Nb. the API key is *not* your Mandrill account password, nor your domain's SMTP/API
key/password. You can find it on the Mandrill webhooks page.

Once you've got it, visit the settings page at:

- Drupal 7: `https://example.com/civicrm/mandrill/settings`
- Wordpress: `https://example.com/?page=CiviCRM&q=civicrm/mandrill/settings`
- Joomla: `https://example.com/index.php?option=com_civicrm&task=civicrm/mandrill/settings`

Put it in the box and press Save.

### Step 3: enter your Mandrill SMTP settings

Put these in the usual place:

*Administer* » *System Settings* » *Outbound Email (SMTP/Sendmail)*

When you save it will send a test message and you should see confirmation of
success on the web page (and you should receive the test email).

Right, you're all set. Do some more testing:

1. On Mandrill's website, on the Webhooks page, there's a "send test" button
   which will send various tests to your webhook. If you've configured things
   correctly this will return a successful message.

2. Then try making a CiviMail/Mosaico mailing to a group with just test email
   accounts in it, e.g. accounts you own. Also good to include an email that
   you know will bounce. Send the mailing in the usual way. Open emails, click
   links. Check that those functions worked and are recorded in CiviCRM in the
   usual way. Check the bounce is registered. Nb. it can take a while for
   Mandrill to notice a bounce (e.g. even when the receiving SMTP server rejects
   the message, Mandrill's "Outbound" page may still show green "Delivered" -
   annoying, when you're not paying peanuts to still get the monkey, but
   eventually, it should turn red and a while after that it should inform
   CiviCRM. This whole thing can take up to an hour in my experience.)

## How does this extension differ from the MTE extension?

This extension focusses on simplicity; all it really does is make sure bounces
are handled when using Mandrill. It leaves click and open recording to CiviCRM
- which seems to do a pretty good job.

The [MTE - Mandrill Transactional
Emails](https://github.com/JMAConsulting/biz.jmaconsulting.mte/) extension
does a whole lot more but can cause serious problems if you do a lot of
mailing.

The MTE extension bundles options around transactional mail along with
integration with Mandrill.

If you want all your mail sent through Mandrill then you don't need the MTE
extension; just put the Mandrill SMTP credentials into CiviCRM (described above)
and all mail will go through Mandrill. If you need to use a different service
for transactional than for bulk (CiviMail) then you will need MTE or other
solution.

If you're looking to store copies of transactional emails (e.g. contribution
receipt emails) then you could try the [transactional
emails](https://civicrm.org/extensions/transactional-emails) extension. (I have
not tested using that extension alongside this one, please let me know if it
works!)

The MTE extension creates several activities for each recipient each with a full
copy of the email.  If you email 10,000 people that's 10,000 'Mandrill Sent'
activities, then if half of them open it 4 times that's another 5,000 × 4 =
20,000 activities. If a quarter of them click 2 links each, that's another 2,500
× 2 = 5,000 activities. Now we're up to 35,000 activities each with a full copy
of the HTML. This caused me disk space issues and MySQL performance issues as
the activities table grew to 50GB!

This extension does not create any activities. Note that core CiviMail creates
one activity per mailing, shared with all the recipient contacts. Because this
extension does less work per email, it should help speed up sending emails.

The MTE extension provides open/click data (in the form of activities) on all
emails, including transactional. The use of this is fairly limited (the
activities don't have accurate times and the clicked links don't tell you which
link was clicked), but if you need to have open/click information on
transactional mail, it's there for you.

For all the above reasons I decided it would be better to write a new extension,
so this was born.

### Moving from MTE to this extension?

If you don't need the functionality of MTE outlined above and just want to use
Mandrill for delivering all your email, it should be safe to disable the MTE
extension and then install this one. This extension will accept webhook data
that MTE accepted, so existing bulk mailings' bounces should still be processed.

## How this extension works (technical overview)

It uses [`hook_civicrm_alterMailParams`](https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMailParams/)
to copy information from CiviCRM's VERP header to a special `X-MC-Metadata`
header that Mandrill will remove from the email but store and pass on when it
calls webhooks.

The webhook handler then extracts that data and other info from Mandrill to
create hard/soft bounces in the normal CiviMail way.

