PWD = $(shell pwd)


install: .env composer.json composer

test: phpunit lint

composer:
	docker run --init -it --rm \
    	-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
    	jakzal/phpqa:php7.4-alpine composer $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

.env:
	cp -n .env.dist .env

phpunit:
	docker run --init -it --rm \
	-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
	jakzal/phpqa:php7.4-alpine phpunit \
	-c /project/phpunit.xml.dist

console:
	docker build -t php-with-pcntl:0.1 ./docker && \
	docker run --rm -it -v $(PWD):/usr/src/myapp php-with-pcntl:0.1 \
		  /usr/src/myapp/bin/console.php $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

server:

phpstan:
	docker run --init -it --rm \
    		-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
    		jakzal/phpqa:php7.4-alpine phpstan analyse \
    		--level=4 src 
lint-fix:
		docker run --init -it --rm \
		-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
		jakzal/phpqa:php7.4-alpine php-cs-fixer fix --diff \
		--verbose --show-progress=estimating --allow-risky=yes \
		$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

lint:
		docker run --init -it --rm \
		-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
		jakzal/phpqa:php7.4-alpine php-cs-fixer fix --diff --dry-run \
		--verbose --show-progress=estimating --allow-risky=yes \
		$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
