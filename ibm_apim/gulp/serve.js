'use strict';

var gulp = require('gulp');
var express = require('express');
var browserOpen = require('open');

var devPort = 9010;
var distPort = 9011;

gulp.task('serve', ['watch'], function() {
  var app = express();
  app.use('/', express.static('.'));
  var server = app.listen(devPort, function() {
    console.log('Express server listening on port ' + server.address().port);
    browserOpen('http://localhost:' + server.address().port);
  });
});

gulp.task('serve:dist', function() {
  var app = express();
  app.use('/', express.static('dist'));
  var server = app.listen(distPort, function() {
    console.log('Express server listening on port ' + server.address().port);
    browserOpen('http://localhost:' + server.address().port);
  });
});