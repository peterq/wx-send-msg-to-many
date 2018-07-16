define('es6', ['babel', 'module'], function(babel, _module) {

  'use strict';
  var reg = /import\s+(\w+)\s+from\s+[\'\"](\S+\/comp\/\S+)[\'\"]/g
  var fetchText, buildMap = {};
  if (typeof window !== "undefined" && window.navigator && window.document) {
    fetchText = function(url, callback) {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url, true);
      xhr.onreadystatechange = function(evt) {
        // Do not explicitly handle errors, those should be
        // visible via console output in the browser.
        if (xhr.readyState === 4) {
          callback(xhr.responseText);
        }
      };
      xhr.send(null);
    };
  }

  return {
    version: '0.5.0',

    load: function(name, req, onload, config) {

      var babelOptions;
      try {
        // Deep clone babel config (#7)
        babelOptions = JSON.parse(JSON.stringify(config.babel));
        // clone callbacks from babel config manually
        var callbackNames = ['getModuleId', 'resolveModuleSource', 'shouldPrintComment', 'wrapPluginVisitorMethod'];
        callbackNames.forEach(function(key) {
          babelOptions[key] = config.babel[key];
        })
      } catch (err) {
        babelOptions = {}
        onload.error(err);
      }

      var pluginOptions = config.es6 || {},
        fileExtension = pluginOptions.fileExtension || '.js',
        sourceFileName = name + fileExtension,
        url = req.toUrl(sourceFileName);

      // Do not load if it is an empty: url
      if (url.indexOf('empty:') === 0) {
        onload();
        return;
      }

      var defaults = {
        sourceMaps: config.isBuild ? false : 'inline',
        sourceFileName: sourceFileName
      };
      for (var key in defaults) {
        babelOptions[key] = defaults[key];
      }

      fetchText(url, function(text) {
        try {
          text = text.replace(reg, "import $1 from 'es6!$2'")
          var code = babel.transform(text, babelOptions).code;
        } catch (err) {
          onload.error(err);
        }

        if (config.isBuild) {
          buildMap[name] = code;
        }

        // console.log(code)
        onload.fromText(code);
      });
    },

    write: function(pluginName, moduleName, write) {
      if (moduleName in buildMap) {
        write.asModule(pluginName + '!' + moduleName, buildMap[moduleName]);
      }
    }
  }
});