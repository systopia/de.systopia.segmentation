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

<div class="crm-section">
  <div class="label">{$form.campaign_id.label}</div>
  <div class="content">{$form.campaign_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.segment_list.label}</div>
  <div class="content">{$form.segment_list.html}</div>
  <div class="clear"></div>
</div>

{include file="CRM/common/formButtons.tpl" location="bottom"}

{literal}
<script type="text/javascript">

/*******************************
 *   campaign changed handler  *
 ******************************/
cj("#campaign_id").change(function() {
  // rebuild segment list:
  // first remove all
  cj("#segment_list option").remove();
  cj("#segment_list").append('<option value="">all</option>');

  // then: look up the specific ones and add
  CRM.api3('Segmentation', 'segmentlist', {
    "campaign_id": cj("#campaign_id").val(),
  }).done(function(result) {
    for (var segment_id in result.values) {
      cj("#segment_list").append('<option value="' + segment_id + '">' + result.values[segment_id] + '</option>');
    }
  });
});

// fire off event once
cj("#campaign_id").change();
{/literal}
</script>