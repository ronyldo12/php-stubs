CONTAINER_NAME=stub-php-test

build:
	docker build -t stub-php .

install: build
	docker run --rm -v $(PWD):/app --entrypoint=composer --name $(CONTAINER_NAME) stub-php install

test: build install
	docker run --rm -v $(PWD):/app --entrypoint=php --name stub-php-test stub-php vendor/bin/phpunit tests
	
test-coverage: build install
	docker run --rm -v $(PWD):/app --entrypoint=php --name stub-php-test -e XDEBUG_MODE=coverage stub-php vendor/bin/phpunit --coverage-clover=coverage.xml --coverage-text -d --min-coverage=100 tests

test-coverage-html: build install
	docker run --rm -v $(PWD):/app --user root --entrypoint=php --name stub-php-test -e XDEBUG_MODE=coverage stub-php vendor/bin/phpunit --coverage-html=coverage-html tests
	docker rm -f $(CONTAINER_NAME)_coverage
	docker run -d -p 3877:80 --name $(CONTAINER_NAME)_coverage \
		-v $(PWD)/coverage-html:/usr/share/nginx/html:ro \
		--user root \
		nginx
	echo "Coverage report available at: http://localhost:3877"