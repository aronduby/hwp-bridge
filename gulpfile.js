var gulp          = require('gulp'),
	templateCache = require('gulp-angular-templatecache');

// cache the angular template files
gulp.task('scoring-templates', function () {
	return gulp.src('admin/partials/**/*.html')
		.pipe(templateCache({
			root: 'partials/',
			standalone: true
		}))
		.pipe(gulp.dest('admin/js'));
});

// Watch Files For Changes
gulp.task('watch', function () {
	gulp.watch('admin/partials/**/*.html', ['scoring-templates']);
});

// Default Task
gulp.task('default', ['scoring-templates', 'watch']);