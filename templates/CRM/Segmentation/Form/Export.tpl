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

{$form.campaign_id.html}

<div class="crm-section">
  <div class="label">{$form.exporter_id.label}</div>
  <div class="content">
    {$form.exporter_id.html}
    <!--a href="{$segments_url}" target="_blank" id="crm-segments-configure" class="crm-hover-button show-add"><span class="icon ui-icon-wrench"></span></a-->
  </div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.segments.label}</div>
  <div class="content">{$form.segments.html}</div>
  <div class="clear"></div>
</div>



{include file="CRM/common/formButtons.tpl" location="bottom"}