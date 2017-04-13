/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+-------------------------------------------------------*/

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