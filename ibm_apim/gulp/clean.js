'use strict';

var gulp = require('gulp');
var del = require('del');

gulp.task('clean:dist', function () {
  return del([
    '.tmp',
    'dist/*'
  ]);
});

gulp.task('clean:server', function () {
  return del([
    '.tmp'
  ]);
});