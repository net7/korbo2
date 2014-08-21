(function (callback) {
  if (typeof define === 'function' && define.amd) {
    define(['core/AbstractWidget'], callback);
  }
  else {
    callback();
  }
}(function () {

(function ($) {

AjaxSolr.CurrentSearchWidget = AjaxSolr.AbstractWidget.extend({
  start: 0,

  afterRequest: function () {
    var self = this;
    var links = [];

    var q = this.manager.store.get('q').val();
    if (q != '*:*') {

        var label = q.substring(q.lastIndexOf('/')+ 1);

        links.push($('<a href="#"></a>').text('[x] ' + label).click(function () {
//        self.manager.store.get('q').val('basket_id_s:'+basket_id);
        self.manager.store.get('q').val('*:*');
        self.doRequest();
        return false;
      }));
    }

    var fq = this.manager.store.values('fq');
    for (var i = 0, l = fq.length; i < l; i++) {
        if (fq[i] != 'basket_id_s:' + basket_id){

            // note that this works even if there aren't any slashes in the string
            var label = fq[i].substring(0, fq[i].length -1).substring(fq[i].lastIndexOf('/')+ 1)
//            links.push($('<a href="#"></a>').text('(x) ' + fq[i]).click(self.removeFacet(fq[i])));
            links.push($('<a href="#" title="'+fq[i].replace('"', ' ')+'"></a>').text('[x] ' + label).click(self.removeFacet(fq[i])));
        }
    }

    if (links.length > 1) {
      links.unshift($('<a href="#"></a>').text('remove all').click(function () {

//          self.manager.store.get('q').val('basket_id_s:'+basket_id);
        self.manager.store.get('q').val('*:*');
        self.manager.store.remove('fq');


        self.manager.store.addByValue('fq', 'basket_id_s:'+basket_id);


          self.doRequest();
        return false;
      }));
    }

    if (links.length) {
      var $target = $(this.target);
      $target.empty();
      for (var i = 0, l = links.length; i < l; i++) {
        $target.append($('<li></li>').append(links[i]));
      }
    }
    else {
      $(this.target).html('<li>Viewing all documents!</li>');
    }
  },

  removeFacet: function (facet) {
    var self = this;
    return function () {
      if (self.manager.store.removeByValue('fq', facet)) {
        self.doRequest();
      }
      return false;
    };
  }
});

})(jQuery);

}));
