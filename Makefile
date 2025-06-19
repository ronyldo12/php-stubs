CONTAINER_NAME=stub-php-test

build:
	docker build -t stub-php .

install: build
	docker run --rm -v $(PWD):/app --entrypoint=composer --name $(CONTAINER_NAME) stub-php install

test: build install
	docker run --rm --entrypoint=php --name $(CONTAINER_NAME) stub-php vendor/bin/phpunit tests
	
test-coverage: build install
	docker run --rm -v $(PWD):/app --entrypoint=php --name stub-php-test -e XDEBUG_MODE=coverage stub-php vendor/bin/phpunit --coverage-clover=coverage.xml tests