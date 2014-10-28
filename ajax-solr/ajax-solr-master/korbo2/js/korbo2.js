if (typeof(basket_id)=='undefined' || $.trim(basket_id) == ''){
    alert('no basket id provided!');
} else {


var Manager;

require.config({
  paths: {
    core: '../../core',
    managers: '../../managers',
    widgets: '../../widgets',
    korbo2: '../widgets'
  },
//  urlArgs: "bust=" +  (new Date()).getTime()
  urlArgs: ""
});

(function ($) {

define([
  'managers/Manager.jquery',
  'core/ParameterStore',
  'korbo2/ResultWidget',
  'korbo2/TagcloudWidget',
  'korbo2/CurrentSearchWidget',
  'korbo2/AutocompleteWidget',
  'korbo2/CountryCodeWidget',
  'korbo2/CalendarWidget',
  'widgets/jquery/PagerWidget'
], function () {
  $(function () {
    Manager = new AjaxSolr.Manager({
	solrUrl: 'http://thepund.it:8080/korbo2-solr/collection1/'
//	solrUrl: 'http://localhost:8983/solr/collection1/'
    });
    Manager.addWidget(new AjaxSolr.ResultWidget({
      id: 'result',
      target: '#docs'
    }));
    Manager.addWidget(new AjaxSolr.PagerWidget({
      id: 'pager',
      target: '#pager',
      prevLabel: '&lt;',
      nextLabel: '&gt;',
      innerWindow: 1,
      renderHeader: function (perPage, offset, total) {
        $('#pager-header').html($('<span></span>').text('displaying ' + Math.min(total, offset + 1) + ' to ' + Math.min(total, offset + perPage) + ' of ' + total));
      }
    }));
    var fields = [ 'type_ss', 'label_ss', 'resource_s' ];
    for (var i = 0, l = fields.length; i < l; i++) {
      Manager.addWidget(new AjaxSolr.TagcloudWidget({
        id: fields[i],
        target: '#' + fields[i],
        field: fields[i]
      }));
    }
    Manager.addWidget(new AjaxSolr.CurrentSearchWidget({
      id: 'currentsearch',
      target: '#selection'
    }));
    Manager.addWidget(new AjaxSolr.AutocompleteWidget({
      id: 'text',
      target: '#search',
      fields: ['type_ss', 'resource_s' ,'label_ss', 'abstract_txt' ]
    }));

    Manager.init();


    Manager.store.addByValue('fq', 'basket_id_s:' + basket_id);
    Manager.store.addByValue('q', '*:*');

    var params = {
      facet: true,
	'facet.field': [ 'resource_s', 'type_ss', 'label_ss'],
      'facet.limit': 20,
      'facet.mincount': 1,
//      'f.topics.facet.limit': 50,
      'f.resource_s.facet.limit': 50,
      'f.types_ss.facet.limit': 50,
      'f.label_ss.facet.limit': 50,
//      'facet.date': 'date',
//      'facet.date.start': '1987-02-26T00:00:00.000Z/DAY',
//      'facet.date.end': '1987-10-20T00:00:00.000Z/DAY+1DAY',
//      'facet.date.gap': '+1DAY',
      'json.nl': 'map',
//       'fq': 'basket_id_s:' +basket_id,
       'q': '*:*'
    };
    for (var name in params) {
      Manager.store.addByValue(name, params[name]);
    }
    Manager.doRequest();
  });

  $.fn.showIf = function (condition) {
    if (condition) {
      return this.show();
    }
    else {
      return this.hide();
    }
  }
});

})(jQuery);

}