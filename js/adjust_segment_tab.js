/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+-------------------------------------------------------*/

var membership_details = MEMBERSHIP_DETAILS;
var campaign_details   = CAMPAIGN_DETAILS;
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

// replace campaign / membership labels
panel.find("td[class^='crmf-custom_91']").each(function() {
  cj(this).html(campaign_details[cj(this).html()]);
});

panel.find("td[class^='crmf-custom_94']").each(function() {
  cj(this).html(membership_details[cj(this).html()]);
});