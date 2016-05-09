'use strict';

var gulp = require('gulp');
var replace = require('gulp-replace');
var rename = require("gulp-rename");
var fs = require('fs');

gulp.task('replace:nls', function () {
  return gulp.src('localization.js')
  .pipe(replace('__inline(nls/en.json)', fs.readFileSync('nls/en.json')))
  .pipe(replace('__inline(nls/de.json)', fs.readFileSync('nls/de.json')))
  .pipe(replace('__inline(nls/es.json)', fs.readFileSync('nls/es.json')))
  .pipe(replace('__inline(nls/fr.json)', fs.readFileSync('nls/fr.json')))
  .pipe(replace('__inline(nls/it.json)', fs.readFileSync('nls/it.json')))
  .pipe(replace('__inline(nls/ja.json)', fs.readFileSync('nls/ja.json')))
  .pipe(replace('__inline(nls/ko.json)', fs.readFileSync('nls/ko.json')))
  .pipe(replace('__inline(nls/pt_BR.json)', fs.readFileSync('nls/pt_BR.json')))
  .pipe(replace('__inline(nls/tr.json)', fs.readFileSync('nls/tr.json')))
  .pipe(replace('__inline(nls/zh.json)', fs.readFileSync('nls/zh.json')))
  .pipe(replace('__inline(nls/zh_TW.json)', fs.readFileSync('nls/zh_TW.json')))
  .pipe(rename('localization-inline.js'))
  .pipe(gulp.dest('.'));
});
