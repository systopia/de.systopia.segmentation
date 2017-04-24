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
var campaign_field_id   = "CAMPAIGN_FIELD_ID";
var panel = cj("#custom-SEGMENT_GROUP_ID-table-wrapper").parent();

// remove the "Add Segments Record" button
panel.find("a.button[accesskey='N']")
     .hide();

// remove all options but Delete
panel.find("a.action-item")
     .filter(":not(.delete-custom-row)")
     .remove();

// move delete button up
// var bay = panel.nearest('td').first('span');
panel.find("a.action-item")
     .filter(".delete-custom-row")
     .each(function () {
        var bay = cj(this).closest('td');
        cj(this).appendTo(bay);
        bay.find('span').remove();
     });

// replace segment / campaign / membership labels
panel.find("td[class^='crmf-custom_" + segments_field_id + "']").each(function() {
  cj(this).html(segments_details[cj(this).html()]);
});

panel.find("td[class^='crmf-custom_" + campaign_field_id + "']").each(function() {
  cj(this).html(campaign_details[cj(this).html()]);
});

panel.find("td[class^='crmf-custom_" + membership_field_id + "']").each(function() {
  cj(this).html(membership_details[cj(this).html()]);
});