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

<div id="crm-container" class="crm-container">
<div id="crm-main-content-wrapper">
  <h2>{ts 1=$campaign.title}Campaign '%1'{/ts}</h2>
  {if $segment_name}
    <h3>{ts 1=$contact_count 2=$segment_name}Contact List (%1) - Segment '%2'{/ts}</h3>
  {else}
    <h3>{ts 1=$contact_count}Contact List (%1){/ts}</h3>
  {/if}

  <table>
    {foreach from=$contacts item=contact}
    <tr>
      <td>
        <a href="{$contact_base_url}{$contact.contact_id}" target="_blank" title="{$contact.display_name}">
          <span class="icon crm-icon {$contact.contact_type}-icon"></span>
          {$contact.display_name}
        </a>
      </td>
    </tr>
    {/foreach}
  </table>
</div>
</div>
