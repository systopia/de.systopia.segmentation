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
  {if $download_name}
  <p>{ts 1=$download_name 2=$file_size}Your export '%1' (%2) is ready for download.{/ts}</p>
  {else}
  <p>{ts 1=$file_size}Your export (%1) is ready for download.{/ts}</p>
  {/if}
</div>

<div class="action-link">
  <a href="{$download_link}" class="button">
    <span>
      <div class="icon ui-icon-script"></div>{ts}Download{/ts}
    </span>
  </a>
  <a href="{$back_link}" class="button">
    <span>
      <div class="icon ui-icon-check"></div>{ts}Back to Campaign{/ts}
    </span>
  </a>
</div>

