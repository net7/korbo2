<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Korbo Search</title>


    <script>
        var basket_id = '<?php echo $_GET['basket_id'];?>';
    </script>



  <link rel="stylesheet" href="css/reuters.css">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/themes/smoothness/jquery-ui.css">
  <script data-main="js/korbo2" src="http://cdnjs.cloudflare.com/ajax/libs/require.js/2.1.5/require.min.js"></script>

    <link rel="stylesheet" href="http://dev.thepund.it/download/client/last-beta/pundit2.css" type="text/css">
    <script src="http://dev.thepund.it/download/client/last-beta/libs.js" type="text/javascript" ></script>
    <script src="http://dev.thepund.it/download/client/last-beta/pundit2.js" type="text/javascript" ></script>
    <script src="http://dev.thepund.it/download/client/last-beta/korboee_conf.js" type="text/javascript" ></script>

</head>
<body>
  <div id="wrap"> 
    <div id="header">
      <h1>Korbo Search</h1>
      <h2>Browse Burckhardt data</h2>
    </div>

    <div class="right">
      <div id="result">
        <div id="navigation">
          <ul id="pager"></ul>
          <div id="pager-header"></div>
        </div>
        <div id="docs"></div>
      </div>
    </div>

    <div class="left">
      <h2>Current Selection</h2>
      <ul id="selection"></ul>

      <h2>Search</h2>
      <span id="search_help">(press ESC to close suggestions)</span>
      <ul id="search">
        <input type="text" id="query" name="query" autocomplete="off">
      </ul>

      <h2>Types</h2>
      <div class="tagcloud" id="type_ss"></div>

<!--
      <h2>Resources</h2>
      <div class="tagcloud" id="resource_s"></div>
-->

      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  </div>


  <div data-ng-app="Pundit2">
      <korbo-entity-editor conf-name="korboeeConfig"></korbo-entity-editor>
  </div>

<script type="text/javascript">
      var obj = window['korboeeConfig'].globalObjectName;
</script>
</body>
</html>
