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
        <li>{ts}Use A/B/Main tests to split a segment into a number of test buckets, and one Main or remainder. Useful, if you want to perform a in situ tests, and use the winner for the main segment.{/ts}</li>
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

  <div class="crm-segmentation-split-type-content" id="crm-segmentation-split-buckets">
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

  <div class="crm-segmentation-split-type-content" id="crm-segmentation-split-test-buckets">
    <p>
      {ts}Split segments into any number of test buckets to perform A/B tests. The last segment ist always the main one.{/ts}
    <ul>
      <li>{ts}You can change the names of the new segments below{/ts}</li>
      <li>{ts}Splits are always performed randomly and all buckets will have the same number of contacts (±1 for uneven totals){/ts}</li>
      <li>{ts}This action cannot be undone once you hit "Split" below{/ts}</li>
    </ul>
    </p>
    <table class="form-layout-compressed">
      <tr>
        <td class="label">{$form.test_percentage.label}</td>
        <td>{$form.test_percentage.html} %</td>
      </tr>
    </table>
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
      {foreach from=$form.test_segment item=segment}
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

  <div class="crm-segmentation-split-type-content" id="crm-segmentation-split-exclusion">
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

  <div class="crm-segmentation-split-type-content" id="crm-segmentation-split-custom">
    <div>
      <p>{ts}In this tab you can split segments by percentage/number of Contacts.{/ts}</p>
      <p>{ts 1=$segmentContactCount}Current segment is included <strong>%1</strong> Contacts.{/ts}</p>
      <p>{ts}Segment names must be unique{/ts}</p>
      <p>{ts 1=$minSplitSegments 2=$maxSplitSegments}This segment can be split minimum on <strong>%1</strong> and maximum on <strong>%2</strong>{/ts} segments.</p>
      <div class="custom-split-mode-block">
        <span>{$form.custom_split_mode.label}</span>
        <span class="custom-split-mode-block-values">{$form.custom_split_mode.html}</span>
      </div>

      <div class="custom-split-table-wrap">
        <table class="custom-split-table">
          <tr>
            <th>#</th>
            <th>{ts}Segment Name{/ts}</th>
            <th class="custom-split-percentage-element">
              <div>{ts}Contact Percentage (%){/ts}</div>
              <div>{$form.segment_percents_errors.html}</div>
            </th>
            <th class="custom-split-number-element">
              <div>{ts}No. of Contacts (#){/ts}</div>
              <div>{$form.segment_number_errors.html}</div>
            </th>
            <th>{ts}Actions{/ts}</th>
          </tr>
          {section name=bar loop=$customSplitSegmentCount step=1}
            <tr class="custom-split-segment-row">
              <td>
                <div class="custom-split-segment-index">
                  {math equation="x + y" x=$smarty.section.bar.index y=1}
                </div>
              </td>
              <td>
                <div class="custom-split-segment-input">
                  {$form.name_of_segment[$smarty.section.bar.index].html}
                </div>
              </td>
              <td class="custom-split-segment-input custom-split-number-element">
                <div>
                  {$form.segment_count_contact_in_number[$smarty.section.bar.index].html}
                </div>
              </td>
              <td class="custom-split-segment-input custom-split-percentage-element">
                <div>
                  {$form.segment_count_contact_in_percents[$smarty.section.bar.index].html}
                </div>
              </td>
              <td>
                <div>
                  <div>
                    <a href="" class="action-item crm-hover-button crm-segmentation-remove-segment" >Remove</a>
                    <div style="display: none;">{$form.is_active_segment[$smarty.section.bar.index].html}</div>
                  </div>
                </div>
              </td>
            </tr>
          {/section}
        </table>
        <div class="crm-segmentation-add-new-segment-wrap">
          <button class="crm-segmentation-add-new-segment crm-button crm-i-button" >
            <i class="crm-segmentation-fa-icon fa fa-plus-circle" aria-hidden="true"></i>
            <span>{ts}Add new segment{/ts}</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

<script type="text/javascript">
{literal}

  CRM.$(document).ready(function() {
    var ts = CRM.ts;
    CRM.$('.crm-segmentation-split-type input[type="radio"]').change(showSplitTypeContent);
    showSplitTypeContent();
    initCustomSplit();
  });

  function showSplitTypeContent() {
    CRM.$('.crm-segmentation-split-type-content').hide();

    switch (CRM.$('input[name="split_type"]:checked').val()) {
      case 'a_b_test':
        CRM.$('#crm-segmentation-split-buckets').show();
        break;
      case 'a_b_main_test':
        CRM.$('#crm-segmentation-split-test-buckets').show();
        break;
      case 'exclusion_test':
        CRM.$('#crm-segmentation-split-exclusion').show();
        break;
      case 'custom':
        CRM.$('#crm-segmentation-split-custom').show();
        break;
    }
  }

  function initCustomSplit() {
    //hide not active segments in table
    CRM.$('#crm-segmentation-split-custom .custom-split-segment-row').each(function() {
      var segmentRow = CRM.$(this);
      var isChecked = segmentRow.find('input[id^="is_active_segment["]').attr('checked') === 'checked';
      if (!isChecked) {
        segmentRow.addClass('crm-segmentation-hidden-segment');
      }
    });

    handleAddSegmentButton();
    handleSplitMode();
    recalculateSegmentsNumber();

    //on change split mode
    CRM.$('#crm-segmentation-split-custom input[name^="custom_split_mode"]').change(function() {
      CRM.$('#crm-segmentation-split-custom input[id^="segment_count_contact_in_number"]').val('');
      CRM.$('#crm-segmentation-split-custom input[id^="segment_count_contact_in_percents"]').val('');
      handleSplitMode();
    });

    //on add segment to table
    CRM.$('#crm-segmentation-split-custom .crm-segmentation-add-new-segment').click(function(event){
      event.preventDefault();
      var segmentRow = CRM.$('.custom-split-segment-row.crm-segmentation-hidden-segment:first');
      var segmentNameInput = segmentRow.find('input[id^="name_of_segment"]');
      segmentRow.removeClass('crm-segmentation-hidden-segment');
      segmentRow.find('input[id^="is_active_segment["]').attr("checked", true);
      segmentNameInput.val(ts('Sample Segment - ') + segmentNameInput.data('alphabet-char'));
      segmentRow.effect("highlight", {}, 3000);
      recalculateSegmentsNumber();
      handleAddSegmentButton();
    });

    //on remove segment from table
    CRM.$('#crm-segmentation-split-custom .crm-segmentation-remove-segment').click(function(event){
      event.preventDefault();
      var segmentRow = CRM.$(this).closest('.custom-split-segment-row');
      segmentRow.addClass('crm-segmentation-hidden-segment');
      segmentRow.find('input[id^="is_active_segment["]').attr("checked", false);
      segmentRow.find('input[id^="segment_count_contact_in_number"]').val('');
      segmentRow.find('input[id^="segment_count_contact_in_percents"]').val('');
      recalculateSegmentsNumber();
      handleAddSegmentButton();
    });
  }

  function handleSplitMode() {
    var splitModeElement = CRM.$('#crm-segmentation-split-custom input[name^="custom_split_mode"]:checked');

    if (splitModeElement.val() === 'percent') {
      CRM.$('#crm-segmentation-split-custom .custom-split-number-element').hide();
      CRM.$('#crm-segmentation-split-custom .custom-split-percentage-element').show();
    }

    if (splitModeElement.val() === 'number') {
      CRM.$('#crm-segmentation-split-custom .custom-split-number-element').show();
      CRM.$('#crm-segmentation-split-custom .custom-split-percentage-element').hide();
    }
  }

  function recalculateSegmentsNumber() {
    var segmentRows = CRM.$('.custom-split-segment-row:not(.crm-segmentation-hidden-segment)');

    var i = 1;
    segmentRows.each(function() {
      CRM.$(this).find('.custom-split-segment-index').empty().html(i);
      i++;
    });
  }

  function handleAddSegmentButton() {
    var addSegmentButton = CRM.$('#crm-segmentation-split-custom .crm-segmentation-add-new-segment');
    if (CRM.$('.custom-split-segment-row.crm-segmentation-hidden-segment').length === 0) {
      addSegmentButton.hide();
    } else {
      addSegmentButton.show();
    }
  }

{/literal}
</script>
