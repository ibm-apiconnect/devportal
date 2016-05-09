'use strict';

var gulp = require('gulp');
var copy = require('gulp-copy');

gulp.task('copy:dist', ['clean'], function () {
  return gulp.src([
	    'apps/**',
	    'css/**',
	    'nls/**',
  	])
    .pipe(copy('dist/'));
});

gulp.task('copy:mdfonts', function () {
  return gulp.src([
	  'node_modules/material-design-icons/iconfont/MaterialIcons-Regular.woff',
	  'node_modules/material-design-icons/iconfont/MaterialIcons-Regular.woff2',
	  'node_modules/material-design-icons/iconfont/MaterialIcons-Regular.ttf'
    ])
    .pipe(copy('fonts', {prefix: 3}));
});