(function (callback) {
  if (typeof define === 'function' && define.amd) {
    define(['core/AbstractWidget'], callback);
  }
  else {
    callback();
  }
}(function () {

(function ($) {

AjaxSolr.ResultWidget = AjaxSolr.AbstractWidget.extend({
  start: 0,

  beforeRequest: function () {
    $(this.target).html($('<img>').attr('src', 'images/ajax-loader.gif'));
  },

  facetLinks: function (facet_field, facet_values) {
    var links = [];
    if (facet_values) {
      for (var i = 0, l = facet_values.length; i < l; i++) {
        if (facet_values[i] !== undefined) {
          links.push(
            $('<a href="#"></a>')
            .text(facet_values[i])
            .click(this.facetHandler(facet_field, facet_values[i]))
          );
        }
        else {
          links.push('no items found in current selection');
        }
      }
    }
    return links;
  },

  facetHandler: function (facet_field, facet_value) {
    var self = this;
    return function () {
      self.manager.store.remove('fq');
      self.manager.store.addByValue('fq', facet_field + ':' + AjaxSolr.Parameter.escapeValue(facet_value));
      self.doRequest();
      return false;
    };
  },

  afterRequest: function () {
    $(this.target).empty();
    for (var i = 0, l = this.manager.response.response.docs.length; i < l; i++) {
      var doc = this.manager.response.response.docs[i];
      $(this.target).append(this.template(doc));

      var items = [];
// questi vanno nei risultati
//      items = items.concat(this.facetLinks('topics', doc.topics));
//      items = items.concat(this.facetLinks('label_ss', doc.label_ss));
//      items = items.concat(this.facetLinks('organisations', doc.organisations));
//      items = items.concat(this.facetLinks('exchanges', doc.exchanges));

      var $links = $('#links_' + doc.id);
      $links.empty();
      for (var j = 0, m = items.length; j < m; j++) {
        $links.append($('<li></li>').append(items[j]));
      }
    }
  },

  template: function (doc) {
    var snippet = '';

//      snippet += doc.resource_s;

//    if (doc.text.length > 300) {
//      snippet += doc.dateline + ' ' + doc.text.substring(0, 300);
//      snippet += '<span style="display:none;">' + doc.text.substring(300);
//      snippet += '</span> <a href="#" class="more">more</a>';
//    }
//    else {
//      snippet += doc.dateline + ' ' + doc.text;
//    }
       var abstract = '';

      if (typeof(doc.abstract_txt)!='undefined'){
          if (doc.abstract_txt.length > 1){
              abstract = doc.abstract_txt[0];
          } else {
              abstract = doc.abstract_txt;
          }
      }

      if (doc.depiction_s != ''){
          snippet += '<img class="result-img" src="'+doc.depiction_s+'"/>';
      }

    if (abstract.length > 300) {
      snippet +=  abstract.substring(0, 300);
      snippet += '<span style="display:none;">' + abstract.substring(300);
      snippet += '</span> <a href="#" class="more">more</a>';
    }
    else {
      snippet +=  abstract;
    }

      var label = '';
 
      if (typeof(doc.label_ss)!='undefined'){
        if (doc.label_ss.length > 1){
           label = doc.label_ss[0];
        } else {
	  label = doc.label_ss;
        }
      }
    var output = '<div><h2>' + label + '</h2>';
    output += '<p id="links_' + doc.id + '" class="links"></p>';
    output += '<p>' + snippet + '</p>';


    //output += '<p><a href="javascript:window[obj].callEdit('+doc.id+')">EDIT</a></p>';
    output += '<p><a href="javascript:window[window[\'korboeeConfig\'].globalObjectName].callEdit('+doc.id+')">EDIT</a></p>';

    output +='</div>';

      //baseurl ID  = ' + doc.id + ' BASKET = ' + doc.basked_id_s + '">


    return output;
  },

  init: function () {
    $(document).on('click', 'a.more', function () {
      var $this = $(this),
          span = $this.parent().find('span');

      if (span.is(':visible')) {
        span.hide();
        $this.text('more');
      }
      else {
        span.show();
        $this.text('less');
      }

      return false;
    });
  }
});

})(jQuery);

}));
