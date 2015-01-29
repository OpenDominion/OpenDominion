var gulp = require('gulp'),
	less = require('gulp-less'),
	minify = require('gulp-minify-css'),
	concat = require('gulp-concat'),
	uglify = require('gulp-uglify'),
	rename = require('gulp-rename'),
	notify = require('gulp-notify'),
	phpunit = require('gulp-phpunit');

var paths = {
	'src': {
		'less': './resources/assets/less/',
		'js': './resources/assets/js/',
		'vendor': './resources/assets/vendor/'
	},
	'dst': {
		'css': './public/assets/css/',
		'js': './public/assets/js/'
	}
};

// CSS
gulp.task('css', function () {
	return gulp.src(paths.src.less + 'app.less')
		.pipe(less())
		.pipe(gulp.dest(paths.dst.css))
		.pipe(minify({keepSpecialComments: 0}))
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest(paths.dst.css));
});

// JS
gulp.task('js', function () {
	return gulp.src([
		paths.src.vendor + 'jquery/dist/jquery.js',
		paths.src.vendor + 'bootstrap/dist/js/bootstrap.js',
		paths.src.js + 'app.js'
	])
		.pipe(concat('app.min.js'))
		.pipe(uglify())
		.pipe(gulp.dest(paths.dst.js));
});

// PHP Unit
//gulp.task('phpunit', function () {
//	var options = {debug: false, notify: true};
//	return gulp.src('./tests/*.php')
//		.pipe(phpunit('./vendor/bin/phpunit', options))
//
//		.on('error', notify.onError({
//			title: 'PHPUnit Failed',
//			message: 'One or more tests failed.'
//		}))
//
//		.pipe(notify({
//			title: 'PHPUnit Passed',
//			message: 'All tests passed!'
//		}));
//});

gulp.task('watch', function() {
	gulp.watch(paths.src.less + '/*.less', ['css']);
	gulp.watch(paths.src.js + '/*.js', ['js']);
	//gulp.watch('./tests/*.php', ['phpunit']);
});

gulp.task('default', ['css', 'js']);
