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
  <div class="content">
    {$form.segment_list.html}
    <a href="{$segments_url}" target="_blank" id="crm-segments-configure" class="crm-hover-button show-add"><span class="icon ui-icon-wrench"></span></a>
  </div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.segment.label}</div>
  <div class="content">{$form.segment.html}</div>
  <div class="clear"></div>
</div>

<h3>{ts}Membership Selector{/ts}</h3>

<div class="help" id="count_preview"></div>

<div class="crm-section">
  <div class="label">{$form.membership_type_id.label}</div>
  <div class="content">{$form.membership_type_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.membership_status_id.label}</div>
  <div class="content">{$form.membership_status_id.html}</div>
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


/********************************
 * re-calculate sample handler  *
 ********************************/
{/literal}
var contact_sample          = {$contact_sample};
var contact_sample_complete = {$contact_sample_complete};
var contact_sample_factor   = {$contact_sample_factor};
var contact_count           = {$contact_count};
{literal}

/**
 * queries the predicted amount of memberships matching the
 * selected status_id and membership_type_id, and
 * updates the UI
 */
function recalculate_stats() {
  cj("#count_preview").hide(300);
  // compile query
  var query = {"contact_id": {"IN":contact_sample}};

  // add membership_type clause
  var types = cj("#membership_type_id").val();
  if (types && types.length > 0) {
    query["membership_type_id"] = {"IN":types};
  }

  // add status clause
  var statuses = cj("#membership_status_id").val();
  if (statuses && statuses.length > 0) {
    query["status_id"] = {"IN":statuses};
  }

  CRM.api3('Membership', 'getcount', query).done(function(result) {
    var sample_count = result.result;
    var qualifier = '';
    var suffix = '.';
    if (!contact_sample_complete) {
      qualifier = '~';
      var suffix = ', based on a sample of ' + contact_sample.length + '.';
      sample_count = Math.round(sample_count * contact_sample_factor);
    }
    cj("#count_preview").html("<p>Your current selection will assign <b>" + qualifier + sample_count + "</b> memberships" + suffix + "</p>");
    cj("#count_preview").show(300);
  });
}

// register events and trigger once
cj("#membership_type_id").change(recalculate_stats);
cj("#membership_status_id").change(recalculate_stats);
recalculate_stats();

{/literal}
</script>