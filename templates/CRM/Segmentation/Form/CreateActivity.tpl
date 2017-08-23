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

{$form.cid.html}

<div class="help" id="individual_activity_warning" style="display: none;">
  <p>{ts}<strong>Caution!</strong> If you want to create too many individual activities you might run into a timeout, resulting in only some of the activities created.{/ts}</p>
</div>

<div class="crm-section">
  <div class="label">{$form.mass_activity.label}</div>
  <div class="content">{$form.mass_activity.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.activity_type_id.label}</div>
  <div class="content">{$form.activity_type_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.subject.label}</div>
  <div class="content">{$form.subject.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.status_id.label}</div>
  <div class="content">{$form.status_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.campaign_id.label}</div>
  <div class="content">{$form.campaign_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.medium_id.label}</div>
  <div class="content">{$form.medium_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.activity_date_time.label}</div>
  <div class="content">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</div>
  <div class="clear"></div>
</div>

{include file="CRM/common/formButtons.tpl" location="bottom"}

<script type="text/javascript">
var total_count   = {$total_count};
var warning_count = {$warning_count};
{literal}
if (total_count > warning_count) {
  cj("#mass_activity").change(function() {
    if (cj(this).val() == '0') {
      cj("#individual_activity_warning").show(100);
    } else {
      cj("#individual_activity_warning").hide(100);
    }
  });
  cj("#mass_activity").change();
}
{/literal}
</script>
