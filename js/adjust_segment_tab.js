/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+-------------------------------------------------------*/

var segments_details    = SEGMENTS_DETAILS;
var segments_field_id   = "SEGMENTS_FIELD_ID";
var membership_details  = MEMBERSHIP_DETAILS;
var membership_field_id = "MEMBERSHIP_FIELD_ID";
var campaign_details    = CAMPAIGN_DETAILS;
var campaign_status     = CAMPAIGN_STATUS;
var campaign_field_id   = "CAMPAIGN_FIELD_ID";
var table_wrapper = cj("#custom-SEGMENT_GROUP_ID-table-wrapper");
var panel = table_wrapper.parent();

// remove the "Add Segments Record" button
panel.find("a.button[accesskey='N']")
     .hide();

// blank the 6th column (actions) completely
panel.find("td:nth-child(6)").html('');

// replace segment / campaign / membership labels
panel.find("td[class^='crmf-custom_" + segments_field_id + "']").each(function() {
  cj(this).html(segments_details[cj(this).html()]);
});

panel.find("td[class^='crmf-custom_" + campaign_field_id + "']").each(function() {
  if (campaign_status[cj(this).html()] == '1') {
    cj(this).closest("tr").addClass("segmentation-planned");
  }
  cj(this).html(campaign_details[cj(this).html()]);
});

panel.find("td[class^='crmf-custom_" + membership_field_id + "']").each(function() {
  cj(this).html(membership_details[cj(this).html()]);
});

// sort table by first column (date)
var dt_hooked = false;
cj(table_wrapper).bind("DOMSubtreeModified", function(){
  if (dt_hooked) return;
  var dt = table_wrapper.find("table.dataTable");
  if (dt.length) {
    // there, the datatable is finally here -> add event hook
    cj(dt).on("init.dt", function(e, myDT) {
      table_wrapper.find("table.dataTable").dataTable().fnSort([[0,'desc']]);
    });
    dt_hooked = true;
  }
});
