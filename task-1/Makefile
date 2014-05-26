all: test

.PHONY: test
test:
	find ./tests -name '*.diff' | xargs rm -f
	find ./tests -name '*.log' | xargs rm -f
	find ./tests -name '*.exp' | xargs rm -f
	find ./tests -name '*.php' | xargs rm -f
	find ./tests -name '*.sh' | xargs rm -f
	php run-tests.php -p `which php` -d 'auto_prepend_file=parse_url.php'