{*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2018 SYSTOPIA                            |
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
<div class="crm-block crm-form-block">
  <div id="crm-segmentation-split-generic">
    <p>
      {ts}You can split segments to perform A/B or exclusion tests.{/ts}
      <ol>
        <li>{ts}Use A/B tests to split a segment into two or more buckets. Useful if you want to test the performance of different copy text variants, etc.{/ts}</li>
        <li>{ts}Use exclusion tests to remove a subset of contacts from a segment. Test how contacts who receive a mailing perform compared to those who do not.{/ts}</li>
      </ol>
    </p>
  </div>

  <div class="crm-section">
    <div class="label">{$form.split_type.label}</div>
    <div class="content crm-segmentation-split-type">{$form.split_type.html}</div>
    <div class="clear"></div>
  </div>

  {$form.cid.html}
  {$form.sid.html}

  <div id="crm-segmentation-split-buckets">
    <p>
      {ts}Split segments into any number of buckets to perform A/B tests. A segment with 10,000 contacts and two buckets would be split into two segments with 5,000 contacts each.{/ts}
      <ul>
        <li>{ts}You can change the names of the new segments below{/ts}</li>
        <li>{ts}Splits are always performed randomly and all buckets will have the same number of contacts (±1 for uneven totals){/ts}</li>
        <li>{ts}This action cannot be undone once you hit "Split" below{/ts}</li>
      </ul>
    </p>
    <table>
      <thead class="sticky">
      <tr>
        <th>
          #
        </th>
        <th>
          {ts}Segment Name{/ts}
        </th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$form.segment item=segment}
        <tr class="{cycle values="odd-row, even-row"}">
          <td>
            {counter}.
          </td>
          <td>
            {$segment.html}
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>

  <div id="crm-segmentation-split-exclusion">
    <p>
      {ts}Contacts in an excluded segment will not be assigned to the campaign or be included in exports.{/ts}
      <ul>
        <li>{ts}You can enter an total number of contacts that should be excluded, or a percentage of contacts in the segment{/ts}</li>
        <li>{ts}If you enter values in both fields, the total will be the upper limit of contacts to be added to the exclusion test (in case the percentage result is higher){/ts}</li>
        <li>{ts}This action cannot be undone once you hit "Split" below{/ts}</li>
      </ul>
    </p>
    <table class="form-layout-compressed">
      <tr>
        <td class="label">{$form.exclude_contacts_total.label}</td>
        <td>{$form.exclude_contacts_total.html}</td>
      </tr>
      <tr>
        <td class="label">{$form.exclude_contacts_percentage.label}</td>
        <td>{$form.exclude_contacts_percentage.html} %</td>
      </tr>
    </table>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

<style type="text/css">
{literal}
  .crm-segmentation-split-type label {
    padding-right: 5em;
  }
  #crm-segmentation-split-buckets, #crm-segmentation-split-exclusion {
    display: none;
  }
{/literal}
</style>

<script type="text/javascript">
{literal}
  function showSplitType() {
    if (CRM.$('input[name="split_type"]:checked').val() == '0') {
      CRM.$('#crm-segmentation-split-buckets').show();
      CRM.$('#crm-segmentation-split-exclusion').hide();
    } else {
      CRM.$('#crm-segmentation-split-exclusion').show();
      CRM.$('#crm-segmentation-split-buckets').hide();
    }
  }
  CRM.$('.crm-segmentation-split-type input[type="radio"]').change(showSplitType);
  CRM.$(document).ready(showSplitType);
{/literal}
</script>