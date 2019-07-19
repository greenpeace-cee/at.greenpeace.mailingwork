<div id="help" class="description section-hidden-border">
  <p>
    This is a listing of all known folders seen during synchronization with Mailingwork. Change the campaign field to
    change campaign assignments for activities created when synchronizing mailings in a folder, or leave it empty to use
    {if isset($default_campaign)}
      either the default campaign "{$default_campaign|escape}" or
    {/if}
    the campaign of the parent folder (if set).
  </p>
  <p>
    Note: Mailings may override the campaign setting by using [CIVICAMPAIGN:XXXX] (where XXXX is the campaign ID) in
    their description.
  </p>
</div>

<table cellpadding="0" cellspacing="0" border="0">
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Identifier{/ts}</th>
    <th>{ts}Campaign{/ts}</th>
  </tr>
  {foreach from=$rows item=row}
    <tr data-entity="MailingworkFolder" data-id="{$row.id|escape}" class="crm-entity {cycle values="odd-row,even-row"}">
      <td style="padding-left: {$row.depth*25}px">{$row.name|escape}</td>
      <td>{$row.mailingwork_identifier|escape}</td>
      <td class="crm-editable" data-field="campaign_id" data-type="select"
          data-empty-option="{ts}- none -{/ts}">{$row.campaign_id|escape}</td>
    </tr>
  {/foreach}
</table>
