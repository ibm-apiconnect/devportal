'use strict';

var gulp = require('gulp');
 
gulp.task('watch:nls', function () {
  return gulp.watch('nls/*.json', ['replace:nls']);
});
 
gulp.task('watch:js', function () {
  return gulp.watch('apps/**/*.js', ['jshint']);
});
 
gulp.task('watch:less', function () {
  return gulp.watch('css/**.less', ['less']);
});

gulp.task('watch', ['watch:nls', 'watch:js', 'watch:less']);