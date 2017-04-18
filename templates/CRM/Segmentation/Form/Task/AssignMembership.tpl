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
  <div class="label">{$form.segment_id.label}</div>
  <div class="content">{$form.segment_id.html}</div>
  <div class="clear"></div>
</div>

{include file="CRM/common/formButtons.tpl" location="bottom"}