{* HEADER *}

<p>Documentation for this extension is available in the  <a href="https://github.com/artfulrobot/mandrill/blob/master/README.md">README file</a></p>

<h2>Webhook URL</h2>

<p>Your webhook URL (needed when defining a webhook on Mandrill's website) is:</p>
<div>
  <input value="{crmURL p='civicrm/mandrill/webhook' a=TRUE be=TRUE fe=FALSE}" size=80 id="mandrill-url" readonly />
</div>

<h2>Webhook Key</h2>
<p>Copy the Webhook key that Mandrill provides into the input below (then press Save):</p>
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
<script>
{literal}
CRM.$(function() {
  var $webhookInput = CRM.$('#mandrill-url');
  $webhookInput.after(
    CRM.$('<button class="btn btn-secondary small" href >Copy</button>')
    .on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $webhookInput[0].select();
      document.execCommand("copy");
      CRM.alert('Webhook link copied to clipboard', 'Copied', 'success');
    })
  );
});
{/literal}
</script>
