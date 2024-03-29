<div id="help" class="description section-hidden-border">
  <p>
    This is a listing of all known mailings seen during synchronization with Mailingwork. You can change the
    synchronization status or date to trigger resynchronization during the next job execution.
  </p>
  <dl>
    <dt>Sync Date</dt>
    <dd>Date until which items have been processed. Only items created after this date will be synchronized.</dd>
    <dt>Sync Status</dt>
    <dd>
      Current status of the synchronization of this mailing.
      <ul>
        <li>
          <strong>Pending:</strong> Synchronization of this mailing is pending and will start with the next
          synchronization or once recipients are available
        </li>
        <li>
          <strong>In Progress:</strong> Synchronization has started and will continue with the next synchronization.<br>
        </li>
        <li>
          <strong>Completed:</strong> Synchronization has finished and no new items will be processed
        </li>
        <li>
          <strong>Retrying:</strong> The last synchronization attempt caused an error; synchronization will be retried.<br>
        </li>
        <li>
          <strong>Failed:</strong> Synchronization encountered an error for two consecutive attempts and will no longer be attempted.<br>
        </li>
      </ul>
    </dd>
    <dt>Campaign</dt>
    <dd>
      This is the campaign that will be used for any future activities created by imports. The campaign is determined
      by the folder in which the mailing is located. The campaign of parent folders is used if no campaign is
      set for the mailing folder.
      <br>
      {if isset($default_campaign)}
        If no campaign is set for any parent folders, the <strong>default campaign "{$default_campaign|escape}"</strong> is used.
      {/if}
      <br>
      Campaigns may be overwritten for individual mailings by adding this code to their description in Mailingwork:
      <code>[CiviCampaign=Campaign_ID]</code>, i.e. <code>[CiviCampaign=1234]</code>
      <br>
      <strong>Note:</strong> Campaigns associated with a mailing may change over time (for example if the folder or description is
      edited), so activities that have already been created might be associated with different campaigns than the ones
      shown on this page.
    </dd>
  </dl>
</div>

<table cellpadding="0" cellspacing="0" border="0">
  <tr class="columnheader">
    <th>{ts}ID{/ts}</th>
    <th>{ts}Mailingwork Identifier{/ts}</th>
    <th>{ts}Date{/ts}</th>
    <th>{ts}Type{/ts}</th>
    <th>{ts}Mailing Status{/ts}</th>
    <th>{ts}Subject{/ts}</th>
    <th>{ts}Folder{/ts}</th>
    <th>{ts}Campaign{/ts}</th>
    <th>{ts}Recipient Sync Date{/ts}</th>
    <th>{ts}Recipient Sync Status{/ts}</th>
    <th>{ts}Opening Sync Date{/ts}</th>
    <th>{ts}Opening Sync Status{/ts}</th>
    <th>{ts}Click Sync Date{/ts}</th>
    <th>{ts}Click Sync Status{/ts}</th>
    <th>{ts}Bounce Sync Date{/ts}</th>
    <th>{ts}Bounce Sync Status{/ts}</th>
  </tr>
  {foreach from=$rows item=row}
    <tr data-entity="MailingworkMailing" data-id="{$row.id|escape}"
        class="crm-entity {cycle values="odd-row,even-row"}">
      <td>{$row.id|escape}</td>
      <td>{$row.mailingwork_identifier|escape}</td>
      <td>{$row.sending_date|escape}</td>
      <td>{$row.type_id|escape}</td>
      <td>{$row.status_id|escape}</td>
      <td title="{$row.description|escape}">{$row.subject|escape}</td>
      <td>{$row.mailingwork_folder_id|escape}</td>
      <td>{$row.campaign_title|escape}</td>
      <td class="crm-editable" data-field="recipient_sync_date">{$row.recipient_sync_date|escape}</td>
      <td class="crm-editable" data-field="recipient_sync_status_id"
          data-type="select">{$row.recipient_sync_status_id|escape}</td>
      <td class="crm-editable" data-field="opening_sync_date">{$row.opening_sync_date|escape}</td>
      <td class="crm-editable" data-field="opening_sync_status_id"
          data-type="select">{$row.opening_sync_status_id|escape}</td>
      <td class="crm-editable" data-field="click_sync_date">{$row.click_sync_date|escape}</td>
      <td class="crm-editable" data-field="click_sync_status_id"
          data-type="select">{$row.click_sync_status_id|escape}</td>
      <td class="crm-editable" data-field="bounce_sync_date">{$row.bounce_sync_date|escape}</td>
      <td class="crm-editable" data-field="bounce_sync_status_id"
          data-type="select">{$row.bounce_sync_status_id|escape}</td>
    </tr>
  {/foreach}
</table>
