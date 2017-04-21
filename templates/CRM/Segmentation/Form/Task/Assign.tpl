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

<div class="crm-section">
  <div class="label">{$form.segment.label}</div>
  <div class="content">{$form.segment.html}</div>
  <div class="clear"></div>
</div>

{include file="CRM/common/formButtons.tpl" location="bottom"}

<script type="text/javascript">
var generic_segments = {$generic_segments};
{literal}

/*******************************
 *   campaign changed handler  *
 ******************************/
cj("#campaign_id").change(function() {
  // rebuild segment list

  // first: remove all
  cj("#segment_list option").remove();
  cj("#segment").val('');

  // then: add default ones
  for (var i = 0; i < generic_segments.length; i++) {
    cj("#segment_list").append('<option value="' + generic_segments[i] + '">' + generic_segments[i] + '(generic) </option>');
  }

  // then: look up the specific ones and add
  CRM.api3('Segmentation', 'segmentlist', {
    "campaign_id": cj("#campaign_id").val(),
  }).done(function(result) {
    for (var i = 0; i < result.values.length; i++) {
      cj("#segment_list").append('<option value="' + result.values[i] + '">' + result.values[i] + '</option>');
    }
  });
});

/*******************************
 *    segemnt list handler     *
 ******************************/
cj("#segment_list").change(function() {
  cj("#segment").val(cj("#segment_list").val());
});

// fire off event once
cj("#campaign_id").change();
{/literal}
</script>