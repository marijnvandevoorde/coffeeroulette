PWD = $(shell pwd)


install: .env composer.json composer-install

test: phpunit lint

composer:
	docker run --init -it --rm \
    	-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
    	jakzal/phpqa:php7.4-alpine composer $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

composer-install:
	docker run --init -it --rm \
    	-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
    	jakzal/phpqa:php7.4-alpine composer install

.env:
	cp -n .env.dist .env

phpunit:
	docker run --init -it --rm \
	-v "$(PWD):/project" -v "$(PWD)/tmp-phpqa:/tmp" -w /project \
	jakzal/phpqa:php7.4-alpine phpunit \
	-c /project/phpunit.xml.dist

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


generatekey: install
	docker-compose run --rm php-fpm php bin/cryptutil.php