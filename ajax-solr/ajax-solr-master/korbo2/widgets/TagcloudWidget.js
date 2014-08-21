(function (callback) {
  if (typeof define === 'function' && define.amd) {
    define(['core/AbstractFacetWidget'], callback);
  }
  else {
    callback();
  }
}(function () {

(function ($) {

AjaxSolr.TagcloudWidget = AjaxSolr.AbstractFacetWidget.extend({
  afterRequest: function () {
    if (this.manager.response.facet_counts.facet_fields[this.field] === undefined) {
      $(this.target).html('no items found in current selection');
      return;
    }

    var maxCount = 0;
    var objectedItems = [];
    for (var facet in this.manager.response.facet_counts.facet_fields[this.field]) {
      var count = parseInt(this.manager.response.facet_counts.facet_fields[this.field][facet]);
      if (count > maxCount) {
        maxCount = count;
      }
      objectedItems.push({ facet: facet, count: count });
    }
    objectedItems.sort(function (a, b) {
//      return a.facet < b.facet ? -1 : 1;
      return a.count > b.count? -1 : 1;
    });

    $(this.target).empty();

    for (var i = 0, l = objectedItems.length; i < l; i++) {
      var facet = objectedItems[i].facet;
      var count = objectedItems[i].count;
      if (facet != ''){

        // note that this works even if there aren't any slashes in the string
        var label = facet.substring(facet.lastIndexOf('/')+ 1);

        $(this.target).append(
          $('<a href="#" class="tagcloud_item"></a>')
//            .text(facet + ' (' + count + ')')
            .text(label + ' (' + count + ')')
       //        .addClass('tagcloud_size_' + parseInt(objectedItems[i].count / maxCount * 10))
            .click(this.clickHandler(facet))
        );
      }
    }
  }
});

})(jQuery);

}));
