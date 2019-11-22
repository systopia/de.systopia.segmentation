cj(document).ready(function () {

  initDragAndDrop();

  function initDragAndDrop() {
    var segmentsTableWrap = cj("#segmentsTableWrap");
    if (segmentsTableWrap.length !== 1) {
      console.warn("Can't init drag and drop.");
      return;
    }

    var campaignId = segmentsTableWrap.data('campaign-id');
    var sortable = segmentsTableWrap.sortable({
      placeholder: "ui-state-highlight",
      handle: '.sort-segments-btn',
      forceHelperSize: true,
      items: '.sorting-init',
      update: function (event, ui) {
        var newOrder = [];

        sortable.find('tr').each(function () {
          var id = cj(this).data('segmentation-id');
          if (id) newOrder.push(parseInt(id, 10));
        });

        CRM.api3('Segmentation', 'sort', {
          "sequential": 1,
          "campaign_id": campaignId,
          "new_order_of_segments": newOrder
        }).done(function (result) {
          if (result.is_error == 1) {
            alert('The following error occured: ' + result.error_message);
            window.location.reload();
          } else if(result.values !== undefined) {
            for (var i = 0; i < result.values.length; i++) {
              cj("#segmentsTableWrap tr[data-segmentation-id='" + result.values[i].segment_id +  "'] .segmentation-count")
                .empty()
                .html(result.values[i].segment_count);
            }
          }
        }).fail(function (result) {
          console.log('fail: ' + result);
        });
      }
    });
  }

});
