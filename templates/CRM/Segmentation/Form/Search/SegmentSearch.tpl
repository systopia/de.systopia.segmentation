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

{include file="CRM/Contact/Form/Search/Custom.tpl"}

{literal}
<script type="text/javascript">

/*******************************
 *   campaign changed handler  *
 ******************************/
cj("#campaign_id").change(function() {
  var campaign_id = cj("#campaign_id").val();
  if (!campaign_id.length) {
    return;
  }

  // reload segment list:
  var current_value = cj("#segment_id").val();

  // reset list
  cj("#segment_list option").remove();
  // cj("#segment_list").append('<option value="0">any</option>');

  // then: look up the specific ones and add
  CRM.api3('Segmentation', 'segmentlist', {
    "campaign_id": campaign_id,
  }).done(function(result) {
    for (var segment_id in result.values) {
      cj("#segment_list").append('<option value="' + segment_id + '">' + result.values[segment_id] + '</option>');
    }
    cj('#segment_list').select2('val', current_value.split(","));
    cj("#segment_id").val(current_value);
  });
});

// copy changes to (shadow) data field
cj("#segment_list").change(function() {
  cj("#segment_id").val(cj("#segment_list").val());
});

// fire off event once
cj("#segment_id").parent().parent().hide();
cj("#campaign_id").change();
</script>
{/literal}
