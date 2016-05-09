'use strict';

var gulp   = require('gulp');
var notify = require('gulp-notify');
var jshint = require('gulp-jshint');
var stylish = require('jshint-stylish');
 
gulp.task('jshint', function() {
  return gulp.src(['apps/**/*.js', 'gulp/*.js'])
  	.pipe(jshint())
  	.pipe(jshint.reporter(stylish))
  	.pipe(notify(function (file) {
      if (!file.jshint || file.jshint.success) {
        // Don't show something if success
        return false;
      }

      var errors = file.jshint.results.map(function (data) {
        if (data.error) {
          return "(" + data.error.line + ':' + data.error.character + ') ' + data.error.reason;
        }
      }).join("\n");
      return file.relative + " (" + file.jshint.results.length + " errors)\n" + errors;
    }));
});
