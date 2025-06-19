CONTAINER_NAME=stub-php-test

install:
	docker build -t stub-php .
	docker run --rm -v $(PWD):/app --entrypoint=composer --name $(CONTAINER_NAME) stub-php install

test:
	docker build -t stub-php .
	docker run --rm -v $(PWD):/app --entrypoint=php --name $(CONTAINER_NAME) stub-php vendor/bin/phpunit tests