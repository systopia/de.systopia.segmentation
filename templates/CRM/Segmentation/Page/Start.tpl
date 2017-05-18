{*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

<div>
  <p>
    {ts}Starting the campaign will do the following:{/ts}
    <ol>
      <li>{ts}Set the status to 'In Progress'{/ts}</li>
      <li>{ts}If the campaign's start date is in the past, it will be set to <code>now</code>{/ts}</li>
      <li>{ts}Freeze the contact segments, i.e. each contact is only linked with the highest segment as of the table below.{/ts}</li>
    </ol>
  </p>
  <p>{ts}You can use the arrows below to re-order the segments. Note, that the counts will change if some contacts are in multiple segments. They will only be counted in the highest segment, so the numbers reflect the situation after starting the project{/ts}</p>
  <p><b>{ts 1=$total_count}There is a total of %1 contacts connected to this campaign.{/ts}</b></p>
</div>

<table id="options" class="row-highlight">
  <thead>
      <tr>
          <th>{ts}Segment Name{/ts}</th>
          <th>{ts}Contact Count{/ts}</th>
          <th>{ts}Segment Order{/ts}</th>
          <th></th>
      </tr>
  </thead>
  <tbody>
  {foreach from=$segment_order item=segment_id}
    <tr id="segment-{$segment_id}" class="crm-entity even-row">
      <td class="crm-admin-options-label" data-field="label">
        <div class="" title="{$segment_titles.$segment_id}">{$segment_titles.$segment_id}</div>
      </td>
      <td class="crm-admin-options-value">{$segment_counts.$segment_id}</td>
      <td class="nowrap crm-admin-options-order">
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&top=$segment_id"}"><img src="{$config->resourceBase}i/arrow/first.gif" title="Move to top" alt="Move to top" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&up=$segment_id"}"><img src="{$config->resourceBase}i/arrow/up.gif" title="Move up one row" alt="Move up one row" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&down=$segment_id"}"><img src="{$config->resourceBase}i/arrow/down.gif" title="Move down one row" alt="Move down one row" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&bottom=$segment_id"}"><img src="{$config->resourceBase}i/arrow/last.gif" title="Move to bottom" alt="Move to bottom" class="order-icon"></a>
      </td>
      <td>
        <span>
          <a name="contact_list" href="{crmURL p='civicrm/segmentation/contacts' q="snippet=1&cid=$campaign_id&sid=$segment_id"}" alt="{ts}View in Popup{/ts}" title="{ts}View in Popup{/ts}">
            <div><span class="ui-icon ui-icon-zoomout" title="{ts}View Contact List{/ts}"></span></div>
          </a>
        </span>
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>

<div>
  <a href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&start=now"}" class="button"><span>{ts}Start Campaign{/ts}</span></a>
</div>




<script type="text/javascript">
// reset the URL
window.history.replaceState("", "", "{$baseurl}");

{literal}
// popup function
cj("a[name=contact_list]").click(function() {
  var url = cj(this).attr('href');
  window.open(url, null, "height=400,width=400,status=no,toolbar=no,menubar=no,location=no,scrollbars=yes");
  return false; // stop processing event
});
{/literal}
</script>