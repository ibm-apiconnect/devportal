'use strict';

var gulp = require('gulp');
var usemin = require('gulp-usemin');
var uglify = require('gulp-uglify');
var htmlmin = require('gulp-htmlmin');
var cssnano = require('gulp-cssnano');
var rev = require('gulp-rev');
var ngAnnotate = require('gulp-ng-annotate');
var debug = require('gulp-debug');
var notify = require('gulp-notify');

gulp.task('usemin', ['usemin:index', 'usemin:cloud', 'usemin:login']);

gulp.task('usemin:index', ['prep'], function() {
  return gulp.src('app/index.html')
  	.pipe(debug({title: "usemin index in"}))
    .pipe(usemin({
      csstheme: [ cssnano(), rev() ],
      csslegacy: [ cssnano(), rev() ],
      cssapim: [ cssnano(), rev() ],
      jsvendor: [ ngAnnotate(), uglify(), rev() ],
      jsapim: [ ngAnnotate(), uglify(), rev() ]
    }))
    .on('error', notify.onError("Error: <%= error.message %>"))
  	.pipe(debug({title: "usemin index out"}))
    .pipe(gulp.dest('dist/'));
});

gulp.task('usemin:cloud', ['prep'], function() {
  return gulp.src('app/cmc.html')
  	.pipe(debug({title: "usemin cmc in"}))
    .pipe(usemin({
      csstheme: [ cssnano(), rev() ],
      csslegacy: [ cssnano(), rev() ],
      cssapim: [ cssnano(), rev() ],
      jsvendor: [ ngAnnotate(), uglify(), rev() ],
      jsapim: [ ngAnnotate(), uglify(), rev() ]
    }))
    .on('error', notify.onError("Error: <%= error.message %>"))
  	.pipe(debug({title: "usemin cmc out"}))
    .pipe(gulp.dest('dist/'));
});

gulp.task('usemin:login', ['prep'], function() {
  return gulp.src('app/login.jsp')
  	.pipe(debug({title: "usemin login in"}))
    .pipe(usemin({
      csstheme: [ cssnano(), rev() ],
      csslegacy: [ cssnano(), rev() ],
      cssapim: [ cssnano(), rev() ],
      jsvendor: [ ngAnnotate(), uglify(), rev() ],
      jsapim: [ ngAnnotate(), uglify(), rev() ]
    }))
    .on('error', notify.onError("Error: <%= error.message %>"))
  	.pipe(debug({title: "usemin login out"}))
    .pipe(gulp.dest('dist/'));
});