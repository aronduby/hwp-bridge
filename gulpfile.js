var gulp          = require('gulp'),
	templateCache = require('gulp-angular-templatecache');

// cache the angular template files
gulp.task('scoring-templates', function () {
	return gulp.src('site/admin/partials/**/*.html')
		.pipe(templateCache({
			root: 'partials/',
			standalone: true
		}))
		.pipe(gulp.dest('site/admin/js'));
});

// Watch Files For Changes
gulp.task('watch', function () {
	gulp.watch('site/admin/partials/**/*.html', ['scoring-templates']);
});

// Default Task
gulp.task('default', ['scoring-templates', 'watch']);