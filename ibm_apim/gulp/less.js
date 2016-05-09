'use strict';

var less = require('gulp-less');
var notify = require('gulp-notify');
var gulp = require('gulp');
 
gulp.task('less', function () {
  return gulp.src(['css/mesh.less', 'css/product.less'])
    .pipe(less())
	.on("error", notify.onError(function (err) {
	return "less error: " + err.message;
	}))
    .pipe(gulp.dest('css'));
});