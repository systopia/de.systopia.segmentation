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
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&top=$segment_id"}"><img src="http://localhost:8888/gp/sites/all/modules/civicrm/i/arrow/first.gif" title="Move to top" alt="Move to top" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&up=$segment_id"}"><img src="http://localhost:8888/gp/sites/all/modules/civicrm/i/arrow/up.gif" title="Move up one row" alt="Move up one row" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&down=$segment_id"}"><img src="http://localhost:8888/gp/sites/all/modules/civicrm/i/arrow/down.gif" title="Move down one row" alt="Move down one row" class="order-icon"></a>&nbsp;
          <a class="crm-weight-arrow" href="{crmURL p='civicrm/segmentation/start' q="cid=$campaign_id&bottom=$segment_id"}"><img src="http://localhost:8888/gp/sites/all/modules/civicrm/i/arrow/last.gif" title="Move to bottom" alt="Move to bottom" class="order-icon"></a>
      </td>
      <td>
        <span>
          <a target="_blank" href="{crmURL p='civicrm/segmentation/contacts' q="cid=$campaign_id&sid=$segment_id"}" alt="{ts}View in Popup{/ts}" title="{ts}View in Popup{/ts}">
            <div><span class="ui-icon ui-icon-zoomout" title="{ts}View Contact List{/ts}"></span></div>
          </a>
        </span>
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>

<script type="text/javascript">
// reset the URL
window.history.replaceState("", "", "{$baseurl}");
</script>